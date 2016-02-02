<?php
namespace NethServer\Module\Services;

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
 * Start/Stop/Restart a services
 *
 */
class Systemctl extends \Nethgui\Controller\Table\AbstractAction
{

    public function __construct($identifier = NULL)
    {
        if ($identifier !== 'start' && $identifier !== 'restart' && $identifier !== 'stop') {
            throw new \InvalidArgumentException(sprintf('%s: module identifier must be one of "start", "stop", "restart".', get_class($this)), 1454344469);
        }
        parent::__construct($identifier);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $this->declareParameter('service', Validate::ANYTHING);

        parent::bind($request);
        $service = \Nethgui\array_end($request->getPath());

        if ( ! $service) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1454344500);
        }

        $this->parameters['service'] = $service;
    }

    public function process()
    {
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }

        $this->getPlatform()->exec("sudo /usr/libexec/nethserver/control-service ".$this->parameters['service']." ".$this->getIdentifier());
    }

}
