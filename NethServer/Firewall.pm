#
# Copyright (C) 2012 Nethesis S.r.l.
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

package NethServer::Firewall;

use strict;
use esmith::ConfigDB;
use esmith::NetworksDB;
use esmith::HostsDB;
use esmith::util;
use NetAddr::IP;

use Exporter qw(import);
our @EXPORT_OK = qw(FIELDS_READ FIELDS_WRITE);

=head2 new

Create a NethServer::Firewall instance.

=cut
sub new
{
    my $class = shift;
    my $self = {};
    bless $self, $class;
    $self->_initialize();
    
    return $self;
}


sub _initialize()
{
    my $self = shift;
    $self->{'ndb'} = esmith::NetworksDB->open_ro();
    $self->{'cdb'} = esmith::ConfigDB->open_ro();
    $self->{'hdb'} = esmith::HostsDB->open_ro();
}

=head2 getAddress(id)

Return the address value corresponding to given id.
If id matches a valid IP or CIDR syntax, simply return it.
Otherwise lookup for the id inside other databases and return the 
value of the key.

=cut
sub getAddress($)
{
    my $self = shift;
    my $id = shift;

    if ( $id =~ m/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/ ) {
        return $id; # IP address
    }
    if ( $id =~ m/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))$/ ) {
        return $id; # CIDR address
    }
   
    if ( $id =~ m/;/ ) { # lookup is needed
        my ($db, $key) = split(';', $id);
        if ( $db eq 'host') {
            return $self->_getHostAddress($key);
        } elsif ( $db eq 'host-group' ) {
            return $self->_getHostGroupAddresses($key);
        } elsif ( $db eq 'zone' ) {
            return $key;
        }
    } 

    return '';
}


=head2 getPorts(id)

Return the port value corresponding to given service id.
If id matches a valid port or port list syntax, simply return it.
Otherwise lookup for the id inside other databases and return an
hash containg port grouped by protocol.

Example:
{
   tcp => 1234
   udp => 1234,456:500
}

=cut
sub getPorts($)
{
    my $self = shift;
    my $id = shift;
    my %ports;
    
    if ( $id =~ m/^\d+$/ ) {
        return $id; # port 
    }
    if ( $id =~ m/^\d+(\-\d+)*$/ ) {
        return $id; # port range 
    }
    
    if ( $id =~ m/;/ ) { # lookup is needed
        my ($db, $key) = split(';', $id);
        if ( $db eq 'service' ) {
            my $service = $self->{'cdb'}->get($key);
            return %ports unless defined($service);
            if  ($service->prop('type') eq 'service') {
                my $tcpPorts = $service->prop('TCPPorts') || $service->prop('TCPPort') || '';
                my $udpPorts = $service->prop('UDPPorts') || $service->prop('UDPPort') || '';
                if ($tcpPorts ne '') {
                    ($ports{'tcp'} = $tcpPorts) =~ s/-/:/; # convert port range syntax
                }
                if ($udpPorts ne '') {
                    ($ports{'udp'} = $udpPorts) =~ s/-/:/; # convert port range syntax
                }
            } elsif ($service->prop('type') eq 'fservice') {
                if ($service->prop('Protocol') eq 'tcpudp') {
                    ($ports{'tcp'} = $service->prop('Ports')) =~ s/-/:/; # convert port range syntax
                    ($ports{'udp'} = $service->prop('Ports')) =~ s/-/:/; # convert port range syntax
                } else {
                    ($ports{$service->prop('Protocol')} = $service->prop('Ports')) =~ s/-/:/; # convert port range syntax
                }
            }
          
        }
    }

    return %ports;
}

=head2 getZone(value)

Return the given value prefixed with its own zone.
Value can be an ip address, an host group or a CIDR subnet.
This function is used to create Shorewall rules file.

Example:
   $v = $fw->getZone('192.168.1.2');
   $v will be "loc:192.168.1.2"

=cut
sub getZone($)
{
    my $self = shift;
    my $value = shift;
    my $str = $value;

    if ( $value =~ m/,/ ) { # host group, pick the first one
        my @tokens = split(/,/, $value);
        $str = $tokens[0];
    }
    my $needle = NetAddr::IP->new($str);
    return $value unless defined($needle); # skip garbage

    # check zones
    my @zones =  $self->{'ndb'}->zones;
    foreach my $z (@zones) { 
        next unless ($z->prop('Network') ne '');
        my $haystack = NetAddr::IP->new($z->prop('Network'));
        if ($needle->within($haystack)) {
            return $z->key.":$value";
        }
    }

    # check interfaces
    my @interfaces = $self->{'ndb'}->interfaces;
    foreach my $i (@interfaces) {
        my $bootproto = $i->prop('bootproto') || '';
        my $role = $i->prop('role') || '';
        next unless ($bootproto eq 'static');
        next unless ($role ne '');
        my $haystack = NetAddr::IP->new($i->prop('ipaddr'),$i->prop('netmask'));
        if ($needle->within($haystack)) {
            if ($i->prop('role') eq 'red') {
                return "net:$value";
            } elsif ($i->prop('role') eq 'green') {
                return "loc:$value";
            } else {
                return substr($i->key, 0, 5).":$value"; # truncate zone name to 5 chars
            }
        }
    }


    # best guess: we don't know anything, it should be in net zone
    return "net:$value";
}

sub _getHostAddress($)
{
    my $self = shift;
    my $key = shift;
    
    my $record = $self->{'hdb'}->get($key);
    return '' unless defined($record);
    my $ip = $record->prop('IpAddress') || '';
    return $ip unless ($ip eq ''); # IP has precedence over MAC address
    return $record->prop('MacAddress') || '';
}
 

sub _getHostGroupAddresses($)
{
    my $self = shift;
    my $key = shift;
    
    my $record = $self->{'hdb'}->get($key);
    return '' unless defined($record);
    my $members = $record->prop('Members') || '';
    my @keys = split(',', $members);
    return '' unless (@keys) ;
    my @hosts = ();
    foreach my $key (@keys) {
        push(@hosts, $self->_getHostAddress($key));  
    }
    return join (',',@hosts);
}

