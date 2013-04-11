<?php
namespace NethServer\Module;

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

/**
 * Configure network adapters
 */
class NetworkAdapter extends \Nethgui\Controller\TableController
{
    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Configuration', 20);
    }

    public function initialize()
    {

        //FIXME
        $this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/update-networks-db'); 

        $columns = array(
            'Key',
            'hwaddr',
            'role',
            'bootproto',
            'ipaddr',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('networks',NULL))
            #->setTableAdapter(new \NethServer\Module\NetworkAdapter\NetworkTableAdapter($this->getPlatform()))
            ->setColumns($columns)
            #->addTableAction(new \NethServer\Module\NetworkAdapter\Modify('create'))            
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \NethServer\Module\NetworkAdapter\Modify('update'))
            ->addRowAction(new \NethServer\Module\NetworkAdapter\Modify('delete'))
        ;

        parent::initialize();
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if (!isset($values['role']) || !$values['role']) {
            $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' user-locked');
        }
        return strval($key);
    }
        
}

