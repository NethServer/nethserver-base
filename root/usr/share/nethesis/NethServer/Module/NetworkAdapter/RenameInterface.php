<?php

namespace NethServer\Module\NetworkAdapter;

/*
 * Copyright (C) 2015 Nethesis Srl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Assign new network adapter to old interface name
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class RenameInterface extends \Nethgui\Controller\AbstractController implements \Nethgui\Component\DependencyConsumer
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('cards', \Nethgui\System\PlatformInterface::ANYTHING_COLLECTION);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        if ( ! $this->getRequest()->isMutation()) {
            return;
        }

        $getSecondField = function($line) {
            $a = str_getcsv($line);
            return $a[1];
        };

        $validInterfaces = array_keys($this->getUnassignedInterfaces());
        $validMacs = array_map($getSecondField, $this->getNicInfo());

        $duplicatesCheck = array();

        foreach ($this->parameters['cards'] as $interface => $setting) {
            if (in_array($setting['interface'], $duplicatesCheck)) {
                $report->addValidationErrorMessage($this, 'cards', 'Duplicated assignment');
            }
            if (isset($setting['interface']) && $setting['interface'] && ! in_array($setting['interface'], $validInterfaces)) {
                $report->addValidationErrorMessage($this, 'cards', 'Inconsistent interface name');
            }
            if ( ! in_array($setting['hwaddr'], $validMacs)) {
                $report->addValidationErrorMessage($this, 'cards', 'Inconsistent mac address');
            }
            if ($setting['interface']) {
                $duplicatesCheck[] = $setting['interface'];
            }
        }
    }

    public function process()
    {
        parent::process();
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }
        $ndb = $this->getPlatform()->getDatabase('networks');
        $modified = FALSE;
        foreach ($this->parameters['cards'] as $name => $setting) {
            if (isset($setting['interface']) && $setting['interface'] !== '') {
                $ndb->setProp($setting['interface'], array('hwaddr' => $setting['hwaddr']));
                if ($name !== $setting['interface']) {
                    $ndb->deleteKey($name);
                }
                $modified = TRUE;
            }
        }
        if ($modified) {
            $this->getPlatform()->signalEvent('interface-update &');
        }
    }

    public function setUserNotifications(\Nethgui\Model\UserNotifications $n)
    {
        $this->notifications = $n;
        return $this;
    }

    public function getDependencySetters()
    {
        return array('UserNotifications' => array($this, 'setUserNotifications'));
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $this->notifications->defineTemplate('adminTodo', \NethServer\Module\AdminTodo::TEMPLATE, 'bg-yellow');
        $T = function($msg, $args = array()) use ($view) {
            return $view->translate($msg, $args);
        };

        $view['cards'] = $this->getCards($T);
        if ($this->getRequest()->isValidated()) {
            $view->getCommandList()->show();
        }
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->setDetachedProcessCondition('success', array(
                'location' => array(
                    'url' => $view->getModuleUrl('/NetworkAdapter?renameSuccess'),
                    'freeze' => TRUE,
            )));
        }
    }

    /**
     * Find interfaces names with a role and associated to a non-existent MAC address
     */
    private function getCards($T)
    {
        $formatInterface = function($e) {
            return strtr($e['bootproto'] === 'dhcp' ? 'role DHCP(hwaddr)' : 'role ipaddr', $e);
        };

        $basicDatasource = array('' => $T('[leave untouched]'));
        $interfaceDatasource = array_merge($basicDatasource, array_map($formatInterface, $this->getUnassignedInterfaces()));
        $ndb = $this->getPlatform()->getDatabase('networks')->getAll('ethernet');

        $fields = explode(' ', 'name hwaddr type model driver speed link');
        $parsef = function($line) use ($fields, $interfaceDatasource, $basicDatasource, $ndb, $T) {

            $values = str_getcsv($line);
            $h = array_combine($fields, $values);
            $h['link'] = $h['link'] ? $T('linkon') : $T('linkoff');
            $isPresent = isset($ndb[$h['name']]) && strtolower($ndb[$h['name']]['hwaddr']) === strtolower($h['hwaddr']);
            $h['interfaceDatasource'] = \Nethgui\Widget\XhtmlWidget::hashToDatasource($isPresent ? $basicDatasource : $interfaceDatasource);
            $h['configuration'] = $isPresent ? 'configured' : 'unconfigured';
            return $h;
        };

        $cards = array_map($parsef, $this->getNicInfo());

        if ($this->getRequest()->isMutation()) {
            return $this->requestAssignment($cards);
        } else {
            return $cards;
        }
    }

    private function requestAssignment($cards)
    {
        foreach ($cards as $i => $elem) {
            if ( ! isset($cards[$i]['interface']) || $cards[$i]['interface'] === '') {
                $cards[$i]['interface'] = $this->parameters['cards'][$elem['name']]['interface'];
            }
        }
        return $cards;
    }

    private function getNicInfo()
    {
        static $info;
        if (isset($info)) {
            return $info;
        }
        $info = $this->getPlatform()->exec('/usr/bin/sudo -n /usr/libexec/nethserver/nic-info')->getOutputArray();
        return $info;
    }

    private function getUnassignedInterfaces()
    {
        static $h;

        if (isset($h)) {
            return $h;
        }

        $h = array();
        $data = json_decode($this->getPlatform()->exec('/usr/libexec/nethserver/eth-unmapped')->getOutput(), TRUE);
        if ( ! is_array($data)) {
            return $h;
        }
        foreach ($data as $e) {
            $h[$e['name']] = $e;
        }
        return $h;
    }

}
