<?php

namespace NethServer\Module;

/*
 * Copyright (C) 2018 Nethesis S.r.l.
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
 * Change the TLS policy
 *
 * @author Stephane de Labrusse <stephdl@de-labrusse.fr>
 */
class TlsPolicy extends \Nethgui\Controller\AbstractController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Security', 12);
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('Policy', $this->createValidator()->memberOf('', '20180330'), array('configuration', 'tls', 'policy'));
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['PolicyDatasource'] = array(
                array('', $view->translate('LEGACY')),
                array('20180330', '2018-03-30'),
        );
    }

    protected function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('tls-policy-save &');
    }

}
