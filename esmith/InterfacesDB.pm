#----------------------------------------------------------------------
# Copyright 2012 Nethesis - www.nethesis.it
# This program is free software; you can redistribute it and/or
# modify it under the same terms as Perl itself.
#----------------------------------------------------------------------

package esmith::InterfacesDB;

use strict;
use warnings;

use esmith::DB::db;
our @ISA = qw( esmith::DB::db );

=head1 NAME

esmith::InterfacesDB - interface to NethServer network interfaces database

=head1 SYNOPSIS

    use esmith::InterfacesDB;
    my $hosts = esmith::InterfacesDB->open;

    # everything else works just like esmith::DB::db

    # these methods are added
    my @interfaces     = $interfaces->interfaces;

=head1 DESCRIPTION

This module provides an abstracted interface to the  NethServer network interfaces
database.

Unless otherwise noted, esmith::InterfacesDB acts like esmith::DB::db.

=cut

=head2 Overridden methods

=over 4

=item I<open>

Like esmith::DB->open, but if given no $file it will try to open the
file in the ESMITH_NETWORK_INTERFACES_DB environment variable or hosts.

=cut

sub open {
    my($class, $file) = @_;
    $file = $file || $ENV{ESMITH_NETWORK_INTERFACES_DB} || "network_interfaces";
    return $class->SUPER::open($file);
}

=head2 open_ro()

Like esmith::DB->open_ro, but if given no $file it will try to open the
file in the ESMITH_NETWORK_INTERFACES_DB environment variable or hosts.

=cut

sub open_ro {
    my($class, $file) = @_;
    $file = $file || $ENV{ESMITH_NETWORK_INTERFACES_DB} || "network_interfaces";
    return $class->SUPER::open_ro($file);
}
=back

=head2 Additional Methods

These methods are added be esmith::InterfacesDB

=over 4

=item I<interfaces>

    my @interfaces = $interfaces->interfaces;

Returns a list of all interface records in the database.

=cut

sub interfaces {
    my ($self) = @_;
    return $self->get_all();
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

Returns the interface with the given role, if exsists. Returns undef, otherwise.

=cut

sub get_by_role {
    my ($self, $role) = @_;
    my @t = $self->get_all_by_prop('role' => $role);
    if ( scalar(@t) > 0) { 
      return $t[0];
    }
    return undef;
}

=item I<green>

Returns the interface with green role.

=cut

sub green {
    my ($self) = @_;
    return $self->get_by_role('green');
    
}

=item I<orange>

Returns the interface with orange role.

=cut

sub orange {
    my ($self) = @_;
    return $self->get_by_role('orange');
}

=item I<blue>

Returns the interface with blue role.

=cut

sub blue {
    my ($self) = @_;
    return $self->get_by_role('blue');
}

=item I<yellow>

Returns the interface with yellowe role.

=cut

sub yellow {
    my ($self) = @_;
    return $self->get_by_role('yellow');
}

=item I<red1>

Returns the interface with red1 role.

=cut

sub red1 {
    my ($self) = @_;
    return $self->get_by_role('red1');
}

=item I<red2>

Returns the interface with red2 role.

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
