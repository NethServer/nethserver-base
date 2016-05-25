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
 * Edit a physical interface
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Edit extends \Nethgui\Controller\Table\RowAbstractAction
{

    public function initialize()
    {

	$WeightAdapter = $this->getPlatform()->getMapAdapter(array($this, 'readWeight'), array($this, 'writeWeight'), array());
	$ProviderNameAdapter = $this->getPlatform()->getMapAdapter(array($this, 'readProviderName'), array($this, 'writeProviderName'), array());
        $MultiwanEnabledAdapter = $this->getPlatform()->getMapAdapter(array($this, 'readMultiwanEnabled'), array($this, 'writeMultiwanEnabled'), array());

        $this->setSchema(array(
            array('device', Validate::ANYTHING, \Nethgui\Controller\Table\RowAbstractAction::KEY),
            array('role', $this->createValidator()->memberOf($this->getParent()->getInterfaceRoles()), \Nethgui\Controller\Table\RowAbstractAction::FIELD),
            array('bootproto', $this->createValidator()->memberOf('dhcp', 'none'), \Nethgui\Controller\Table\RowAbstractAction::FIELD),
            array('ipaddr', Validate::IPv4, \Nethgui\Controller\Table\RowAbstractAction::FIELD),
            array('netmask', Validate::IPv4_NETMASK, \Nethgui\Controller\Table\RowAbstractAction::FIELD),
            array('gateway', Validate::IP_OR_EMPTY, \Nethgui\Controller\Table\RowAbstractAction::FIELD),

            array('ProviderName', $this->createValidator()->maxLength(5)->minLength(1)->regexp('/^(?:(?!main).)*$/')->regexp('/^(?:(?!local).)*$/'), $ProviderNameAdapter),
            array('Weight', $this->createValidator()->integer()->greatThan(0)->lessThan(256), $WeightAdapter),
            array('Multiwan', Validate::SERVICESTATUS,$MultiwanEnabledAdapter),
        ));
        parent::initialize();
    }

    public function readProviderName()
    {
	foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
            if ($provider['interface'] === $this->parameters['device']){
                return $name;
            }
        }
    }

    public function writeProviderName()
    {
        //check that there aren't providers for this interface
        foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
            if ($provider['interface'] === $this->parameters['device']){
                if ($this->parameters['ProviderName']!=$name) {
                     $this->getPlatform()->getDatabase('networks')->deleteKey($name);
                } else {
                    $this->getPlatform()->getDatabase('networks')->setProp($this->parameters['ProviderName'],array('interface'=>$this->parameters['device'], 'status'=>'enabled','weight'=>$this->parameters['Weight']));
                    return TRUE;
                }
            }
        }
        $this->getPlatform()->getDatabase('networks')->setKey($this->parameters['ProviderName'],'provider',array());
        $this->getPlatform()->getDatabase('networks')->setProp($this->parameters['ProviderName'],array('interface'=>$this->parameters['device'], 'status'=>'enabled','weight'=>$this->parameters['Weight'] ));
        return TRUE;
    }

    public function readWeight()
    {
        foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
            if ($provider['interface'] === $this->parameters['device']) {
                return $provider['weight'];
            }
        }
    }

    public function writeWeight()
    {
        foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
            if ($provider['interface'] === $this->parameters['device']) {
                $this->getPlatform()->getDatabase('networks')->setProp($name,array('weight'=>$this->parameters['Weight']));
                return TRUE;
            }
        }
    }

    public function readMultiwanEnabled()
    {
        foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
            if ($provider['interface'] === $this->parameters['device']) {
                return $provider['status'];
            }
        }
        return 'disabled';
    }

    public function writeMultiwanEnabled()
    {
        foreach ($this->getPlatform()->getDatabase('networks')->getAll('provider') as $name=>$provider) {
            if ($provider['interface'] === $this->parameters['device']) {
                if ($this->parameters['Multiwan'] === 'enabled' ) {
                    $this->getPlatform()->getDatabase('networks')->setProp($name,array('status'=>'enabled'));
                } else {
                    $this->getPlatform()->getDatabase('networks')->setProp($name,array('status'=>'disabled'));
                }
                return TRUE;
            }
        }
        return FALSE;
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $keyValue = \Nethgui\array_head($request->getPath());

        $A = $this->getParent()->getAdapter();

        if ( ! isset($A[$keyValue])) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399033549);
        }

        if (isset($A[$keyValue]['role']) && in_array($A[$keyValue]['role'], array('bridged', 'slave', 'alias'))) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1399033550);
        }


        $this->getAdapter()->setKeyValue($keyValue);
        parent::bind($request);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        if ($this->getRequest()->isMutation()) {
            $v = $this->createValidator()->platform('interface-config');
            if ( ! $v->evaluate(json_encode($this->parameters->getArrayCopy()))) {
                $report->addValidationError($this, 'interface-config', $v);
            }
        }
    }

    private function getNicInfo(\Nethgui\View\ViewInterface $view)
    {
        $v = array();
        $nicInfo = array();

        if ($this->getAdapter()->offsetGet('type') === 'ethernet') {
            // Array of informations about NIC.
            // Fields: name, hwaddr, bus, model, driver, speed, link
            // Eg: green,08:00:27:77:fd:be,pci,Intel Corporation 82540EM Gigabit Ethernet Controller (rev 02),e1000,1000,1
            $nicInfo = str_getcsv($this->getPlatform()->exec('/usr/bin/sudo -n /usr/libexec/nethserver/nic-info ${1}', array($this->parameters['device']))->getOutput());
        }

        $v['dev'] = isset($this->parameters['device']) ? $this->parameters['device'] : '';
        $v['mac'] = \strtolower(isset($nicInfo[1]) ? $nicInfo[1] : '');
        $v['bus'] = \strtolower(isset($nicInfo[2]) ? $nicInfo[2] : "");
        $v['model'] = isset($nicInfo[3]) ? $nicInfo[3] : "";
        $v['driver'] = isset($nicInfo[4]) ? $nicInfo[4] : "";
        $v['speed'] = isset($nicInfo[5]) && $nicInfo[5] ? $nicInfo[5] : "0";
        if ( ! isset($nicInfo[6]) || (intval($nicInfo[6]) < 0)) {
            $v['link'] = $view->translate('Link_status_na');
        } else {
            $v['link'] = $nicInfo[6] ? $view->translate('Link_status_up') : $view->translate('Link_status_down');
        }

        return $v;
    }

    protected function onParametersSaved($changedParameters)
    {
        parent::onParametersSaved($changedParameters);
        $this->getPlatform()->signalEvent('interface-update &');
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if ( ! $this->getRequest()->isMutation() && $this->getRequest()->isValidated()) {
            $view['deviceInfos'] = $this->getNicInfo($view);
        }
        $view['bootproto'] = $view['bootproto'] ? $view['bootproto'] : 'none';
        $view['roleDatasource'] = array_map(function($fmt) use ($view) {
            return array($fmt, $view->translate($fmt . '_label'));
        }, $this->getParent()->getInterfaceRoles());
    }

}
