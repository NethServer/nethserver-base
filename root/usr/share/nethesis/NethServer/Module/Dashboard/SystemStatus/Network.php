<?php
namespace NethServer\Module\Dashboard\SystemStatus;

/*
 * Copyright (C) 2013 Nethesis S.r.l.
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

/**
 * Retrieve system release reading /etc/nethserver-release
 *
 * @author Giacomo Sanchietti
 */
class Network extends \Nethgui\Controller\AbstractController
{

    public $sortId = 30;
 
    private $interfaces = array();
    private $dns = "";
    private $hostname = "";
    private $domain = "";

    private function readInterfaces()
    {
        $interfaces = $this->getPlatform()->getDatabase('networks')->getAll();
        foreach ($interfaces as $interface => $props) {
             # remove non existing interfaces
             if (!file_exists("/sys/class/net/$interface")) {
                 unset($interfaces[$interface]);
                 continue;
             }
             $tmp = array(
                 'name' => $interface,
                 'ipaddr'=> isset($props['ipaddr'])?$props['ipaddr']:'-', 
                 'netmask'=> isset($props['netmask'])?$props['netmask']:"-", 
                 'gateway'=> isset($props['gateway'])?$props['gateway']:"-", 
                 'hwaddr'=> isset($props['hwaddr'])?$props['hwaddr']:"-", 
                 'bootproto'=> isset($props['bootproto'])?$props['bootproto']:"-", 
                 'role'=> isset($props['role'])?$props['role']:"-" 
             );
             $tmp['speed'] = file_get_contents("/sys/class/net/".$interface."/speed")." Mb/s";
             $tmp['link'] = file_get_contents("/sys/class/net/".$interface."/carrier");
             $interfaces[$interface] = $tmp;
        }
        return $interfaces;
    }

    private function readDNS()
    {
        $dns = $this->getPlatform()->getDatabase('configuration')->getKey('dns');
        if ($dns['role'] == 'none') { //dnsmasq not installed
            return $dns['NameServers'];
        } else {
            return "127.0.0.1";
        }
    }

    private function readHostname()
    {
        return $this->getPlatform()->getDatabase('configuration')->getType('SystemName');
    }

    private function readDomain()
    {
        return $this->getPlatform()->getDatabase('configuration')->getType('DomainName');
    }

    public function process()
    {
        $this->interfaces = $this->readInterfaces();
        $this->dns = $this->readDNS();
        $this->hostname = $this->readHostname();
        $this->domain = $this->readDomain();
    }
 
    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if (!$this->hostname) {
            $this->hostname = $this->readHostname();
        }
        $view['hostname'] = $this->hostname;

        if (!$this->domain) {
            $this->domain = $this->readDomain();
        }
        $view['domain'] = $this->domain;

        if (!$this->dns) {
            $this->dns = $this->readDNS();
        }
        $view['dns'] = $this->dns;

        if (!$this->interfaces) {
            $this->interfaces = $this->readInterfaces();
        }
        $ifaces = array();
        $view['gateway'] = "-";
        foreach ($this->interfaces as $i=>$props) {
            if ( $this->interfaces[$i]['role'] == 'green') {
                $view['gateway'] = $this->interfaces[$i]['gateway'];
            }
        }
        $view['interfaces'] = $this->interfaces;
    }
}
