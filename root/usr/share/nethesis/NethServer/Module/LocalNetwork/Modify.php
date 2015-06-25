<?php
namespace NethServer\Module\LocalNetwork;

/*
 * Copyright (C) 2015 Nethesis S.r.l.
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
 * Edit LocalNetwork
 *
 * @author Giacomo Sanchietti
 *
 */
class Modify extends \Nethgui\Controller\Table\Modify
{

    public function initialize()
    {

        $parameterSchema = array(
            array('network', Validate::IPv4, \Nethgui\Controller\Table\Modify::KEY),
            array('Mask', Validate::IPv4_NETMASK, \Nethgui\Controller\Table\Modify::FIELD),
            array('Description', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
        );

        $this->setSchema($parameterSchema);

        parent::initialize();
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        if ( ! $this->getRequest()->isMutation() || $report->hasValidationErrors()) {
            return;
        }

        // check the "network" parameter is consistent with its "Mask" (only 0-bits in tail)
        $net = long2ip(ip2long($this->parameters['network']) & ip2long($this->parameters['Mask']));
        if ($net != $this->parameters['network']) {
            $report->addValidationErrorMessage($this, 'network', 'invalid_network', array($this->parameters['network']));
            return;
        }

        // check the network is not already used
        $curr = $this->parameters['network'] . '/' . $this->parameters['Mask'];
        foreach ($this->getParent()->getAdapter() as $net => $props) {
            if ($curr === ($net . '/' . $props['Mask'])) {
                $report->addValidationErrorMessage($this, 'network', 'used_network', array($this->parameters['network']));
                return;
            }
        }
    }

}
