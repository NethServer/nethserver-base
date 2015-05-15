<?php
namespace NethServer\Module\LocalNetwork;

/*
 * Copyright (C) 2015 Nethesis S.r.l.
 * 
 * This script is part of NethServer.
 * 
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

use Nethgui\System\PlatformInterface as Validate;

/**
 * Edit LocalNetwork
 *
 * @author Giacomo Sanchietti
 *
 */
class Modify extends \Nethgui\Controller\Table\Modify
{

    public function initialize()
    {
        $columns = array(       
            'Key',
            'Mask',
            'Description',
            'Actions'
        );

        $parameterSchema = array(
            array('network', Validate::IPv4, \Nethgui\Controller\Table\Modify::KEY),
            array('Mask', Validate::IPv4, \Nethgui\Controller\Table\Modify::FIELD),
            array('Description', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
        );

        $this->setSchema($parameterSchema);

        parent::initialize();
    }

    private function maskToCidr($mask){
        $long = ip2long($mask);
        $base = ip2long('255.255.255.255');
        return 32-log(($long ^ $base)+1,2);
    }

    private function ipInRange( $ip, $range ) {
        if ( strpos( $range, '/' ) == false ) {
                $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        list( $range, $netmask ) = explode( '/', $range, 2 );
        $range_decimal = ip2long( $range );
        $ip_decimal = ip2long( $ip );
        $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
    }


    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        if( $this->getRequest()->isMutation()) {
            $net = long2ip(ip2long($this->parameters['network']) & ip2long($this->parameters['Mask']));
            if ($net != $this->parameters['network']) {
                $report->addValidationErrorMessage($this, 'network', 'invalid_network', array($this->parameters['network']));
            }

            // check the network is not already used
            $interfaces = $this->getPlatform()->getDatabase('networks')->getAll();
            foreach ($interfaces as $interface => $props) {
                if(isset($props['role']) && isset($props['ipaddr']) ) {
                    $cidr = $this->parameters['network']."/".$this->maskToCidr($this->parameters['Mask']);
                    if ($this->ipInRange($props['ipaddr'], $cidr)) {
                        $report->addValidationErrorMessage($this, 'network', 'used_network', array($this->parameters['network']));
                    }
                }
            }

        $openvpn = $this->getPlatform()->getDatabase('configuration')->getKey('openvpn');
        if (isset($openvpn['Network']) && $openvpn['Network'] == $this->parameters['network']) {
            $report->addValidationErrorMessage($this, 'network', 'used_network', array($this->parameters['network']));
        }

        $ipsec = $this->getPlatform()->getDatabase('configuration')->getKey('ipsec');
        if (isset($ipsec['L2tpNetwork']) && $ipsec['L2tpNetwork'] == $this->parameters['network']) {
            $report->addValidationErrorMessage($this, 'network', 'used_network', array($this->parameters['network']));
        }

        }
    }


}
