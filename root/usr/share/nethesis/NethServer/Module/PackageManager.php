<?php
namespace NethServer\Module;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
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
 * Manage group and package installation/removal
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class PackageManager extends \Nethgui\Controller\TableController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($attributes, 'Configuration', 20);
    }

    public function initialize()
    {
        $adapter = new PackageManager\YumAdapter($this->getPlatform());

        $columns = array(
            'Name',
            'Status',
            'Actions'
        );

        $this
            ->setTableAdapter($adapter)
            ->setColumns($columns)
            ->addRowAction(new PackageManager\Operation('Add'))
            ->addRowAction(new PackageManager\Operation('Remove'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addChild(new PackageManager\StatusTracker())
        ;

        parent::initialize();
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $this->getAdapter()->setLanguage($request->getUser()->getLanguageCode());
        parent::bind($request);
    }

    public function prepareViewForColumnActions(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $cellView = $action->prepareViewForColumnActions($view, $key, $values, $rowMetadata);

        if ($values['Status'] === 'available') {
            unset($cellView['Remove']);
        } else {
            unset($cellView['Add']);
        }

        return $cellView;
    }

    public function prepareViewForColumnStatus(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        return $view->translate($values['Status']);
    }

}
