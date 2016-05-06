<?php

namespace NethServer\Module\NetworkAdapter;

/*
 * Copyright (C) 2015 Nethesis Srl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Nethgui\System\PlatformInterface as Validate;

/**
 * Description of SetPppoeParameters
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class SetPppoeParameters extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();

        $adapter = $this->getPlatform()->getMapAdapter(array($this, 'readInterface'), array($this, 'writeInterface'), array());

        $this->declareParameter('PppoeUser', Validate::NOTEMPTY, array('networks', 'ppp0', 'user'));
        $this->declareParameter('PppoeProvider', Validate::NOTEMPTY, array('networks', 'ppp0', 'provider'));
        $this->declareParameter('PppoePassword', Validate::NOTEMPTY, array('networks', 'ppp0', 'Password'));
        $this->declareParameter('PppoeInterface', $this->createValidator()->memberOf($this->getValidInterfaces()), $adapter);
        $this->declareParameter('PppoeAuthType', $this->createValidator()->memberOf('auto', 'pap', 'chap'), array('networks', 'ppp0', 'AuthType'));
    }

    public function writeInterface($eth)
    {
        $changed = FALSE;
        $ndb = $this->getPlatform()->getDatabase('networks');
        foreach ($this->getParent()->getAdapter() as $key => $props) {
            if ($props['type'] !== 'ethernet') {
                continue;
            }
            $role = isset($props['role']) ? $props['role'] : '';

            if ($key === $eth && $role === '') {
                $ndb->setProp($key, array('role' => 'pppoe'));
                $ndb->setType('ppp0', 'xdsl');
                $changed = TRUE;
            }
            if ($key !== $eth && $role === 'pppoe') {
                $ndb->setProp($key, array('role' => ''));
                $changed = TRUE;
            }
        }
        return $changed;
    }

    public function readInterface()
    {
        foreach ($this->getParent()->getAdapter() as $key => $props) {
            if (isset($props['role']) && $props['role'] === 'pppoe') {
                return $key;
            }
        }
        return NULL;
    }

    private function getValidInterfaces()
    {
        $parts = array();
        $curr = $this->readInterface();
        foreach ($this->getParent()->getAdapter() as $key => $props) {
            $isFreeEthernet = ( ! isset($props['role']) || $props['role'] === '');
            $isCurrentPppoe = $curr === $key;
            if ($props['type'] === 'ethernet' && ($isFreeEthernet || $isCurrentPppoe)) {
                $parts[] = $key;
            }
        }
        return $parts;
    }

    public function process()
    {
        parent::process();
        $ndb = $this->getPlatform()->getDatabase('networks');
    }

    protected function onParametersSaved($changedParameters)
    {
        $this->getPlatform()->signalEvent('interface-update &');
        $this->getAdapter()->flush();
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);

        if ($request->isMutation()) {
            $this->parameters['PppoeUser'] = trim($request->getParameter('PppoeUser'));
            $this->parameters['PppoeProvider'] = trim($request->getParameter('PppoeProvider'));
            $this->parameters['PppoePassword']= trim($request->getParameter('PppoePassword'));
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        $parent = $this->getParent();
        $dsMap = function($x) use ($parent, $view) {
            return array($x, sprintf("%s %s", $x, call_user_func(array($parent, 'getRoleText'), $view, $x)));
        };
        parent::prepareView($view);
        $view['PppoeInterfaceDatasource'] = array_map($dsMap, $this->getValidInterfaces());
    }

}
