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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Create an IP alias on the given interface
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class CreateIpAlias extends \Nethgui\Controller\Table\RowAbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $this->setSchema(array(
            array('alias', FALSE, \Nethgui\Controller\Table\RowAbstractAction::KEY),
            array('role', FALSE, \Nethgui\Controller\Table\RowAbstractAction::FIELD),
            array('type', FALSE, \Nethgui\Controller\Table\RowAbstractAction::FIELD),
            array('ipaddr', $this->createValidator(Validate::IPv4), \Nethgui\Controller\Table\RowAbstractAction::FIELD),
            array('netmask', $this->createValidator(Validate::NETMASK), \Nethgui\Controller\Table\RowAbstractAction::FIELD),
        ));
    }

    private function generateKey($request)
    {
        $device = \Nethgui\array_head($request->getPath());
        $A = $this->getParent()->getAdapter();
        if ( ! isset($A[$device])) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399459956);
        }
        if ($A[$device]['type'] === 'alias') {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399459957);
        }
        $existingAliases = array();
        foreach ($A as $key => $props) {
            if ($props['type'] === 'alias' && substr($key, 0, strlen($device)) === $device) {
                $existingAliases[] = substr($key, strlen($device) + 1); // remove the prefix up to ":"
            }
        }
        for($aliasNb = 0; in_array($aliasNb, $existingAliases); $aliasNb++) ;
        return sprintf('%s:%d', $device, $aliasNb);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $this->getAdapter()->setKeyValue($this->generateKey($request));
        if ($this->getRequest()->isMutation()) {
            $this->parameters['type'] = 'alias';
            $this->parameters['role'] = 'alias';
        }
    }

    protected function onParametersSaved($changedParameters)
    {
        parent::onParametersSaved($changedParameters);
        $this->getPlatform()->signalEvent('interface-update &');
    }

}