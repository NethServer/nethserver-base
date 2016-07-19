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

    private function mask2cidr($mask){
        $long = ip2long($mask);
        $base = ip2long('255.255.255.255');
        return 32-log(($long ^ $base)+1,2);
    }

    private function readInterfaces()
    {
        $interfaces = $this->getPlatform()->getDatabase('networks')->getAll();
        $tmp = $this->getPlatform()->exec('/usr/bin/sudo -n /usr/libexec/nethserver/nic-info')->getOutputArray();
        foreach ($tmp as $line) {
            $data = explode(',',$line);
            $interfaces[$data[0]]['speed'] = $data[5];
            $interfaces[$data[0]]['link'] = $data[6];
            $ipaddr = $this->getPlatform()->exec("/sbin/ip -o -4 address show ".$data[0]." primary | head -1 | awk '{print \$4}'")->getOutput();
            $interfaces[$data[0]]['ipaddr'] = $ipaddr;
        }
        foreach ($interfaces as $interface => $props) {
             if ($props['type'] == 'network' || $props['type'] == 'xdsl-disabled' || $props['type'] == 'provider' || $props['type'] == 'zone') {
                 unset($interfaces[$interface]);
                 continue;
             }
             $ipaddr = '';
             if (strpos($interface,'ppp') !== false) {
                $ipaddr = $this->getPlatform()->exec("/sbin/ip -o -4 address show $interface primary | head -1 | awk '{print \$4}'")->getOutput();
             } else if(strpos($interfaces[$interface]['ipaddr'],'/') === false) {
                 if ($props['ipaddr']) {
                     $ipaddr = $props['ipaddr']."/".$this->mask2cidr($props['netmask']);
                 }
             } else {
                 $ipaddr = $interfaces[$interface]['ipaddr'];
             }
             $tmp = array(
                 'name' => $interface,
                 'ipaddr' => $ipaddr,
                 'gateway'=> isset($props['gateway'])?$props['gateway']:"",
                 'hwaddr'=> isset($props['hwaddr'])?$props['hwaddr']:"",
                 'bootproto'=> isset($props['bootproto'])?$props['bootproto']:"",
                 'role'=> isset($props['role'])?$props['role']:"",
                 'link'=> isset($props['link'])?$props['link']:"",
                 'speed'=> isset($props['speed'])?$props['speed']." Mb/s":""
             );
             $interfaces[$interface] = $tmp;
        }
        return $interfaces;

    }

    private function readDNS()
    {
        $dns = $this->getPlatform()->getDatabase('configuration')->getKey('dns');
        return $dns['NameServers'];
    }

    public function process()
    {
        $this->interfaces = $this->readInterfaces();
        $this->dns = $this->readDNS();
    }
 
    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        $view['hostname'] = \gethostname();

        if (!$this->dns) {
            $this->dns = $this->readDNS();
        }
        $view['dns'] = $this->dns;

        if (!$this->interfaces) {
            $this->interfaces = $this->readInterfaces();
        }
        $view['interfaces'] = $this->interfaces;
    }
}
