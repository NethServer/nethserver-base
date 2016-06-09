<?php
namespace NethServer\Module\NetworkAdapter;

/*
 * Copyright (C) 2016 Nethesis S.r.l.
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
 * Control upstream proxy configuration
 * 
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class UpstreamProxy extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $vh = $this->createValidator()->orValidator($this->createValidator(Validate::HOSTNAME), $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING));
        $vp = $this->createValidator()->orValidator($this->createValidator(Validate::PORTNUMBER), $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING));
        $this->declareParameter('host', $vh, array('configuration', 'proxy', 'host'));
        $this->declareParameter('port', $vp, array('configuration', 'proxy', 'port'));
        $this->declareParameter('user', Validate::ANYTHING, array('configuration', 'proxy', 'user'));
        $this->declareParameter('password', Validate::ANYTHING, array('configuration', 'proxy', 'password'));
    }

    protected function onParametersSaved($changedParameters)
    {
        parent::onParametersSaved($changedParameters);
        $this->getPlatform()->signalEvent('proxy-update');
    }

}
