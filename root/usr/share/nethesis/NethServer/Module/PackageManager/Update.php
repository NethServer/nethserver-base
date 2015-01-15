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
 * Description of Update
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class Update extends \Nethgui\Controller\AbstractController implements \Nethgui\Component\DependencyConsumer
{

    private $updates = array();
    private $checkUpdateJob = array();

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $this->checkUpdateJob = $this->getPlatform()->exec('/usr/bin/sudo -n /sbin/e-smith/pkginfo check-update');
        if ($this->checkUpdateJob->getExitCode() !== 0) {
            return;
        }

        $this->updates = json_decode($this->checkUpdateJob->getOutput(), TRUE);
        if (is_array($this->updates)) {
            usort($this->updates, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        } else {
            $this->updates = array();
        }
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        if ($this->checkUpdateJob->getExitCode() !== 0) {
            $report->addValidationErrorMessage($this, 'updates', 'update_error', json_decode($this->checkUpdateJob->getOutput(), TRUE));
        }
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
        if ($this->getRequest()->isValidated()) {
            $view['updates'] = $this->updates;
            $view['updates_count'] = count($this->updates);
            $view->getCommandList()->show();
            if ($this->getRequest()->hasParameter('updateSuccess')) {
                $this->notifications->message($view->translate('update_success_message', array('updates_count' => count($this->updates))));
            }
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
