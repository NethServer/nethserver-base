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

/**
 * Description of CleanPhysicalInterface
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class CleanPhysicalInterface extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('device', FALSE);
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
        $this->parameters['device'] = $keyValue;
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $ndb = $this->getPlatform()->getDatabase('networks');
            $ndb->deleteKey($this->parameters['device']);
            $this->getAdapter()->flush();
        }
    }

}
