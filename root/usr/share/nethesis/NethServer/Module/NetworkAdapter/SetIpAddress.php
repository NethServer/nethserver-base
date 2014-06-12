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
 * Wizard panel to configure IP address for a Logical Interface
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class SetIpAddress extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();

        $sessionKey = get_class($this->getParent());

        $this->declareParameter('bootproto', $this->createValidator()->memberOf('none', 'dhcp'), array('SESSION', $sessionKey, 'bootproto'));
        $this->declareParameter('ipaddr', $this->createValidator(Validate::IPv4), array('SESSION', $sessionKey, 'ipaddr'));
        $this->declareParameter('netmask', $this->createValidator(Validate::NETMASK), array('SESSION', $sessionKey, 'netmask'));
        $this->declareParameter('gateway', $this->createValidator(Validate::IPv4_OR_EMPTY), array('SESSION', $sessionKey, 'gateway'));
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        if ($this->parameters['bootproto'] == 'dhcp') {
            unset($this->parameters['netmask']);
            unset($this->parameters['ipaddr']);
            unset($this->parameters['gateway']);
        }
        parent::validate($report);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['role'] = $this->getPlatform()->getDatabase('SESSION')->getProp(get_class($this->getParent()), 'role');
    }

    public function nextPath()
    {
        if ($this->getRequest()->isMutation()) {
            return 'ConfirmInterfaceCreation';
        }
        return parent::nextPath();
    }

}