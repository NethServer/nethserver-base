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
 * @todo describe class
 */
class Services extends \Nethgui\Controller\TableController
{

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
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('configuration', 'service'))
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
    public function prepareViewForColumnRunning(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $p = $this->getPlatform()->exec('/usr/bin/sudo /sbin/service ${1} status', array($key));
        return ($p->getExitCode() === 0)?"running":"stopped:".$p->getExitCode();    
    }

   /**
    * XXX: experimental -> CSS injection 
   **/
   public function prepareView(\Nethgui\View\ViewInterface $view)
   {
       $cssCode = ".odd { color: red }";
       $view->getCommandList('/Resource/css')->appendCode($cssCode, 'css');
       parent::prepareView($view);
   }

}

