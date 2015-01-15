<?php

namespace NethServer\Module\PackageManager;

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
 * Description of Modules
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class Modules extends \Nethgui\Controller\CollectionController implements \Nethgui\Component\DependencyConsumer
{

    public function initialize()
    {
        $this->setAdapter(new \Nethgui\Adapter\LazyLoaderAdapter(array($this->getParent(), 'yumGroupsLoader')));
        $this->setIndexAction(new \NethServer\Module\PackageManager\Modules\Available());
        $this->addChild(new \NethServer\Module\PackageManager\Modules\Installed());
        $this->addChild(new \NethServer\Module\PackageManager\Modules\Update());
        parent::initialize();
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        if ($this->getRequest()->hasParameter('installSuccess')) {
            $this->notifications->message($view->translate('package_success'));
        }
    }
    
    public function renderIndex(\Nethgui\Renderer\Xhtml $view)
    {
        $view->includeFile('Nethgui/Js/jquery.nethgui.tabs.js');
        $view->includeFile('Nethgui/Js/jquery.nethgui.controller.js');

        $panel = $view->panel()->setAttribute('class', 'ModulesWrapped');
        $header = $view->header()->setAttribute('template', $view->translate('Modules_header'));

        $tabs = $view->tabs()->setAttribute('receiver', '');

        foreach ($this->getChildren() as $module) {
            $moduleIdentifier = $module->getIdentifier();

            $flags = \Nethgui\Renderer\WidgetFactoryInterface::INSET_WRAP;

            if ($this->needsAutoFormWrap($module)) {
                $flags |= \Nethgui\Renderer\WidgetFactoryInterface::INSET_FORM;
            }

            $action = $view->inset($moduleIdentifier, $flags)
                    ->setAttribute('class', 'Action')
                    ->setAttribute('title', $view->getTranslator()->translate($module, $moduleIdentifier . '_Title'))
            ;

            $tabs->insert($action);
        }

        return $panel->insert($header)->insert($tabs);
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
}
