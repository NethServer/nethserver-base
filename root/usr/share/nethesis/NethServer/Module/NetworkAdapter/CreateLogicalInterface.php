<?php

namespace NethServer\Module\NetworkAdapter;

/*
 * Copyright (C) 2014  Nethesis S.r.l.
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
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class CreateLogicalInterface extends \Nethgui\Controller\Table\AbstractAction
{
    private $sessionKey;

    public function initialize()
    {
        parent::initialize();

        $this->sessionKey = get_class($this->getParent());

        $this->declareParameter('role', $this->createValidator()->memberOf($this->getParent()->getInterfaceRoles()), array('SESSION', $this->sessionKey, 'role'));
        $this->declareParameter('type', $this->createValidator()->memberOf($this->getParent()->getNetworkAdapterTypes()), array('SESSION', $this->sessionKey, 'type'));
        $this->declareParameter('bridge', $this->getMemberOfOrEmptyValidator($this->getBridgeParts()), array('SESSION', $this->sessionKey, 'bridge', ','));
        $this->declareParameter('bond', $this->getMemberOfOrEmptyValidator($this->getBondParts()), array('SESSION', $this->sessionKey, 'bond', ','));
        $this->declareParameter('vlan', $this->createValidator()->memberOf($this->getBondParts()), array('SESSION', $this->sessionKey, 'vlan'));

        $this->declareParameter('vlanTag', $this->createValidator(\Nethgui\System\PlatformInterface::POSITIVE_INTEGER), array('SESSION', $this->sessionKey, 'vlanTag'));
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        if ( ! $request->isMutation() && $this->getPlatform()->getDatabase('SESSION')->getKey($this->sessionKey)) {
            // Clear the session key when starting a new wizard
            $this->getPlatform()->getDatabase('SESSION')->deleteKey($this->sessionKey);
        }
        parent::bind($request);
    }

    private function getMemberOfOrEmptyValidator($memberOf)
    {
        return $this->createValidator()->orValidator(
                $this->createValidator()->collectionValidator($this->createValidator()->memberOf($memberOf)), $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING)
        );
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        if ($this->parameters['type'] == 'bond') {
            $this->getValidator('bond')->notEmpty();
        }
        if ($this->parameters['type'] == 'bridge') {
            $this->getValidator('bridge')->notEmpty();
        }
        parent::validate($report);
    }

    private function getBondParts()
    {
        $parts = array();
        foreach ($this->getAdapter() as $key => $props) {
            if ($props['type'] == 'ethernet') {
                $parts[] = $key;
            }
        }
        return $parts;
    }

    private function getBridgeParts()
    {
        $parts = array();
        foreach ($this->getAdapter() as $key => $props) {
            if ($props['type'] != 'bridge' && $props['type'] != 'alias') {
                $parts[] = $key;
            }
        }
        return $parts;
    }

    private function getRoleIpSettings($role)
    {
        $defaultBootproto = $role === 'red' ? 'dhcp' : 'none';

        foreach ($this->getAdapter() as $key => $row) {
            if (isset($row['role']) && $row['role'] == $role) {
                return array(
                    'bootproto' => $defaultBootproto,
                    'ipaddr' => isset($row['ipaddr']) ? $row['ipaddr'] : '',
                    'netmask' => isset($row['netmask']) ? $row['netmask'] : '',
                    'gateway' => isset($row['gateway']) ? $row['gateway'] : ''
                );
            }
        }

        return array('bootproto' => $defaultBootproto, 'ipaddr' => '', 'netmask' => '', 'gateway' => '');
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->getDatabase('SESSION')->setProp($this->sessionKey, $this->getRoleIpSettings($this->parameters['role']));
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        if ($this->getRequest()->isValidated()) {
            $view['roleDatasource'] = array_map(function($fmt) use ($view) {
                return array($fmt, $view->translate($fmt . '_label'));
            }, $this->getParent()->getInterfaceRoles());

            $parent = $this->getParent();
            $dsMap = function($x) use ($parent, $view) {
                return array($x, sprintf("%s %s", $x, call_user_func(array($parent, 'getRoleText'), $view, $x)));
            };

            $view['bondDatasource'] = array_map($dsMap, $this->getBondParts());
            $view['bridgeDatasource'] = array_map($dsMap, $this->getBridgeParts());

            if ($this->getRequest()->isMutation()) {
                $view->getCommandList()->sendQuery($view->getModuleUrl('../SetIpAddress'));
            } else {
                $view->getCommandList()->show();
            }
        }

        if ( ! $view['type']) {
            $view['type'] = 'bond';
        }
    }

    public function nextPath()
    {
        return FALSE;
    }

}
