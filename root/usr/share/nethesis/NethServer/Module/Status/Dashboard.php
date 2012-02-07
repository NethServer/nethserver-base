<?php
namespace NethServer\Module\Status;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
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
 * Dashboard
 *
 * @author Giovanni Bezicheri <giovanni.bezicheri@nethesis.it>
 */
class Dashboard extends \Nethgui\Controller\AbstractController
{

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        if ($view->getTargetFormat() === $view::TARGET_XHTML) {

            // init e-smith configuration db
            $db = $this->getPlatform()->getDatabase('configuration');

            $hostname = $db->getType('SystemName');
            $domain = $db->getType('DomainName');
            $ip = $db->getType('LocalIP');

            // get services
            $services = array('smb' => array(),
                'backup' => array(),
                'backupwk' => array(),
                'dhcpd' => array(),
                'dnsmasq' => array(),
                'httpd' => array(),
                'httpd-admin' => array(),
                'ldap' => array(),
                'nmb' => array(),
                'sshd' => array());

            foreach ($services as $srv => $cnt) {
                $retbuf = $db->getKey($srv);
                $cmdres = $this->getPlatform()->exec('/sbin/chkconfig ' . $srv);
                $retbuf['enabled'] = ($cmdres->getExitCode() == 0);
                $cmdres = $this->getPlatform()->exec('/bin/ps -C ' . $srv);
                $retbuf['running'] = ($cmdres->getExitCode() == 0);
                $services[$srv] = $retbuf;
            }

            // get interfaces
            $nics = array('InternalInterface' => array('title' => 'Interna'),
                'ExternalInterface' => array('title' => 'Esterna'));


            $nics['InternalInterface']['ip'] = $db->getType('LocalIP');
            $nics['ExternalInterface']['ip'] = $db->getProp('ExternalInterface', 'IPAddress');


            // get users
            // init e-smith accounts db
            $db = $this->getPlatform()->getDatabase('accounts');
            $users = $db->getAll('user');
            $users_stats = array('active' => 0, 'blocked' => 0);
            foreach ($users as $user) {
                if (array_key_exists('PasswordSet', $user) && $user['PasswordSet'] == 'yes'
                    && ( ! array_key_exists('Locked', $user) || $user['Locked'] == 'no'))
                    $users_stats['active'] ++;
                else
                    $users_stats['blocked'] ++;
            }


            // assign values to controller params
            $view['hostname'] = $hostname;
            $view['domain'] = $domain;
            $view['ip'] = $ip;
            $view['services'] = $services;
            $view['nics'] = $nics;
            $view['users_stats'] = $users_stats;
        }


        if ($this->getRequest()->isEmpty() || $view->getTargetFormat() !== $view::TARGET_JSON) {
            return;
        }

        $action = \Nethgui\array_head($request->getPath());
        if (method_exists($this, $action)) {
            call_user_func(array($this, $action), $view);
        }
    }

    public function getNetworkTraffic(\Nethgui\View\ViewInterface $view)
    {
        // NICs

        $cmdres = $this->getPlatform()->exec('cat /proc/net/dev');
        $lines = $cmdres->getOutputArray();
        $netstats = array();
        foreach ($lines as $line) {
            if (preg_match('/^\s*(?<iface>\w+):'
                    . '\s*(?<rx_bytes>\d+)'
                    . '\s+(?<rx_packets>\d+)'
                    . '\s+(?<rx_errs>\d+)'
                    . '\s+(?<rx_drop>\d+)'
                    . '\s+(?<rx_fifo>\d+)'
                    . '\s+(?<rx_frame>\d+)'
                    . '\s+(?<rx_compressed>\d+)'
                    . '\s+(?<rx_multicast>\d+)'
                    . '\s+(?<tx_bytes>\d+)'
                    . '\s+(?<tx_packets>\d+)'
                    . '\s+(?<tx_errs>\d+)'
                    . '\s+(?<tx_drop>\d+)'
                    . '\s+(?<tx_fifo>\d+)'
                    . '\s+(?<tx_colls>\d+)'
                    . '\s+(?<tx_carrier>\d+)'
                    . '\s+(?<tx_compressed>\d+).*$/', $line, $matches)) {

                $netstats[$matches['iface']] = $matches;
            }
        }

        $view['response'] = array('netstats' => $netstats);
    }

    public function getActiveAlerts(\Nethgui\View\ViewInterface $view)
    {
        try {
            $client = new SoapClient("http://c4.nethesis.it/soap/register.wsdl",
                    array('exceptions' => true,
                        'trace' => 0)); //FIXME: c4 -> register.nethesis.it

            $db = $this->getPlatform()->getDatabase('configuration');
            $lk = $db->getProp('nethupdate', 'SystemID');

            $alerts = $client->getActiveAlerts($lk);

            $view['response'] = array('alerts' => $alerts);
        } catch (Exception $e) {
            error_log(time() . ': ' . $e->getMessage());
        }
    }

}
