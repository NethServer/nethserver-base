<?php

namespace NethServer\Module\PackageManager;

/*
 * Copyright (C) 2016 Nethesis Srl
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
 * Description of ClearYumCache
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class ClearYumCache extends \Nethgui\Controller\AbstractController
{

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->exec('/usr/bin/sudo /usr/bin/yum clean all');
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $this->absoluteModulePath = '/' . implode('/', array_slice($view->getModulePath(), 0, -1));
    }

    public function nextPath()
    {
        if ($this->getRequest()->isMutation()) {
            return $this->absoluteModulePath . '/Modules';
        }
        return FALSE;
    }

}
