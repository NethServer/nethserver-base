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
 * Mange system network services
 * @author Giacomo Sanchietti
 *
 */
class NetworkServices extends \Nethgui\Controller\TableController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Security', 30);
    }

    public function initialize()
    {
        $columns = array(
            'Key',
            'ports',
            'access',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('configuration','service', function($record) {
                if (!isset($record['TCPPorts']) && !isset($record['UDPPorts']) && !isset($record['TCPPort']) && !isset($record['UDPPort']) ) {
                    return false;
                }
                if ( !isset($record['access']) || !isset($record['status']) )  {
                    return false;
                }
                return true;
            }))
            ->addRowAction(new \NethServer\Module\NetworkServices\Modify('update'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->setColumns($columns)
        ;

        parent::initialize();
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

}
