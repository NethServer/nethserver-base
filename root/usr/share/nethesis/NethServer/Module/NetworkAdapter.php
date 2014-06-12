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

/**
 * Configure network adapters
 */
class NetworkAdapter extends \Nethgui\Controller\TableController
{
    /**
     *
     * @var array
     */
    private $types = array('ethernet', 'bridge', 'bond', 'vlan', 'alias');

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Configuration', 14);
    }

    public function getNetworkAdapterTypes()
    {
        return $this->types;
    }

    public function getInterfaceRoles()
    {
        $event = $this->getPlatform()->getDatabase('configuration')->getProp('firewall', 'event');
        if ($event == 'lokkit-save') {
            return array('green');
        } else {
            return array('green', 'red', 'blue', 'orange');
        }
    }

    public function initialize()
    {
        $columns = array(
            'Key',
            'hwaddr',
            'role',
            'ipaddr',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('networks', $this->getNetworkAdapterTypes()))
            ->setColumns($columns)
            // Creation of logical interface wizard
            ->addChild(new \NethServer\Module\NetworkAdapter\SetIpAddress())
            ->addTableAction(new \NethServer\Module\NetworkAdapter\CreateLogicalInterface())
            ->addChild(new \NethServer\Module\NetworkAdapter\ConfirmInterfaceCreation())

            // Row actions
            ->addRowAction(new \NethServer\Module\NetworkAdapter\Edit())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\DeleteLogicalInterface())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\ReleasePhysicalInterface())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\CreateIpAlias())
            ->addTableAction(new \Nethgui\Controller\Table\Help())
        ;


        parent::initialize();
    }

    public function prepareViewForColumnDescription(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        return strval(isset($values['hwaddr']) ? $values['hwaddr'] : 'n/a');
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if ( ! isset($values['role']) || ! $values['role']) {
            $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' user-locked');
        }
        return strval($key);
    }

    public function getRoleText(\Nethgui\View\ViewInterface $view, $key, $values = NULL)
    {
        if (is_null($values)) {
            $values = $this->getAdapter()->offsetGet($key);
        }
        if (!isset($values['role']) || !$values['role']) {
            return '';
        }
        $roleLabel = $view->translate($values['role'] . "_label");

        if ($values['role'] === 'slave') {
            return $roleLabel . " (" . $values['master'] . ")";
        } elseif ($values['role'] === 'bridged') {
            return $roleLabel . " (" . $values['bridge'] . ")";
        }

        return $roleLabel;
    }

    public function prepareViewForColumnIpaddr(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if(isset($values['bootproto']) && $values['bootproto'] === 'dhcp') {
            return 'DHCP';
        }
        return strval(isset($values['ipaddr']) ? $values['ipaddr'] : '');
    }

    public function prepareViewForColumnRole(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $role = isset($values['role']) ? $values['role'] : '';
        $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' ' . $role);
        return $this->getRoleText($view, $key, $values);
    }

    /**
     * Override prepareViewForColumnActions to hide/show delete action
     * @param \Nethgui\View\ViewInterface $view
     * @param string $key The data row key
     * @param array $values The data row values
     * @return \Nethgui\View\ViewInterface
     */
    public function prepareViewForColumnActions(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $cellView = $action->prepareViewForColumnActions($view, $key, $values, $rowMetadata);

        $role = isset($values['role']) ? $values['role'] : '';

        $isLogicalDevice = in_array($values['type'], array('alias', 'bridge', 'bond', 'vlan'));
        $isPhysicalInterface = in_array($values['type'], array('ethernet'));
        $isEditable = $values['type'] !== 'alias' && ! in_array($role, array('slave', 'bridged'));
        $canHaveIpAlias = $values['type'] !== 'alias' && ! in_array($role, array('slave', 'bridged'));

        if ( ! $isLogicalDevice) {
            unset($cellView['DeleteLogicalInterface']);
        }

        if ( ! $isPhysicalInterface || $role === '') {
            unset($cellView['ReleasePhysicalInterface']);
        }

        if ( ! $isEditable) {
            unset($cellView['Edit']);
        }

        if ( ! $canHaveIpAlias) {
            unset($cellView['CreateIpAlias']);
        }

        return $cellView;
    }

    public function getDeviceParts($device)
    {
        if ( ! isset($device) || ! $this->getAdapter()->offsetExists($device)) {
            return array();
        }

        $parts = array();

        $type = $this->getAdapter()->offsetGet($device)->offsetGet('type');

        if ($type === 'bond') {
            $link = 'master';
        } elseif ($type === 'bridge') {
            $link = 'bridge';
        } else {
            return array();
        }

        foreach ($this->getAdapter() as $key => $props) {
            $roleMatch = isset($props['role']) && (($props['role'] === 'bridged' && $type === 'bridge') || ($props['role'] === 'slave' && $type === 'bond'));
            if ($roleMatch && isset($props[$link]) && $props[$link] === $device) {
                $parts[] = $key;
            }
        }

        return $parts;
    }

    public function hasSiblings($device)
    {
        $props = $this->getAdapter()->offsetGet($device);
        if ( ! isset($props)) {
            return FALSE;
        }
        if ($props['role'] === 'slave') {
            $hasSiblings = count($this->getDeviceParts($props['master'])) > 1;
        } elseif ($props['role'] === 'bridged') {
            $hasSiblings = count($this->getDeviceParts($props['bridge'])) > 1;
        } else {
            $hasSiblings = FALSE;
        }

        return $hasSiblings;
    }

    public function hasParent($device)
    {
        $A = $this->getAdapter();
        if( ! isset($A[$device])) {
            return FALSE;
        }
        return in_array($A[$device]['role'], array('slave', 'bridged'));
    }
    
}
