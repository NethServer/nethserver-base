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

    private function checkUpdates()
    {
        static $data;

        if (isset($data)) {
            return $data;
        }

        $data = array();
        $checkUpdateJob = $this->getPlatform()->exec('/usr/bin/sudo -n /sbin/e-smith/pkginfo check-update');
        if ($checkUpdateJob->getExitCode() !== 0) {
            $this->notifications->error("Error\n" . $checkUpdateJob->getOutput());
            return array();
        }
        $data = json_decode($checkUpdateJob->getOutput(), TRUE);
        return $data;
    }

    private function getUpdatesViewValue()
    {
        $data = $this->checkUpdates();

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

    private function getChangelogViewValue()
    {
        $data = $this->checkUpdates();
        return isset($data['changelog']) ? $data['changelog'] : '';
    }

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

        $view['updates'] = $this->getUpdatesViewValue();
        $view['updates_count'] = count($view['updates']);
        $view['changelog'] = $this->getChangelogViewValue();

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
