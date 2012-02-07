<?php
namespace NethServer\Module\RemoteAccess;

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
 * @todo Describe Module class
 */
class Pptp extends \Nethgui\Controller\AbstractController
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter(
            'client', Validate::POSITIVE_INTEGER, array('configuration', 'pptpd', 'sessions')
        );
        $this->declareParameter(
            'status', Validate::SERVICESTATUS, array('configuration', 'pptpd', 'status')
        );
    }

    protected function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('remoteaccess-update@post-process');
    }

}
