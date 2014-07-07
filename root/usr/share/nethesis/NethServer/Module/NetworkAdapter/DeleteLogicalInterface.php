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
 * Remove logical / unconfigure physical interaces
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class DeleteLogicalInterface extends \Nethgui\Controller\Table\AbstractAction
{
    private $type;

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('device', FALSE);
        $this->declareParameter('type', FALSE);
        $this->declareParameter('successor', $this->createValidator());
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $keyValue = \Nethgui\array_head($request->getPath());
        $adapter = $this->getParent()->getAdapter();
        if ( ! isset($adapter[$keyValue])) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399456905);
        }
        if ( ! in_array($adapter[$keyValue]['type'], array('bridge', 'bond', 'alias', 'vlan'))) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399456906);
        }
        parent::bind($request);
        $this->parameters['type'] = $adapter[$keyValue]['type'];
        $this->parameters['device'] = $keyValue;
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        $this->getValidator('successor')->memberOf(array_merge(array(''), $this->getDeviceParts($this->parameters['device'])));
        parent::validate($report);
    }

    /**
     * When a logical device is not itself a part of a composition
     * and is deleted, its parts become "free". Only the designed successor
     * inherits IP and role.
     *
     * @param string $partKey
     */
    private function releasePart($partKey)
    {
        $ndb = $this->getPlatform()->getDatabase('networks');
        $ndb->delProp($partKey, array('bridge', 'master', 'vlan'));
        if ($partKey === $this->parameters['successor']) {
            $props = array();
            foreach ($ndb->getKey($this->parameters['device']) as $key => $value) {
                if (in_array($key, array('role', 'bootproto', 'ipaddr', 'netmask', 'gateway'))) {
                    $props[$key] = $value;
                }
            }
            $ndb->setProp($partKey, $props);
        } else {
            $ndb->setProp($partKey, array('role' => ''));
        }
    }

    /**
     * If the logical interface is itself a part of an upper composition,
     * move child parts to its parent. No successor is considered.
     *
     * @param string $partKey
     */
    private function movePartToParent($partKey)
    {
        $ndb = $this->getPlatform()->getDatabase('networks');
        $type = $ndb->getType($this->parameters['device']);
        $props = $ndb->getKey($this->parameters['device']);

        if ($type === 'bridge' && $props['role'] != 'bridged') {
            $ndb->delProp($partKey, array('bridge'));
        } elseif ($type === 'bond' && $props['role'] != 'slave') {
            $ndb->delProp($partKey, array('bond'));
        }

        $partProps = array('role' => $props['role']);
        if ($props['role'] === 'slave') {
            $partProps['bond'] = $props['bond'];
        } elseif ($props['role'] === 'bridged') {
            $partProps['bridge'] = $props['bridge'];
        }

        $ndb->setProp($partKey, $partProps);
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            foreach ($this->getDeviceParts($this->parameters['device']) as $partKey) {
                if ($this->getParent()->hasParent($this->parameters['device'])) {
                    $this->movePartToParent($partKey);
                } else {
                    $this->releasePart($partKey);
                }
            }
            $this->getPlatform()->getDatabase('networks')->deleteKey($this->parameters['device']);
            $this->getAdapter()->flush();
            $this->getPlatform()->signalEvent('interface-update &');
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $X2 = function($x) use ($view) {
            if ($x === '') {
                return array($x, $view->translate("NoSuccessor_label"));
            } else {
                return array($x, $x);
            }
        };
        $view['successorDatasource'] = array_map($X2, array_merge($this->getDeviceParts($this->parameters['device']), array('')));
        $view['message'] = $view->translate(sprintf("DeleteLogicalInterface_%s_message", $this->parameters['type']), \iterator_to_array($this->parameters));
    }

    private function getDeviceParts($device)
    {
        return $this->getParent()->getDeviceParts($device);
    }

}
