<?php
namespace NethServer\Module\NetworkServices;

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

use Nethgui\System\PlatformInterface as Validate;
use Nethgui\Controller\Table\Modify as Table;

/**
 * Service mdify action
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class Modify extends \Nethgui\Controller\Table\Modify
{
    private $zones = array();

    private function listZones()
    {
        if ($this->zones) {
            return $this->zones;
        }
        $invalid_roles = array('bridged', 'alias', 'slave', 'xdsl');
        $networks = $this->getPlatform()->getDatabase('networks')->getAll();
        $this->zones['red'] = ''; # always enable red
        foreach ($networks as $key => $values) {
            if ($values['type']  == 'zone') {
               $this->zones[$key] = '';
            }
            if(isset($values['role']) && ! preg_match("/(".implode('|',$invalid_roles).")/", $values['role'])) {
               $this->zones[$values['role']] = ''; 
            }
        }
        
        $this->zones = array_keys($this->zones);
        return $this->zones;
    }

    public function initialize()
    {
        parent::initialize();
        $this->setViewTemplate('NethServer\Template\NetworkServices\Modify');


        $parameterSchema = array(
            array('name', Validate::ANYTHING, Table::KEY),
            array('status', Validate::SERVICESTATUS, Table::FIELD),
            array('access', Validate::ANYTHING, Table::FIELD, 'access', ','),
        );

        $this->setSchema($parameterSchema);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $view['accessDatasource'] = array_map(function($fmt) use ($view) {
            $label = $view->translate($fmt . '_label');
            if ($label == $fmt . '_label') {
                $label = $fmt;
            }
 
            return array($fmt, $label);
        }, $this->listZones());
    }

    protected function onParametersSaved($changedParameters)
    {
        $this->getPlatform()->signalEvent('firewall-adjust');
    }

}
