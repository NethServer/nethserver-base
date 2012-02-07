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

/**
 * @todo describe class
 * 
 * @link http://redmine.nethesis.it/issues/185
 */
class User extends \Nethgui\Controller\TableController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\CompositeModuleAttributesProvider::extendModuleAttributes($base, 'Management', 10)->extendFromComposite($this);
    }

    public function initialize()
    {
        $columns = array(
            'Key',
            'FirstName',
            'LastName',
            'Actions',
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('accounts', 'user'))
            ->setColumns($columns)
            ->addTableAction(new User\Modify('create'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new User\Update())
            ->addRowAction(new User\ChangePassword(new User\PasswordStash(), 'change-password'))
            ->addRowAction(new User\ToggleLock('lock'))
            ->addRowAction(new User\ToggleLock('unlock'))
            ->addRowAction(new User\Modify('delete'))
        ;

        parent::initialize();
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if ( ! isset($values['PasswordSet']) || $values['PasswordSet'] == "no") {
            $rowMetadata['rowCssClass'] = ' nopasswd ';
            $rowMetadata['rowCssClass'] .= ' locked ';
        }
        if (isset($values['Locked']) && $values['Locked'] == 'yes') {
            $rowMetadata['rowCssClass'] .= ' locked ';
        }

        return strval($key);
    }

    /**
     * Override prepareViewForColumnActions to hide/show lock/unlock actions
     * @param \Nethgui\View\ViewInterface $view
     * @param string $key The data row key
     * @param array $values The data row values
     * @return \Nethgui\View\ViewInterface 
     */
    public function prepareViewForColumnActions(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $cellView = $action->prepareViewForColumnActions($view, $key, $values, $rowMetadata);
        if (stripos($rowMetadata['rowCssClass'], 'nopasswd') !== false) {
            unset($cellView['lock']);
            unset($cellView['unlock']);
        } else if (stripos($rowMetadata['rowCssClass'], 'locked') !== false) { //hide lock action
            unset($cellView['lock']);
        } else {
            unset($cellView['unlock']); //hide unlock action
        }
        return $cellView;
    }

}
