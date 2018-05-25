<?php

namespace NethServer\Module\PackageManager\Modules;

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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Groups index
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Available extends \Nethgui\Controller\Collection\AbstractAction implements \Nethgui\Component\DependencyConsumer, \Nethgui\Utility\SessionConsumerInterface
{

    /**
     *
     * @var \Nethgui\Model\UserNotifications
     */
    private $notifications;
    private $session;
    public $csrfToken;

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('groups', Validate::ANYTHING_COLLECTION);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        if (empty($this->parameters['groups'])) {
            return;
        }

        if ( ! is_array($this->parameters['groups'])) {
            $report->addValidationErrorMessage($this, 'groups', 'Invalid groups');
            return;
        }

        $allowedGroups = array_keys(iterator_to_array($this->getAdapter(), TRUE));
        $check = array_diff(array_keys($this->parameters['groups']), $allowedGroups);
        foreach ($check as $groupName) {
            $report->addValidationErrorMessage($this, 'groups', 'Invalid group name: ' . $groupName);
        }
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()
                    ->getDatabase('SESSION')
                    ->setKey(get_class($this->getParent()), 'array', array('groups' => $this->parameters['groups']));
        }
    }

    private function getGroupsViewValue(\Nethgui\View\ViewInterface $view)
    {
        static $groupsState;
        if (isset($groupsState)) {
            return $groupsState;
        }
        $groupsState = array();

        foreach (iterator_to_array($this->getAdapter()) as $id => $yumGroup) {
            if ($yumGroup['status'] === 'installed') {
                continue; // skip installed groups
            }
            if (isset($this->parameters['groups'][$id])) {
                $groupsState[$id] = $this->parameters['groups'][$id];
                
            } else {
                $groupsState[$id]['opackages_selected'] = array();
            }

            $groupsState[$id]['id'] = $yumGroup['id'];
            $groupsState[$id]['name'] = $yumGroup['name'];
            $groupsState[$id]['description'] = $yumGroup['description'];
            $groupsState[$id]['mpackages'] = array_keys($yumGroup['mpackages']);
            $groupsState[$id]['opackages_datasource'] = array_map(function ($item) {
                return array($item, $item);
            }, array_keys(array_filter($yumGroup['opackages'], function($installed) {
                                return ! $installed;
                            })));

        }

        usort($groupsState, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $groupsState;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $view['groups'] = $this->getGroupsViewValue($view);
        $view['categories'] = $this->getCategoriesViewValue($view);

        if ($this->getRequest()->isValidated()) {
            $view->getCommandList()->show();
        }
    }

    private function getCategoriesViewValue(\Nethgui\View\ViewInterface $view)
    {
        $groups = array();
        $categories = $this->getParent()->getYumCategories();

        if (count($categories) === 0) {
            return array();
        }

        foreach ($this->getAdapter() as $group) {
            $groups[] = $group['id'];
        }

        $everything = array(
            'id' => 'everything',
            'name' => $view->translate('Everything_category_label'),
            'description' => $view->translate('Everything_category_description'),
            'display_order' => 0,
            'groups' => array_unique($groups),
            'selected' => TRUE
        );

        return array_merge(array($everything), $categories);
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

    public function nextPath()
    {
        if ($this->getRequest()->isMutation()) {
            return '../Review';
        }
        return parent::nextPath();
    }

    public function setSession(\Nethgui\Utility\SessionInterface $session)
    {
        $this->session = $session;
        $security = $this->session->retrieve('SECURITY');
        $this->csrfToken = $security['csrfToken'][0];
        return $this;
    }
}
