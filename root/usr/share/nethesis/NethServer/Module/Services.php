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
 * Show list of services. For each service show:
 *   - status property
 *   - running status
 *   - TCP and UDP ports (if present)
 *
 * @author Giacomo Sanchietti
 *
 */
class Services extends \Nethgui\Controller\TableController
{
    private $serviceStatusCache;

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return new \NethServer\Tool\CustomModuleAttributesProvider($attributes, array(
            'languageCatalog' => array('NethServer_Module_NetworkServices', 'NethServer_Module_Dashboard_Services'),
            'category' => 'Status')
        );
    }


    public function initialize()
    {
        $columns = array(
            'Key',
            'status',
            'running',
            'ports',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('configuration', 'service', function($key, $record) {
                return file_exists("/etc/e-smith/db/configuration/defaults/$key/status");
            }))
            ->addRowAction(new Services\Systemctl('restart'))
            ->addRowAction(new Services\Systemctl('stop'))
            ->addRowAction(new Services\Systemctl('start'))
            ->setColumns($columns)
        ;

        parent::initialize();
    }

    private function getServiceStatusCache()
    {
        if( ! isset($this->serviceStatusCache)) {
            $this->serviceStatusCache = json_decode($this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/read-service-status')->getOutput(), TRUE);
        }
        return $this->serviceStatusCache;
    }

    public function prepareViewForColumnStatus(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $ret = "...";
        $request = $this->getRequest();

        $serviceStatusCache = $this->getServiceStatusCache();
        if(!$serviceStatusCache || !isset($serviceStatusCache[$key]['enabled'])) {
            return "N/A";
        } elseif (isset($serviceStatusCache[$key]['enabled'])) {
            $ret = $serviceStatusCache[$key]['enabled'] ? $view->translate("enabled_label") : $view->translate("disabled_label");
        }
 
        return $ret;
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $d = $view->translate($key."_Description");
        if ($d && $d != $key."_Description") {
                $key .= " ($d)";
            return $key;
        }
        return $key;
    }

    /**
     *
     * @param \Nethgui\Controller\Table\Read $action
     * @param \Nethgui\View\ViewInterface $view
     * @param string $key The data row key
     * @param array $values The data row values
     * @return string|\Nethgui\View\ViewInterface
     */
    public function prepareViewForColumnRunning(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $ret = "...";
        $serviceStatusCache = $this->getServiceStatusCache();
        if(!$serviceStatusCache || !isset($serviceStatusCache[$key]['running'])) {
            return "N/A";
        } elseif (isset($serviceStatusCache[$key]['running']) && $serviceStatusCache[$key]['running']) {
            $ret = $view->translate("running_label");
            $rowMetadata['rowCssClass'] .= ' running ';
        } else {
            $ret = $view->translate("stopped_label");
            if ($serviceStatusCache[$key]['enabled']) {
                $rowMetadata['rowCssClass'] .= ' stopped ';
            }
        }

        return $ret; 
    }

    /**
     *
     * @param \Nethgui\Controller\Table\Read $action
     * @param \Nethgui\View\ViewInterface $view
     * @param string $key The data row key
     * @param array $values The data row values
     * @return string|\Nethgui\View\ViewInterface
     */
    public function prepareViewForColumnPorts(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $ret = " ";
        $tcp = isset($values['TCPPort'])?$values['TCPPort']:"";
        if ($tcp == "" &&  isset($values['TCPPorts'])) {
             $tcp = $values['TCPPorts'];
        }
        $udp = isset($values['UDPPort'])?$values['UDPPort']:"";
        if ($udp == "" &&  isset($values['UDPPorts'])) {
             $udp = $values['UDPPorts'];
        }
        if ($tcp !== "") {
            $ret .= $view->translate("TCPPorts_label").": $tcp ";
        }
        if ($udp !== "") {
            $ret .= " ".$view->translate("UDPPorts_label").": $udp ";
        }
       
        return $ret;
    }


    public function prepareViewForColumnActions(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $serviceStatusCache = $this->getServiceStatusCache();
        $cellView = $action->prepareViewForColumnActions($view, $key, $values, $rowMetadata);
        if (isset($serviceStatusCache[$key]) && isset($serviceStatusCache[$key]['running'])) {
            if ( $serviceStatusCache[$key]['running'] ) {
                unset($cellView['start']);
            } else {
                unset($cellView['stop']);
                unset($cellView['restart']);
            }
        }
        return $cellView;
   }

}

