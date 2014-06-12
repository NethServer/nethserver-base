<?php
namespace NethServer\Module\NetworkServices;

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
use Nethgui\Controller\Table\Modify as Table;

/**
 * Service mdify action
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class Modify extends \Nethgui\Controller\Table\Modify
{
    private $access_values = array('private','public','none');

    public function initialize()
    {
        parent::initialize();
        $this->setViewTemplate('NethServer\Template\NetworkServices\Modify');

        $parameterSchema = array(
            array('name', Validate::ANYTHING, Table::KEY),
            array('status', Validate::SERVICESTATUS, Table::FIELD),
            array('access', $this->createValidator()->memberOf($this->access_values), Table::FIELD),
            array('AllowHosts', Validate::ANYTHING, Table::FIELD),
            array('DenyHosts', Validate::ANYTHING, Table::FIELD),
        );

        $this->setSchema($parameterSchema);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        $ips = explode(',',$this->parameters['AllowHosts']);
        $ipvalidator = $this->createValidator(Validate::IPv4_OR_EMPTY);
        foreach($ips as $ip) {
            if(!$ipvalidator->evaluate($ip)) {
                $report->addValidationErrorMessage($this, 'AllowHosts', 'AllowHosts_validator');
            }
        }

        $ips = explode(',',$this->parameters['DenyHosts']);
        $ipvalidator = $this->createValidator(Validate::IPv4_OR_EMPTY);
        foreach($ips as $ip) {
            if(!$ipvalidator->evaluate($ip)) {
                $report->addValidationErrorMessage($this, 'DenyHosts', 'DenyHosts_validator');
            }
        }

        parent::validate($report);
    }

    protected function onParametersSaved($changedParameters)
    {
        $this->getPlatform()->signalEvent('firewall-adjust');
    }

}
