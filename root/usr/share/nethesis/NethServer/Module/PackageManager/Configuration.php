<?php

/*
 * Copyright (C) 2018 Nethesis S.r.l.
 * http://www.nethesis.it - nethserver@nethesis.it
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see COPYING.
 */

namespace NethServer\Module\PackageManager;

class Configuration extends \Nethgui\Controller\Collection\AbstractAction implements \Nethgui\Component\DependencyConsumer
{
    public function initialize()
    {
        $this->declareParameter('NsReleaseLock', $this->createValidator()->notEmpty()->memberOf('enabled', 'disabled'), array('configuration', 'sysconfig', 'NsReleaseLock'));
    }

    public function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('software-repos-save &');
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $db = $this->getPlatform()->getDatabase('configuration');
        $view['Version'] = $db->getProp('sysconfig', 'Version');
        $view['PolicyDisabled'] = (bool) $db->getProp('subscription', 'SystemId') || @file_exists('/etc/e-smith/db/configuration/force/sysconfig/NsReleaseLock');
        $view['BackToModules'] = $view->getModuleUrl('../Modules');
        if($this->getRequest()->isValidated()) {
            $view->getCommandList()->show();
            $db = $this->getPlatform()->getDatabase('configuration');
            $nsReleaseLock = $db->getProp('sysconfig', 'NsReleaseLock');
            if( ! $nsReleaseLock) {
                $this->notifications->warning($view->translate('NsReleaseLock_policy_warning'));
            }
        }
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->setDetachedProcessCondition('success', array(
                'location' => array(
                    'url' => $view->getModuleUrl('../Modules'),
                    'freeze' => TRUE,
            )));
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
}