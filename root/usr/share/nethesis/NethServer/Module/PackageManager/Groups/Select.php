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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Groups index
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Select extends \Nethgui\Controller\Collection\AbstractAction implements \Nethgui\Utility\SessionConsumerInterface
{
    /**
     *
     * @var \Nethgui\Utility\SessionInterface
     */
    private $session;

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
            $txOrder = $this->prepareTransactionOrder();
            $this->session->store(get_class($this->getParent()), $txOrder);
        }
    }

    /**
     * Compare the request and the currently installed package groups, returning
     * a Serializable ArrayObject that specifies what to do.
     * 
     * @return \ArrayObject
     */
    private function prepareTransactionOrder()
    {
        $installedList = array();
        $availableList = array();

        $selectedList = array();
        $unselectedList = array();

        foreach ($this->getAdapter() as $id => $element) {
            if ($element['status'] === 'installed') {
                $installedList[] = $id;
            } else {
                $availableList[] = $id;
            }
        }

        foreach ($this->parameters['groups'] as $id => $element) {
            if ($element['status'] === 'installed') {
                $selectedList[] = $id;
            } else {
                $unselectedList[] = $id;
            }
        }

        $addList = array_diff($selectedList, $installedList);
        $removeList = array_diff($unselectedList, $availableList);
        $keepList = array_diff(array_keys($this->parameters['groups']), $addList, $removeList);

        return new \ArrayObject(array('add' => array_values($addList), 'remove' => array_values($removeList), 'keep' => array_values($keepList)));
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $values = iterator_to_array($this->getAdapter());
        usort($values, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        $view['groups'] = $values;
        if ($this->getRequest()->isValidated()) {
            $view->getCommandList()->show();
        }
    }

    public function setSession(\Nethgui\Utility\SessionInterface $session)
    {
        $this->session = $session;
        return $this;
    }

    public function nextPath()
    {
        if ($this->getRequest()->isMutation()) {
            // FIXME: Absolute path, to avoid next view "prefeching"
            return '/PackageManager/Groups/Review';
        }
        return parent::nextPath();
    }

}