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
