<?php
namespace NethServer\Module;

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
 * Change SystemName and DomainName
 *
 * @author Giacomo Sanchietti<giacomo.sanchietti@nethesis.it>
 */
class FQDN extends \Nethgui\Controller\AbstractController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Configuration', 10);
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('FQDN', $this->createValidator()->hostname(2)->platform('myhostname'), array('NethServer::Database::Hostname', 'StaticHostname'));
    }

    protected function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('hostname-modify &');
    }

}

