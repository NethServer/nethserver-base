<?php

/*
 * Copyright (C) 2018 Nethesis S.r.l.
 * http://www.nethesis.it - nethserver@nethesis.it
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see COPYING.
 */

namespace NethServer\Module\PackageManager;

class DistroUpgrade extends \Nethgui\Controller\Collection\AbstractAction
{

    public function process()
    {
        parent::process();
        if($this->getRequest()->isMutation()) {
            $this->getPlatform()->signalEvent('software-repos-upgrade &');
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $db = $this->getPlatform()->getDatabase('configuration');
        $productName = $db->getProp('sysconfig', 'ProductName');
        $sbVersion = $db->getProp('subscription', 'NsRelease');
        $view['DistroUpgradeParams'] = array('product' => $productName, 'version' => $sbVersion);
        $view['UpgradeLater'] = $view->getModuleUrl('../Modules');
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->setDetachedProcessCondition('success', array(
                'location' => array(
                    'url' => $view->getModuleUrl('../Modules?installSuccess'),
                    'freeze' => TRUE,
            )));
        }
    }

}