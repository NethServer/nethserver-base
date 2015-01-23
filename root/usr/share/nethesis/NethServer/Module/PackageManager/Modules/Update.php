<?php

namespace NethServer\Module\PackageManager\Modules;

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
 * Description of Update
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class Update extends \Nethgui\Controller\AbstractController implements \Nethgui\Component\DependencyConsumer
{

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->exec('/usr/bin/sudo /sbin/e-smith/pkgaction --update \*', array(), TRUE);
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $view['updates'] = $this->getParent()->getYumUpdates();
        $view['updates_count'] = count($view['updates']);
        $view['changelog'] = $this->getParent()->getYumChangelog();

        if ($view['updates_count'] > 0) {
            $this->notifications->warning($view->translate('updates_available_message', array('updates_count' => $view['updates_count'])));
        }

        if ($this->getRequest()->hasParameter('updateSuccess')) {
            $this->notifications->message($view->translate('update_success_message', array('updates_count' => $view['updates_count'])));
        }
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->setDetachedProcessCondition('success', array(
                'location' => array(
                    'url' => $view->getModuleUrl('../Update?updateSuccess'),
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
