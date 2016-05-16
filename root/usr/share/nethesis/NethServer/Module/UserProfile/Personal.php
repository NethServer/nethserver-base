<?php
namespace NethServer\Module\UserProfile;

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

use Nethgui\System\PlatformInterface as Validate;

/**
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Personal extends \Nethgui\Controller\AbstractController implements \Nethgui\Component\DependencyConsumer
{

    private $userName;

    public function initialize()
    {
        $this->declareParameter('FullName', FALSE);
        $this->declareParameter('EmailAddress', Validate::ANYTHING, array('configuration', 'root', 'EmailAddress'));
        parent::initialize();
    }

    protected function onParametersSaved()
    {
        if($this->userName === 'root') {
            $this->getPlatform()->signalEvent('profile-modify@post-process', array('root'));
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['username'] = $this->userName;
        $view['FullName'] = $this->getPlatform()->getDatabase('NethServer::Database::Passwd')->getProp($this->userName, 'gecos');
        $view['ChangePassword'] = $view->getModuleUrl('../ChangePassword');
    }

    public function setUser(\Nethgui\Authorization\UserInterface $u)
    {
        $this->user = $u;
        $this->userName = $u->getCredential('username');
        return $this;
    }

    public function getDependencySetters()
    {
        return array(
            'User' => array($this, 'setUser')
        );
    }

}