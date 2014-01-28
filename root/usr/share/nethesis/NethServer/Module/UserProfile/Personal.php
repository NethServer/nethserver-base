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
class Personal extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        $this->declareParameter('FirstName', Validate::ANYTHING, array($this->getAdapter(), 'FirstName'));
        $this->declareParameter('LastName', Validate::ANYTHING, array($this->getAdapter(), 'LastName'));
        $this->declareParameter('EmailAddress', $this->createValidator()->orValidator($this->createValidator(Validate::EMAIL), $this->createValidator()->maxLength(0)), array($this->getAdapter(), 'EmailAddress'));

        $this->declareParameter('Company', Validate::ANYTHING, array($this->getAdapter(), 'Company'));
        $this->declareParameter('Dept', Validate::ANYTHING, array($this->getAdapter(), 'Dept'));
        $this->declareParameter('City', Validate::ANYTHING, array($this->getAdapter(), 'City'));
        $this->declareParameter('Street', Validate::ANYTHING, array($this->getAdapter(), 'Street'));
        $this->declareParameter('Phone', Validate::ANYTHING, array($this->getAdapter(), 'Phone'));

        parent::initialize();
    }

    protected function onParametersSaved($changedParameters)
    {
        parent::onParametersSaved($changedParameters);
        $this->getPlatform()->signalEvent('profile-modify@post-process', array($this->getAdapter()->getKeyValue()));
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['username'] = $this->getAdapter()->getKeyValue();
        $view['ChangePassword'] = $view->getModuleUrl('../ChangePassword');

        $defaults = array_merge(array(
            'Company' => '',
            'Department' => '',
            'City' => '',
            'Street' => '',
            'PhoneNumber' => '',
            ), $this->getPlatform()->getDatabase('configuration')->getKey('OrganizationContact')
        );
        $view['Default_Company'] = $defaults['Company'];
        $view['Default_Dept'] = $defaults['Department'];
        $view['Default_City'] = $defaults['City'];
        $view['Default_Street'] = $defaults['Street'];
        $view['Default_Phone'] = $defaults['PhoneNumber'];
    }

}