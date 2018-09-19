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
        $this->declareParameter('Policy', $this->createValidator()->memberOf('', '20180330', '20180621','20181001'), array('configuration', 'tls', 'policy'));
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        $this->getValidator('Policy')->platform('tlspolicy-safetyguard', $this->getPlatform()->getDatabase('configuration')->getProp('pki', 'KeyFile'));
        parent::validate($report);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['PolicyDatasource'] = array(
                array('', $view->translate('Default_policy_label')),
                array('20180330', $view->translate('Policy_item_label', array('2018-03-30'))),
                array('20180621', $view->translate('Policy_item_label', array('2018-06-21'))),
                array('20181001', $view->translate('Policy_item_label', array('2018-10-01'))),
        );
    }

    protected function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('tls-policy-save &');
    }

}
