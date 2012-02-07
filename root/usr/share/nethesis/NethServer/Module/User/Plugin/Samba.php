<?php
namespace NethServer\Module\User\Plugin;

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
 * @todo describe class
 */
class Samba extends \Nethgui\Controller\Table\AbstractAction implements \NethServer\Module\User\PluginInterface
{

    private $key;

    public function getParentIdentifier()
    {
        return array("Service", 0);
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $this->declareParameter('Samba', Validate::SERVICESTATUS, array($this->getAdapter(), $this->key, 'Samba'));
        parent::bind($request);
    }

}
