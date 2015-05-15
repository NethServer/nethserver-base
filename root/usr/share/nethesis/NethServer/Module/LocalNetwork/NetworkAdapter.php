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

/**
 * Manage trusted networks, display green networks
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class NetworkAdapter extends \Nethgui\Adapter\LazyLoaderAdapter
{
    /**
     * @var \Nethgui\System\PlatformInterface
     */
    private $platform;

    /**
     * @var \Nethgui\Adapter\AdapterInterface
     */
    private $innerAdapter;

    public function __construct(\Nethgui\System\PlatformInterface $p)
    {
        $this->platform = $p;
        $this->innerAdapter = $this->platform->getTableAdapter('networks', 'network');
        parent::__construct(array($this, 'readTable'));
    }

    public function isModified()
    {
        return $this->innerAdapter->isModified();
    }

    public function save()
    {
        $s = $this->innerAdapter->save();
        if ($s) {
            $this->lazyInitialization();
        }
        return $s;
    }

    public function offsetSet($offset, $value)
    {
        return $this->innerAdapter->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->innerAdapter->offsetUnset($offset);
    }

    private function addrToNet($ip, $mask) {
        $ip = ip2long($ip);
        $mask = ip2long($mask);
        return long2ip($ip & $mask);
    }

    public function readTable()
    {
        $networks = iterator_to_array($this->innerAdapter);

        $interfaces = $this->platform->getDatabase('networks')->getAll();
        foreach ($interfaces as $k => $props) {
            if (isset($props['role']) && $props['role'] == 'green' && isset($props['ipaddr']) ) {
                $net = $this->addrToNet($props['ipaddr'], $props['netmask']);
                $networks[$net] = array(
                    'network' => $net,
                    'Mask' => $props['netmask'],
                    'Description' => "Green: $k",
                    'editable' => 0
                );
            }
        }

        $openvpn = $this->platform->getDatabase('configuration')->getKey('openvpn');
        if (isset($openvpn['Network'])) {
           $networks[$openvpn['Network']] = array(
                    'network' => $openvpn['Network'],
                    'Mask' => $openvpn['Netmask'],
                    'Description' => "OpenVPN",
                    'editable' => 0
                );

        }

        $ipsec = $this->platform->getDatabase('configuration')->getKey('ipsec');
        if (isset($ipsec['L2tpNetwork'])) {
           $networks[$ipsec['L2tpNetwork']] = array(
                    'network' => $ipsec['L2tpNetwork'],
                    'Mask' => $ipsec['L2tpNetmask'],
                    'Description' => "L2TP",
                    'editable' => 0
                );

        }

        return new \ArrayObject($networks);
    }

}
