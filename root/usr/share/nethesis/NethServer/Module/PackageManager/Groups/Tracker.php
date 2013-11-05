<?php
namespace NethServer\Module\PackageManager\Groups;

/*
 * Copyright (C) 2013 Nethesis S.r.l.
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
 * @since 1.0
 */
class Tracker extends \NethServer\Tool\Tracker
{
    private $failedEvents = array();

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        if ( ! $this->getRequest()->isValidated()) {
            return;
        }

        $view['failedEvents'] = array();
        $view['message'] = '';

        $view->getCommandList()->show();

        $state = $this->getProgress();
        if (is_array($state)) {
            $view['progress'] = intval(100 * $state['progress']);

            $parts = array();
            if ( ! empty($state['last']['title']) && ! empty($state['last']['id'])) {
                $parts[] = $view->translate($state['last']['title']);
            }
            if ( ! empty($state['last']['message'])) {
                $parts[] = $view->translate($state['last']['message']);
            }
            $view['message'] = implode(' - ', $parts);
        } else {
            $view['progress'] = FALSE;
        }
        $view['exitCode'] = $this->getExitCode();
        $view['FormAction'] = $view->getModuleUrl($this->getTaskId());

        if ($this->getExitCode() === FALSE) {
            // Still running
            $view->getCommandList()->reloadData(4000);
        } else {
            $this->findFailedEvents(array('children' => $this->getTasks()), $this->failedEvents);
            if (empty($this->failedEvents)) {
                $view->getCommandList('/Notification')->showMessage($view->translate("package_success"), \Nethgui\Module\Notification\AbstractNotification::NOTIFY_SUCCESS);
            } else {
                $message = $view->translate('Failed_events_label', array(
                    count($this->failedEvents)));
                $view['failedEvents'] = $this->failedEvents;
                $view['message'] = $message;
                $view->getCommandList('/Notification')->showMessage($message, \Nethgui\Module\Notification\AbstractNotification::NOTIFY_ERROR);
            }
        }
    }

    private function findFailedEvents($currentTask, &$failures)
    {
        if ( ! is_array($currentTask['children'])) {
            return;
        }
        foreach ($currentTask['children'] as $task) {
            if ($task['code'] != 0 && substr($task['title'], 0, 5) == 'Event') {
                $failures[] = $task['title'];
            }
            $this->findFailedEvents($task, $failures);
        }
    }

    public function nextPath()
    {
        return ($this->getExitCode() === FALSE) ? FALSE : '/PackageManager/Groups/Select';
    }

}