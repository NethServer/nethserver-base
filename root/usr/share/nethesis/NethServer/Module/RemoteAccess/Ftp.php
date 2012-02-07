<?php
namespace NethServer\Module\RemoteAccess;

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
 * @todo Describe Module class
 */
class Ftp extends \Nethgui\Controller\AbstractController
{

    public function initialize()
    {
        parent::initialize();
        $validServiceStatusList = array('disabled', 'localNetwork', 'anyNetwork');
        $this->declareParameter(
            'status', // parameter name
            $this->createValidator()->memberOf($validServiceStatusList), array(
            array('configuration', 'ftp', 'status'),
            array('configuration', 'ftp', 'LoginAccess')
            )
        );
        $this->declareParameter(
            'acceptPasswordFromAnyNetwork', // parameter name
            $this->createValidator()->memberOf('1', ''), array('configuration', 'ftp', 'access')
        );
        $this->parameters['statusOptions'] = $validServiceStatusList;
    }

    protected function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('remoteaccess-update@post-process');
    }

    /**
     * 
     * 
     * @param string $status
     * @param string $access
     * @return string
     */
    public function readStatus($status, $access)
    {
        if ($status == 'enabled') {
            if ($access == 'public') {
                return 'anyNetwork';
            } elseif ($access == 'private') {
                return 'localNetwork';
            }
        }

        return 'disabled';
    }

    /**
     * 
     *
     * @param string $value
     * @return array
     */
    public function writeStatus($value)
    {
        switch ($value) {
            case 'localNetwork':
                return array('enabled', 'private');

            case 'anyNetwork':
                return array('enabled', 'public');

            case 'disabled':
            default:
                return array('disabled', 'private');
        }
    }

    /**
     * 
     *
     * @param string $value
     * @return string
     */
    public function readAcceptPasswordFromAnyNetwork($value)
    {
        if ($value == 'public') {
            return '1';
        }

        return '';
    }

    /**
     * 
     *
     * @param string $value
     * @return array
     */
    public function writeAcceptPasswordFromAnyNetwork($value)
    {
        if ($value == '1') {
            return array('public');
        } else {
            return array('private');
        }
    }

}
