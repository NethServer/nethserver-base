<?php
namespace NethServer\Module\User;

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
 * Lock/unlock a user account
 *
 * Fires events
 * - user-lock
 * - user-unlock
 */
class ToggleLock extends \Nethgui\Controller\Table\AbstractAction
{

    public function __construct($identifier = NULL)
    {
        if ($identifier !== 'lock' && $identifier !== 'unlock') {
            throw new \InvalidArgumentException(sprintf('%s: module identifier must be one of "lock" or "unlock".', get_class($this)), 1325579395);
        }
        parent::__construct($identifier);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $this->declareParameter('username', Validate::USERNAME);

        parent::bind($request);
        $username = \Nethgui\array_end($request->getPath());

        if ( ! $username) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1322148400);
        }

        $this->parameters['username'] = $username;
    }

    public function process()
    {
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }

        $this->getPlatform()->signalEvent(sprintf('user-%s@post-process', $this->getIdentifier()), array($this->parameters['username']));
    }

}
