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
    /**
     *
     * @var \Nethgui\Model\UserNotifications
     */
    public $notifications;

    private $yumError = NULL;

    public function initialize()
    {
        $this->setAdapter(new \Nethgui\Adapter\LazyLoaderAdapter(array($this->getParent(), 'yumGroupsLoader')));
        $this->setIndexAction(new \NethServer\Module\PackageManager\Modules\Available());
        $this->addChild(new \NethServer\Module\PackageManager\Modules\Installed());
        $this->addChild(new \NethServer\Module\PackageManager\Modules\Update());
        $this->addChild(new \NethServer\Module\AdminTodo());
        parent::initialize();
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if( ! $this->getRequest()->isValidated()) {
            $this->getAdapter()->setLoader(NULL);
        } else {
            $view->getCommandList()->show();
        }

        if ($this->getRequest()->hasParameter('installSuccess')) {
            $this->getAction('AdminTodo')->emitNotifications = TRUE;
            $this->notifications->yumSuccess(array(
                'message' => $view->translate('YumSuccess_message'),
                'description' => $view->translate('YumSuccess_description'),
                'buttonLabel' => $view->translate('YumSuccess_button_label'),
                'action' => $view->getModuleUrl('..')
            ));
        } elseif($this->getRequest()->hasParameter('installFailure')) {
            $taskStatus = $this->systemTasks->getTaskStatus($this->getRequest()->getParameter('taskId'));

            if(isset($taskStatus['children'][0]['message'])) {
                $message = $taskStatus['children'][0]['message'];
            } else {
                $message = $view->translate('An unknown installation error has occurred');
            }

            $this->notifications->yumError(array(
                'message' => $message,
                'description' => $view->translate('ClearYumCache_description'),
                'buttonLabel' => $view->translate('ClearYumCache_label'),
                'action' => $view->getModuleUrl('../ClearYumCache')
                ));
        }

        parent::prepareView($view);

        if (isset($this->yumError)) {
            $this->notifications->yumError(array(
                'message' => $this->yumError,
                'description' => $view->translate('ClearYumCache_description'),
                'buttonLabel' => $view->translate('ClearYumCache_label'),
                'action' => $view->getModuleUrl('../ClearYumCache')
            ));
        }
    }

    public function getYumCategories()
    {
        if( ! $this->getRequest()->isValidated()) {
            return array();
        }
        
        return $this->getParent()->yumCategories();
    }

    private function yumCheckUpdates()
    {
        static $data;

        if( ! $this->getRequest()->isValidated()) {
            return array();
        }

        if (isset($data)) {
            return $data;
        }

        $data = array();
        $checkUpdateJob = $this->getPlatform()->exec('/usr/bin/sudo -n /sbin/e-smith/pkginfo check-update');
        if ($checkUpdateJob->getExitCode() !== 0) {
            $data = json_decode($checkUpdateJob->getOutput(), TRUE);
            $this->yumError = isset($data['error']) ? $data['error'] : '';
            return array();
        }
        $data = json_decode($checkUpdateJob->getOutput(), TRUE);
        return $data;
    }

    public function getYumUpdates()
    {
        $data = $this->yumCheckUpdates();

        if (isset($data['updates'])) {
            $updates = $data['updates'];
            usort($updates, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        } else {
            $updates = array();
        }

        return $updates;
    }

    public function getYumChangelog()
    {
        $data = $this->yumCheckUpdates();
        return isset($data['changelog']) ? $data['changelog'] : '';
    }

    public function renderIndex(\Nethgui\Renderer\Xhtml $view)
    {
        $view->includeFile('Nethgui/Js/jquery.nethgui.tabs.js');
        $view->includeFile('Nethgui/Js/jquery.nethgui.controller.js');

        $panel = $view->panel()->setAttribute('class', 'ModulesWrapped')->setAttribute('id', 'PackageManager');
        $header = $view->header()->setAttribute('template', $view->translate('Modules_header'));

        $tabs = $view->tabs()->setAttribute('receiver', '');

        foreach ($this->getChildren() as $module) {
            $moduleIdentifier = $module->getIdentifier();

            if($moduleIdentifier === 'AdminTodo') {
                continue;
            }

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

        $element  = json_encode($view->getUniqueId());
        $url = json_encode($view->getModuleUrl());
        $view->includeJavascript(sprintf('(function($){$(function(){$.Nethgui.Server.ajaxMessage({url:%s, freezeElement:$("#" + %s)})})})(jQuery);', $url, $element));

        return $panel->insert($header)->insert($tabs);
    }

    public function setUserNotifications(\Nethgui\Model\UserNotifications $n)
    {
        $this->notifications = $n;
        return $this;
    }

    public function setSystemTasks(\Nethgui\Model\SystemTasks $t)
    {
        $this->systemTasks = $t;
        return $this;
    }

    public function getDependencySetters()
    {
        return array('UserNotifications' => array($this, 'setUserNotifications'), 'SystemTasks' => array($this, 'setSystemTasks'));
    }

}
