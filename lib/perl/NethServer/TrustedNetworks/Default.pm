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

package NethServer::TrustedNetworks::Default;
use NethServer::TrustedNetworks qw(register_callback);
use esmith::DB::db;
use esmith::util;

register_callback(\&local_networks, 30);

#
# Local networks are ethernet records with role=green and network records
# from networks DB
#
sub local_networks
{
    my $results = shift;

    my $networks_db = esmith::DB::db->open_ro('networks');
    if( ! $networks_db ) {
        return;
    }

    foreach ($networks_db->get_all_by_prop('role' => 'green')) {
        my $greenNetwork = esmith::util::computeLocalNetworkShortSpec($_->prop('ipaddr'), $_->prop('netmask'));
        if($greenNetwork) {
            push(@$results, {'cidr' => $greenNetwork, 'provider' => 'green'});
        }
    }

    foreach($networks_db->get_all_by_prop('type' => 'network')) {
        my $network = esmith::util::computeLocalNetworkShortSpec($_->key, $_->prop('Mask'));
        if($network) {
            push(@$results, {'cidr' => $network, 'provider' => 'networksdb'});
        }
    }
}


1;
