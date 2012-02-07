<?php
namespace NethServer\Module\User;

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

/**
 * @todo describe class
 */
class Update extends Modify implements \Nethgui\Module\ModuleCompositeInterface
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\CompositeModuleAttributesProvider::extendModuleAttributes($base)->extendFromComposite($this);        
    }

    /**
     * @var \NethServer\Module\User\Plugin
     */
    private $pluginSet;

    public function __construct()
    {
        parent::__construct('update');
        $this->pluginSet = new Plugin();
        $this->pluginSet->setParent($this);
    }

    public function addChild(\Nethgui\Module\ModuleInterface $module)
    {
        $this->pluginSet->addChild($module);
    }

    public function getChildren()
    {
        return $this->pluginSet->getChildren();
    }

    public function setPlatform(\Nethgui\System\PlatformInterface $platform)
    {
        parent::setPlatform($platform);
        $this->pluginSet->setPlatform($this->getPlatform());
        return $this;
    }

    public function initialize()
    {
        parent::initialize();
        $this->pluginSet->initialize();
    }

    public function setTableAdapter(\Nethgui\Adapter\AdapterInterface $tableAdapter)
    {
        parent::setTableAdapter($tableAdapter);
        $this->pluginSet->setTableAdapter($tableAdapter);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $this->pluginSet
            ->setKey($this->parameters['username'])
            ->bind($request->spawnRequest($this->pluginSet->getIdentifier()));
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        $this->pluginSet->validate($report);
    }

    public function process()
    {
        $this->pluginSet->process();
        parent::process();
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $this->pluginSet->prepareView($view->spawnView($this->pluginSet, TRUE));
        if (isset($this->parameters['username'])) {
            $view['change-password'] = $view->getModuleUrl('../change-password/' . $this->parameters['username']);
            $view['FormAction'] = $view->getModuleUrl($this->parameters['username']);
        } else {
            $view['change-password'] = '';
        }
    }

}

