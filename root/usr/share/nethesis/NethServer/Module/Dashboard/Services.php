<?php
namespace NethServer\Module\Dashboard;

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

    public $sortId = 10;

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Security', 20);
    }

    public function initialize()
    {
        $columns = array(
            'Key',
            'status',
            'running',
            'ports',
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('configuration', 'service', function($key, $record) {
                return file_exists("/etc/e-smith/db/configuration/defaults/$key/status");
            }))
            ->setColumns($columns)
        ;

        parent::initialize();
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
        static $serviceStatusCache;
        $ret = "...";
        $request = $this->getRequest();

        if (isset($request) && $this->getRequest()->getExtension() === 'json') {
            if( ! isset($serviceStatusCache)) {
                $serviceStatusCache = json_decode($this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/read-service-status')->getOutput(), TRUE);
            }
            if($serviceStatusCache === FALSE) {
                return "N/A";
            } elseif (isset($serviceStatusCache[$key]['running']) && $serviceStatusCache[$key]['running']) {
               $ret = $view->translate("running_label");
               $rowMetadata['rowCssClass'] .= ' running ';
            } else {
               $ret = $view->translate("stopped_label");
               if ($values['status'] == 'enabled') {
                   $rowMetadata['rowCssClass'] .= ' stopped ';
               }
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



   /**
    * XXX: experimental -> CSS injection 
   **/
   public function prepareView(\Nethgui\View\ViewInterface $view)
   {
       $cssCode = "
           tr.running td:nth-child(3) { color: green }
           tr.stopped td:nth-child(3) { color: red }
       ";
       $view->getCommandList('/Resource/css')->appendCode($cssCode, 'css');
       $moduleUrl = json_encode($view->getModuleUrl("/Dashboard/Services"));

       $jsCode = "
(function ( $ ) {
    $(document).ready(function() {
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: $moduleUrl
        });
    });
} ( jQuery ));
       ";
       $view->getCommandList('/Resource/js')->appendCode($jsCode, 'js');
       parent::prepareView($view);
   }

}

