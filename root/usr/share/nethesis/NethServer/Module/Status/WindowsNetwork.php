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
 * Windows Network
 *
 * @author Giovanni Bezicheri <giovanni.bezicheri@nethesis.it>
 */

class WindowsNetwork extends \Nethgui\Controller\AbstractController
{
    public function initialize()
    {
        parent::initialize();
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

            // init e-smith configuration db
            $db = $this->getPlatform()->getDatabase('configuration');

        $smbdb = $db->getKey('smb');

            // init e-smith accounts db
            $db_accounts = $this->getPlatform()->getDatabase('accounts');

        $shfolders = $db_accounts->getAll('ibay');

            // get smbstatus
            $cmdres = $this->getPlatform()->exec('cat /tmp/smbstatus'); //TODO
            $lines = $cmdres->getOutputArray();
            
        $filter = '';
        $smb_users = array();
        $smb_services = array();
        foreach ($lines as $line) {
            if (preg_match('/^\s*(?<field1>.+?)'
                           . '\s+(?<field2>.+?)'
                           . '\s+(?<field3>.+?)'
                           . '\s+(?<field4>.+?)'
                           . '\s*$/', $line, $matches)) {

                if ($matches['field1'] == 'PID') { $filter = 'users'; continue; }
                else if ($matches['field1'] == 'Service') { $filter = 'services'; continue; }
                else if ($matches['field1'] == 'Pid') { break; }

                if ($filter == 'users') {
                    $smb_users[$matches['field2']] = array('pid' => $matches['field1'],
                                                           'username' => $matches['field2'],
                                                           'group' => $matches['field3'],
                                                           'machine' => $matches['field4']);
                }
                else if ($filter == 'services') {
                    if (!array_key_exists($matches['field2'], $smb_services))
                        $smb_services[$matches['field2']] = array();
                    $smb_services[$matches['field2']][] = array('service' => $matches['field1'],
                                                                'pid' => $matches['field2'],
                                                                'machine' => $matches['field3'],
                                                                'connected_time' => $matches['field4']);
                }
            }
        }
            
        // assign values to controller params
        $view['smbdb'] = $smbdb;
        $view['shfolders'] = $shfolders;
        $view['smb_users'] = $smb_users;
        $view['smb_services'] = $smb_services;
    }
}
