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

    private function readNetStats()
    {
        $ret = array();
        $stats = file("/proc/net/dev");
        $stats = array_slice($stats,2); #remove headers
        foreach ($stats as $line) {
             $tmp = explode(":",trim($line)); # get device name
             $values = preg_split("/\s+|:/",trim($tmp[1])); # get values
             $ret[$tmp[0]] = array(
                 'RX_bytes' => $values[1], 'RX_pkts' => $values[0], 'RX_errs' => $values[2], 'RX_drops' => $values[3],
                 'TX_bytes' => $values[8], 'TX_pkts' => $values[9], 'TX_errs' => $values[10], 'TX_drops' => $values[11]
             );
             $ret[$tmp[0]] = array_map(function($v) { return intval($v); }, $ret[$tmp[0]]);
        }
        return $ret;
    }

    private function readInterfaces()
    {
        $stats = $this->readNetStats();
        $interfaces = $this->getPlatform()->getDatabase('networks')->getAll('ethernet');
        foreach ($interfaces as $interface => $props) {
             $tmp = array(
                 'name' => $props['device'],
                 'ipaddr'=> $props['ipaddr'], 
                 'netmask'=> $props['netmask'], 
                 'gateway'=>$props['gateway'], 
                 'hwaddr'=> $props['hwaddr'], 
                 'bootproto'=> $props['bootproto'], 
             );
             $tmp['speed'] = file_get_contents("/sys/class/net/".$interface."/speed")." Mb/s";
             $tmp['stats'] = $stats[$interface];
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
        foreach ($this->interfaces as $i=>$props) {
            $tmp = array();
            foreach ($props as $k=>$v) {
              $tmp[] = array($k,$v);
            }
            $ifaces[] = $tmp;
        }
        $view['interfaces'] = $ifaces;
        if (isset($this->interfaces['green'])) {
            $view['gateway'] = $this->interfaces['green']['gateway'];
        } else {
            $view['gateway'] = "";
        }
    }
}
