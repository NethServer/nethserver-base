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
 * Description of EditModule
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class EditModule extends \Nethgui\Controller\AbstractController
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('name', \Nethgui\System\PlatformInterface::ANYTHING);
        $this->declareParameter('components', \Nethgui\System\PlatformInterface::ANYTHING_COLLECTION);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        if ( ! $this->getRequest()->isMutation()) {
            $this->parameters['name'] = \Nethgui\array_head($request->getPath());
            $this->parameters['components'] = $this->getInstalledComponents();
        }
    }

    private function getComponentsDatasourceViewValue(\Nethgui\View\ViewInterface $view)
    {
        return array_map(function ($item) {
            return array($item, $item);
        }, $this->getAvailableComponents());
    }

    private function getAvailableComponents()
    {
        $groups = $this->getParent()->yumGroupsLoader();
        if ( ! isset($groups[$this->parameters['name']])) {
            return array();
        }
        return array_keys($groups[$this->parameters['name']]['opackages']);
    }

    private function getInstalledComponents()
    {
        $groups = $this->getParent()->yumGroupsLoader();
        if ( ! isset($groups[$this->parameters['name']])) {
            return array();
        }
        return array_keys(array_filter($groups[$this->parameters['name']]['opackages']));
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $args = array();

            $installComponents = is_array($this->parameters['components']) ? $this->parameters['components'] : array();
            $removeComponents = array_diff($this->getAvailableComponents(), $installComponents);

            if (count($installComponents) > 0) {
                $args[] = '--install';
                $args[] = implode(',', $installComponents);
            }

            if (count($removeComponents) > 0) {
                $args[] = '--remove';
                $args[] = implode(',', $removeComponents);
            }

            if (count($args) > 0) {
                $this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/pkgaction ${@}', $args, TRUE);
            }
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        if ($this->getRequest()->isValidated()) {
            $view['componentsDatasource'] = $this->getComponentsDatasourceViewValue($view);
            $view->getCommandList()->show();
        }

    }

    public function nextPath()
    {
        if ($this->getRequest()->isMutation()) {
            return './Modules';
        }
        return parent::nextPath();
    }

}
