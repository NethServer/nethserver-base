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
 * Manage local networks
 */
class LocalNetwork extends \Nethgui\Controller\TableController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Security', 20);
    }

    public function initialize()
    {
        $columns = array(
            'Key',
            'Mask',
            'Description',
            'Actions'
        );

        $parameterSchema = array(
            array('network', Validate::IPv4, \Nethgui\Controller\Table\Modify::KEY),
            array('Mask', Validate::IPv4, \Nethgui\Controller\Table\Modify::FIELD),
            array('Description', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
        );

        $this
            ->setTableAdapter(new LocalNetwork\NetworkAdapter($this->getPlatform()))
            ->setColumns($columns)
            ->addTableAction(new \Nethgui\Controller\Table\Modify('create', $parameterSchema, 'NethServer\Template\LocalNetwork\CreateUpdate'))            
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \Nethgui\Controller\Table\Modify('update', $parameterSchema, 'NethServer\Template\LocalNetwork\CreateUpdate'))
            ->addRowAction(new \Nethgui\Controller\Table\Modify('delete', $parameterSchema, 'Nethgui\Template\Table\Delete'))
        ;

        parent::initialize();
    }

    public function onParametersSaved(\Nethgui\Module\ModuleInterface $currentAction, $changes, $parameters)
    {
        $eventName = strtolower($currentAction->getIdentifier());
        // Update is replaced by "modify":
        if($eventName === 'update') {
            $eventName = 'modify';
        }
        $this->getPlatform()->signalEvent(sprintf('network-%s &', $eventName));
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata) {
        if (isset($values['editable']) && !$values['editable']) {
            $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' user-locked');
        }
        return strval($key);
    }

    public function prepareViewForColumnActions(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $cellView = $action->prepareViewForColumnActions($view, $key, $values, $rowMetadata);
        if (isset($values['editable']) && $values['editable'] == 0) {
            unset($cellView['delete']);
            unset($cellView['update']);
        }
        return $cellView;
   }
}

