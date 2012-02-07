<?php
namespace NethServer\Module;

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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Manage system user groups
 * 
 * @link http://redmine.nethesis.it/issues/185
 */
class Group extends \Nethgui\Controller\TableController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Management', 20);
    }

    public function initialize()
    {
        $columns = array(
            'Key',
            'Description',
            'Actions',
        );

        $parameterSchema = array(
            array('groupname', Validate::USERNAME, \Nethgui\Controller\Table\Modify::KEY),
            array('Description', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD, 'Description'),
            array('Members', Validate::USERNAME_COLLECTION, \Nethgui\Controller\Table\Modify::FIELD, 'Members', ','),
            array('MembersDatasource', FALSE, array($this, 'provideMembersDatasource')), // this parameter will never be submitted: set an always-failing validator
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('accounts', 'group'))
            ->setColumns($columns)
            ->addTableAction(new \Nethgui\Controller\Table\Modify('create', $parameterSchema, 'NethServer\Template\Group\Modify'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \Nethgui\Controller\Table\Modify('update', $parameterSchema, 'NethServer\Template\Group\Modify'))
            ->addRowAction(new \Nethgui\Controller\Table\Modify('delete', $parameterSchema, 'Nethgui\Template\Table\Delete'))
        ;

        parent::initialize();
    }

    public function provideMembersDatasource()
    {
        $platform = $this->getPlatform();
        if (is_null($platform)) {
            return array();
        }

        $users = $platform->getTableAdapter('accounts', 'user');

        $values = array();

        // Build the datasource rows couples <key, label>
        foreach ($users as $username => $row) {
            $values[] = array($username, sprintf('%s %s (%s)', $row['FirstName'], $row['LastName'], $username));
        }

        return $values;
    }

    public function onParametersSaved(\Nethgui\Module\ModuleInterface $currentAction, $changes)
    {
        $this->getPlatform()->signalEvent(sprintf('group-%s@post-process', $currentAction->getIdentifier()), $this->parameters['groupname']);
    }

}
