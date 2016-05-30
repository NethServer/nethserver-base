<?php
namespace NethServer\Module;
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

/**
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.6
 */
class Pki extends \Nethgui\Controller\TableController
{
    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Configuration', 10);
    }


    public function initialize()
    {
        
        $adapter = new \NethServer\Module\Pki\CertAdapter($this->getPlatform());
        $this->setTableAdapter($adapter);
        $this->setColumns(array(
            'Key',
            'Issuer',
            'ExpireDate',
            'Actions'
        ));
        parent::initialize();        
        $this->addRowAction(new \NethServer\Module\Pki\Show());
        $this->addRowAction(new \NethServer\Module\Pki\SetDefault());
        $this->addChild(new \NethServer\Module\Pki\Generate());
        $this->addChild(new \NethServer\Module\Pki\GenerateLe());
        $this->addChild(new \NethServer\Module\Pki\Upload());
        
        
    }
    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if(is_object($view['read'])) {
            $view['read']->setTemplate('NethServer\Template\Pki\Index');
        }
    }
}