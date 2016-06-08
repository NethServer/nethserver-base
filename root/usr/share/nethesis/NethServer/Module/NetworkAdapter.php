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
    private $types = array('ethernet', 'bridge', 'bond', 'vlan', 'alias', 'xdsl');

    private $nicInfo;
    private $providers;

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
        $interfaces = $this->getPlatform()->getDatabase('configuration')->getProp('firewall', 'InterfaceRoleList');
        return explode(',',$interfaces);
    }

    private function getProviderNames()
    {
        if (!$this->providers) {
            $tmp = $this->getPlatform()->getDatabase('networks')->getAll('provider');
            foreach ($tmp as $name => $props) {
                $this->providers[$props['interface']] = $name;
            }
        }
        return $this->providers;
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
            ->addChild(new \NethServer\Module\NetworkAdapter\RenameInterface())
            ->addChild(new \NethServer\Module\NetworkAdapter\ConfirmInterfaceCreation())
            ->addTableAction(new \NethServer\Module\NetworkAdapter\UpstreamProxy())

            // Row actions
            ->addRowAction(new \NethServer\Module\NetworkAdapter\Edit())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\SetPppoeParameters())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\DeleteLogicalInterface())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\ReleasePhysicalInterface())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\CleanPhysicalInterface())
            ->addRowAction(new \NethServer\Module\NetworkAdapter\CreateIpAlias())
            ->addTableAction(new \Nethgui\Controller\Table\Help())
        ;


        parent::initialize();
    }

    public function prepareViewForColumnHwaddr(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        
        if(!$this->nicInfo) {
            $this->nicInfo = $this->getNicInfo();
        }
        return strval(isset($this->nicInfo[$key]) ? $this->nicInfo[$key] : 'n/a');
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if(!$this->nicInfo) {
            $this->nicInfo = $this->getNicInfo();
        }
        $isPresent = isset($this->nicInfo[$key]);
        $isLogicalDevice = in_array($values['type'], array('alias', 'bridge', 'bond', 'vlan', 'xdsl'));

        if ( ! $isPresent && ! $isLogicalDevice) {
            $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' user-locked');
        } elseif ( ! isset($values['role']) || ! $values['role']) {
            $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' free');
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
        } elseif ($values['role'] === 'red') {
           $providers = $this->getProviderNames();
           return $roleLabel ." - ".$providers[$key];
        }

        return $roleLabel;
    }

    public function prepareViewForColumnIpaddr(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if(isset($values['bootproto']) && $values['bootproto'] === 'dhcp') {
            $ipaddr = $this->getPlatform()->exec("/sbin/ip -o -4 address show $key primary | head -1 | awk '{print \$4}' | cut -d '/' -f1")->getOutput();
            return "$ipaddr (DHCP)";
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
        if(!$this->nicInfo) {
            $this->nicInfo = $this->getNicInfo();
        }

        $role = isset($values['role']) ? $values['role'] : '';

        $isPresent = isset($this->nicInfo[$key]);
        $isLogicalDevice = in_array($values['type'], array('alias', 'bridge', 'bond', 'vlan', 'xdsl'));
        $isPhysicalInterface = in_array($values['type'], array('ethernet'));
        $isEditable = ! in_array($values['type'], array('alias', 'xdsl')) && ! in_array($role, array('slave', 'bridged', 'pppoe'));
        $canHaveIpAlias = (isset($values['role']) && $values['role']) && $values['type'] !== 'alias' && ! in_array($role, array('slave', 'bridged', 'pppoe'));
        $isXdsl = $values['type'] === 'xdsl';

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

        if ($isPresent || $isLogicalDevice) {
            unset($cellView['CleanPhysicalInterface']);
        } else {
            unset($cellView['Edit']);
        }

        if ($isXdsl ) {
            unset($cellView['CleanPhysicalInterface']);
        } else {
            unset($cellView['SetPppoeParameters']);
        }

        return $cellView;
    }

    private function getNicInfo()
    {
        static $info;
        if (isset($info)) {
            return $info;
        }

        $data = $this->getPlatform()->exec('/usr/libexec/nethserver/nic-info')->getOutputArray();
        $info = array();
        foreach($data as $line) {
            $values = str_getcsv($line);
            $info[$values[0]] = $values[1];
        }
        return $info;
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

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if($this->getRequest()->hasParameter('renameSuccess')) {
            $view->getCommandList('read')->show();
        }
    }
}
