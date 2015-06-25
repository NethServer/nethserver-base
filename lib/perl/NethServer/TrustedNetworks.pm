#
# Copyright (C) 2015 Nethesis S.r.l.
# http://www.nethesis.it - nethserver@nethesis.it
#
# This script is part of NethServer.
#
# NethServer is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License,
# or any later version.
#
# NethServer is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with NethServer.  If not, see COPYING.
#

use strict;
package NethServer::TrustedNetworks;
use base 'Exporter';
our @EXPORT_OK = qw(register_callback);

use File::Basename qw(dirname);
use esmith::util;

my @callbacks = ();

INIT {
    my $dir = dirname($INC{'NethServer/TrustedNetworks.pm'});
    foreach (glob qq($dir/TrustedNetworks/*.pm)) {
        require "$_";
    }
}


=head1 NAME

NethServer::TrustedNetworks -- extensible module for trusted networks
providers

=head1 DESCRIPTION

A network can be trusted for disparate reasons, and cannot be defined
a priori.   This module defines an API to

=over

=item * register a custom "provider" function, that returns some trusted
networks when invoked

=item * retrieve the complete list of trusted networks from the registered
providers

=back

To define a provider function, add a Perl module under
TrustedNetworks/ directory, with namespace prefix
NethServer::TrustedNetworks.

For a real example, see the file
Default.pm.

=head1 USAGE

This is an example provider "Provider1" definition.

 package NethServer::TrustedNetworks::Provider1;
 use NethServer::TrustedNetworks qw(register_callback);

 register_callback(&provider1);

 sub provider1
 {
    my $results = shift;
    ... # do something with $results (array ref)
 }

User code example, that retrieves the list of trusted networks in CIDR
format:

 use NethServer::TrustedNetworks;

 print join(",", NethServer::TrustedNetworks::list_cidr()) . "\n";

=head1 API FUNCTIONS

=over

=item * register_callback($func_ref, $order = undef)

=item * list_cidr() returns the trusted networks list with CIDR format
(i.e. 192.168.1.0/24)

=item * list_mask() returns the trusted networks list with netmask
format (i.e 192.168.1.0/255.255.255.0)

=back

=cut

sub register_callback
{
    my $func = shift;
    my $order = shift;
    push @callbacks, [$func, $order || 50];
}

sub list_cidr
{
    return _run_callbacks();
}

sub list_mask
{
    return map {
        my($net, $bits) = split /\//, $_;
        "$net/" . esmith::util::computeNetmaskFromBits($bits);
    } list_cidr();
}

sub _run_callbacks
{
    my @results = ();
    foreach (sort { $a->[1] <=> $b->[1] } @callbacks) {
        &{$_->[0]}(\@results);
    }
    my %unique = map { $_ => 1 } @results;
    return sort keys %unique;
}


1;
