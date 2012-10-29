#----------------------------------------------------------------------
# Copyright 1999-2003 Mitel Networks Corporation
# This program is free software; you can redistribute it and/or
# modify it under the same terms as Perl itself.
#----------------------------------------------------------------------

package esmith::NetworksDB;

use strict;
use warnings;

use esmith::DB::db;
our @ISA = qw( esmith::DB::db );

=head1 NAME

esmith::NetworksDB - interface to esmith networks database

=head1 SYNOPSIS

    use esmith::NetworksDB;
    my $c = esmith::NetworksDB->open;

    # everything else works just like esmith::DB::db

=head1 DESCRIPTION

This module provides an abstracted interface to the esmith master
configuration database.

Unless otherwise noted, esmith::NetworksDB acts like esmith::DB::db.

=cut

=head2 open()

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

=head2 open_ro()

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

=head2 networks

Return a list of all objects of type "network".

=cut

sub networks {
    my ($self) = @_;
    return $self->get_all_by_prop(type => 'network');
}

=head2 local_access_spec ([$access])

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

=head1 AUTHOR

SME Server Developers <bugs@e-smith.com>

=head1 SEE ALSO

L<esmith::DB::db>

L<esmith::DB::Record>

=cut

1;
