#
# Copyright (C) 2013 Nethesis S.r.l.
# Original work by: Copyright 1999-2003 Mitel Networks Corporation
# http://www.nethesis.it - support@nethesis.it
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
# along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
#
package esmith::NetworksDB;

use strict;
use warnings;

use esmith::DB::db;
use esmith::util;
our @ISA = qw( esmith::DB::db );

=head1 NAME

esmith::NetworksDB - interface to esmith networks database

=head1 SYNOPSIS

    use esmith::NetworksDB;
    my $c = esmith::NetworksDB->open;

    # everything else works just like esmith::DB::db

=head1 DESCRIPTION

This module provides an abstracted interface to the esmith networks
database.

Unless otherwise noted, esmith::NetworksDB acts like esmith::DB::db.

=cut

=head2 Original network database methods

=over 4

=item I<open>

Like esmith::DB->open, but if given no $file it will try to open the
file in the ESMITH_NETWORKS_DB environment variable or networks.

=begin testing

use_ok("esmith::NetworksDB");

$C = esmith::NetworksDB->open('10e-smith-lib/networks.conf');
isa_ok($C, 'esmith::NetworksDB');
is( $C->get("10.0.0.0")->prop('Mask'), "255.255.255.0", 
                                    "We can get stuff from the db");

=end testing

=cut

sub open
{
    my ( $class, $file ) = @_;
    $file = $file || $ENV{ESMITH_NETWORKS_DB} || "networks";
    return $class->SUPER::open($file);
}

=item I<open_ro>

Like esmith::DB->open_ro, but if given no $file it will try to open the
file in the ESMITH_NETWORKS_DB environment variable or networks.

=begin testing

=end testing

=cut

sub open_ro
{
    my ( $class, $file ) = @_;
    $file = $file || $ENV{ESMITH_NETWORKS_DB} || "networks";
    return $class->SUPER::open_ro($file);
}

=item I<networks>

Return a list of all objects of type "network".

=cut

sub networks {
    my ($self) = @_;
    return $self->get_all_by_prop(type => 'network');
}

=item I<local_access_spec ([$access])>

Compute the network/netmask entries which are to treated as local access.

There is also an optional access parameter which can further restrict 
the values returned. If C<access> is C<localhost>, this routine will only
return a single value, equating to access from localhost only.

If called in scalar context, the returned string is suitable for 
use in /etc/hosts.allow, smb.conf and httpd.conf, for example:

127.0.0.1 192.168.1.1/255.255.255.0

Note: The elements are space separated, which is suitable for use in
hosts.allow, smb.conf and httpd.conf. httpd.conf does not permit 
comma separated lists in C<allow from> directives. Each element is either
an IP address, or a network/netmask string.

If called in list context, returns the array of addresses and network/netmask
strings. It's trivial, of course, to convert an array to a comma separated
list :-)

=cut

sub local_access_spec
{
    my $self   = shift;
    my $access = shift || "private";

    my @localAccess = ("127.0.0.1");    

    if($self && $self->green()) {
	my $greenNetwork = esmith::util::computeLocalNetworkSpec(
	    $self->green()->prop('ipaddr'),
	    $self->green()->prop('netmask')
	);
	if($greenNetwork) {
	    push @localAccess, $greenNetwork;
	}
    }

    if ( $access eq "localhost" )
    {
        # Nothing more to do
    }
    elsif ( $access eq "private" )
    {
        foreach my $network ( $self->networks )
        {
	    my $element = $network->key;
            my $mask = $network->prop('Mask');
	    $element .= "/$mask" unless ($mask eq "255.255.255.255");
            push @localAccess, $element;
        }
    }
    elsif ( $access eq "public" )
    {
        @localAccess = ("ALL");
    }
    else
    {
        warn "local_access_spec: unknown access value $access\n";
    }
    return wantarray ? @localAccess : "@localAccess";
}

=back

=head2 Network interfaces methods

=over 4

=item I<interfaces>

    my @interfaces = $interfaces->interfaces;

Returns a list of all interface records in the database.

=cut

sub interfaces {
    my ($self) = @_;
    return grep { $_->prop('type') =~ /^(ethernet|bridge|bond|alias|ipsec)$/ } $self->get_all();
}


=item I<ethernets>

    my @interfaces = $interfaces->ethernets;

Returns a list of all interfaces of type 'ethernet'.

=cut

sub ethernets {
    my ($self) = @_;
    return $self->get_all_by_prop('type' => 'ethernet');
}

=item I<bridges>

    my @interfaces = $interfaces->bridges;

Returns a list of all interfaces of type 'bridges'.

=cut

sub bridges {
    my ($self) = @_;
    return $self->get_all_by_prop('type' => 'bridge');
}

=item I<bonds>

    my @interfaces = $interfaces->bonds;

Returns a list of all interfaces of type 'bond'.

=cut

sub bonds {
    my ($self) = @_;
    return $self->get_all_by_prop('type' => 'bond');
}

=item I<aliases>

    my @interfaces = $interfaces->aliases;

Returns a list of all interfaces of type 'aliases'.

=cut

sub aliases {
    my ($self) = @_;
    return $self->get_all_by_prop('type' => 'alias');
}

=item I<ipsecs>

    my @interfaces = $interfaces->ipsecs;

Returns a list of all interfaces of type 'ipsecs'.

=cut

sub ipsecs {
    my ($self) = @_;
    return $self->get_all_by_prop('type' => 'ipsec');
}

=item I<get_by_role>

    my @interfaces = $interfaces->get_by_role('myrole');

Returns the interface(s) with the given role, if exsists. Returns undef, otherwise.

The return type is context sensible. In array context a list is
returned, in scalar context a Record is returned, if at least one
exists.

=cut

sub get_by_role {
    my ($self, $role) = @_;
    my @t = $self->get_all_by_prop('role' => $role);
    if ( wantarray ) {
	return @t;
    } 
    return (scalar @t > 0) ? $t[0] : undef;
}

=item I<green>

Returns the interface(s) with green role.

=cut

sub green {
    my ($self) = @_;
    return $self->get_by_role('green');   
}

=item I<orange>

Returns the interface(s) with orange role.

=cut

sub orange {
    my ($self) = @_;
    return $self->get_by_role('orange');
}

=item I<blue>

Returns the interface(s) with blue role.

=cut

sub blue {
    my ($self) = @_;
    return $self->get_by_role('blue');
}

=item I<yellow>

Returns the interface(s) with yellowe role.

=cut

sub yellow {
    my ($self) = @_;
    return $self->get_by_role('yellow');
}

=item I<red1>

Returns the interface(s) with red1 role.

=cut

sub red1 {
    my ($self) = @_;
    return $self->get_by_role('red1');
}

=item I<red2>

Returns the interface(s) with red2 role.

=cut

sub red2 {
    my ($self) = @_;
    return $self->get_by_role('red2');
} 


=back

=head1 AUTHOR

Giacomo Sanchietti - Nethesis <support@nethesis.it>

=head1 SEE ALSO

L<esmith::ConfigDB>

=cut

1;
