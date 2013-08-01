<?php
namespace NethServer\Module\NetworkAdapter;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
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
 * Modify domain
 *
 * Generic class to create/update/delete Domain records
 * 
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Modify extends \Nethgui\Controller\Table\Modify
{
    /**
     * @var Array list of valid roles
     */
    private $roles = array('green','red');

    /**
    * @var Array of information about nic.
    * Fields: name, hwaddr, bus, model, driver, speed, link
    * Eg: green,08:00:27:77:fd:be,pci,Intel Corporation 82540EM Gigabit Ethernet Controller (rev 02),e1000,1000,1
    */
    private $nicInfo = null;

    public function initialize()
    {
        $parameterSchema = array(
            array('device', Validate::USERNAME, \Nethgui\Controller\Table\Modify::KEY),
            array('hwaddr', Validate::MACADDRESS, \Nethgui\Controller\Table\Modify::FIELD),
            array('role', $this->getPlatform()->createValidator()->memberOf($this->roles), \Nethgui\Controller\Table\Modify::FIELD),
            array('bootproto', $this->getPlatform()->createValidator()->memberOf(array('dhcp','static')), \Nethgui\Controller\Table\Modify::FIELD),
            array('ipaddr', Validate::IPv4_OR_EMPTY, \Nethgui\Controller\Table\Modify::FIELD),
            array('netmask', Validate::NETMASK_OR_EMPTY, \Nethgui\Controller\Table\Modify::FIELD),
            array('gateway', Validate::IPv4_OR_EMPTY, \Nethgui\Controller\Table\Modify::FIELD),
        );


        $this->setSchema($parameterSchema);
        $this->setDefaultValue('bootproto', 'static');

        parent::initialize();
    }

 
    public function process()
    {
        if($this->getIdentifier() === 'update') {
            if(!$this->nicInfo) {
                $this->nicInfo = str_getcsv($this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/nic-info '.$this->parameters['device'])->getOutput());
            }
        }
        parent::process();
    }


    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['roleDatasource'] =  array_map(function($fmt) use ($view) {
            return array($fmt, $view->translate($fmt . '_label'));
        }, $this->roles);

        if($this->getIdentifier() === 'update') {
            $view['bus'] = isset($this->nicInfo[2])?$this->nicInfo[2]:"";
            $view['model'] = isset($this->nicInfo[3])?$this->nicInfo[3]:"";
            $view['driver'] = isset($this->nicInfo[4])?$this->nicInfo[4]:"";
            $view['speed'] = isset($this->nicInfo[5])?$this->nicInfo[5]:"";
            if (!isset($this->nicInfo[6]) || (intval($this->nicInfo[6]) < 0) ) {
                $view['link'] = "N/A";
            } else {
                $view['link'] = $this->nicInfo[6]?$view->translate('Yes'):$view->translate('No');
            }
        }

        $templates = array(
            'create' => 'NethServer\Template\NetworkAdapter\Modify',
            'update' => 'NethServer\Template\NetworkAdapter\Modify',
            'delete' => 'Nethgui\Template\Table\Delete',
        );
        $view->setTemplate($templates[$this->getIdentifier()]);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        $role = $this->getRequest()->getParameter('role');
        $bootproto = $this->getRequest()->getParameter('bootproto');

        if (strpos(is_string($role) ? $role : '','red') === FALSE && $bootproto === 'dhcp') {
                $report->addValidationErrorMessage($this, 'bootproto', 'valid_bootproto_combination');
        }
    }

    /**
     * Delete the record after the event has been successfully completed
     * @param string $key
     */
    protected function processDelete($key)
    {
        parent::processDelete($key);
    }

    protected function onParametersSaved($changedParameters)
    {
        $this->getPlatform()->signalEvent('interface-update@post-response &');
    }

}
