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
class ReleasePhysicalInterface extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('device', FALSE);
        $this->declareParameter('parent', FALSE);
        $this->declareParameter('role', FALSE);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $keyValue = \Nethgui\array_head($request->getPath());
        $adapter = $this->getParent()->getAdapter();
        if ( ! isset($adapter[$keyValue])) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399456808);
        }
        $props = $adapter[$keyValue];
        if ($props['type'] !== 'ethernet') {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399456809);
        }

        parent::bind($request);        
        if ($props['role'] === 'slave') {
            $parent = isset($props['master']) ? $props['master'] : '';
        } else {
            $parent = isset($props['bridge']) ? $props['bridge'] : '';
        }
        $this->parameters['parent'] = $parent;
        $this->parameters['role'] = $props['role'];
        $this->parameters['device'] = $keyValue;
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $ndb = $this->getPlatform()->getDatabase('networks');
            $ndb->delProp($this->parameters['device'], array('master', 'bridge', 'bootproto', 'ipaddr', 'netmask', 'gateway', 'vlan'));
            $ndb->setProp($this->parameters['device'], array('role' => ''));
            if($this->parameters['role'] === 'pppoe') {
                $ndb->setType('ppp0', 'xdsl-disabled');
            }
            $this->getAdapter()->flush();
            $this->getPlatform()->signalEvent('interface-update &');
        }
    }

    private function getMessageText(\Nethgui\View\ViewInterface $view)
    {
        if (in_array($this->parameters['role'], array('bridged', 'slave', 'pppoe'))) {
            $msgTemplate = sprintf("ReleasePhysicalInterface_%s_message", $this->parameters['role']);
        } else {
            $msgTemplate = "ReleasePhysicalInterface_roled_message";
        }
        return $view->translate($msgTemplate, \iterator_to_array($this->parameters));
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['message'] = $this->getMessageText($view);
    }

}
