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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Manage access to httpd-admin service.
 * Also show local networks (access is always granted to these networks).
 */
class HttpdAdmin extends \Nethgui\Controller\TableController
{

    public function initialize()
    {
        $columns = array(
            'Key',
            '0',
            'Actions',
        );
       
        $parameterSchema = array(
            array('address', Validate::IPv4, \Nethgui\Controller\Table\Modify::KEY),
            array('mask', Validate::IPv4_NETMASK, \Nethgui\Controller\Table\Modify::FIELD, '0'), // map 'mask' parameter to '0' column
        );
        $rw_table = $this->getPlatform()->getTableAdapter('configuration', 'httpd-admin', 'ValidFrom', array(',', '/'));
        $ro_table = $this->getPlatform()->getTableAdapter('networks','network');

        $this
            ->setTableAdapter(new \NethServer\Module\RemoteAccess\HttpdAdmin\TableMergeDecorator($rw_table, $ro_table))
            ->setColumns($columns)
            ->addTableAction(new \Nethgui\Controller\Table\Modify('create', $parameterSchema, 'NethServer\Template\RemoteAccess\HttpdAdmin'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \Nethgui\Controller\Table\Modify('delete', $parameterSchema, 'Nethgui\Template\Table\Delete'))
        ;             
        
        parent::initialize();
    }

    /**
     * Override prepareViewForColumnActions to hide/show enable/disable actions
     * @param \Nethgui\View\ViewInterface $view
     * @param string $key The data row key
     * @param array $values The data row values
     * @return \Nethgui\View\ViewInterface 
     */
    public function prepareViewForColumnActions(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $cellView = $action->prepareViewForColumnActions($view, $key, $values, $rowMetadata);

        $editable = isset($values['editable']) ? $values['editable'] : false;

        if (!$editable) {
            unset($cellView['delete']);
        }

        return $cellView;
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if (!isset($values['editable']) || (!$values['editable'])) {
            $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' user-locked');
        }
        return strval($key);
    }


    public function onParametersSaved(\Nethgui\Module\ModuleInterface $currentAction, $changes, $parameters)
    {
        // Detach event to avoid connection-lost during httpd restart:
        $this->getPlatform()->signalEvent('remoteaccess-update@post-process');
    }

}

