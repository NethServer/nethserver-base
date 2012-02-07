<?php
namespace NethServer\Module\User;

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
use Nethgui\Controller\Table\Modify as Table;

/**
 * @todo describe class
 */
class Modify extends \Nethgui\Controller\Table\Modify
{

    public function __construct($identifier)
    {
        if (in_array($identifier, array('create', 'update'))) {
            $viewTemplate = 'NethServer\Template\User\Modify';
        } elseif ($identifier == 'delete') {
            $viewTemplate = 'Nethgui\Template\Table\Delete';
        }

        $parameterSchema = array(
            array('username', Validate::USERNAME, Table::KEY),
            array('PasswordSet', Validate::ANYTHING, Table::FIELD),
            array('FirstName', Validate::NOTEMPTY, Table::FIELD),
            array('LastName', Validate::NOTEMPTY, Table::FIELD),
            array('Company', Validate::ANYTHING, Table::FIELD),
            array('Dept', Validate::ANYTHING, Table::FIELD),
            array('Street', Validate::ANYTHING, Table::FIELD),
            array('City', Validate::ANYTHING, Table::FIELD),
            array('Phone', Validate::ANYTHING, Table::FIELD),
        );

        parent::__construct($identifier, $parameterSchema, $viewTemplate);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        if ($this->getRequest()->isMutation() && $this->getIdentifier() == 'create' && ! $report->hasValidationErrors()) {
            /*
             * At this point the username parameter has passed the grammatical check
             */
            if ($this->systemUsernameExists($this->parameters['username'])) {
                $report->addValidationErrorMessage($this, 'username', 'The system user "${0}" already exists!', array('${0}' => $this->parameters['username']));
            }
        }
    }

    /**
     * @todo Fix the
     * @param type $username
     * @return type
     */
    private function systemUsernameExists($username)
    {
        $exitInfo = $this->getPlatform()->exec('/usr/bin/id ${1}', array($username));
        return $exitInfo->getExitCode() === 0;
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);

        $groupsAdapter = new MembershipAdapter($this->parameters['username'], $this->getPlatform());
        $this->declareParameter('Groups', Validate::USERNAME_COLLECTION, $groupsAdapter);
        $this->declareParameter('GroupsDatasource', FALSE, array($groupsAdapter, 'provideGroupsDatasource'));

        /*
         * Having declared Groups parameter after "bind()" call we now perform
         * the value assignment by hand.
         */
        if ($request->isMutation() && $request->hasParameter('Groups')) {
            $this->parameters['Groups'] = $request->getParameter('Groups');
        } elseif ( ! $request->isMutation()) {
            $this->checkOrganizationDefaults();
        }
    }

    /**
     * Read default values from "ldap" settings for each missing "organization" field value
     */
    private function checkOrganizationDefaults()
    {
        $ldapDefaults = $this->getPlatform()->getDatabase('configuration')->getKey('ldap');

        $keyMap = array(
            'Company' => 'defaultCompany',
            'Dept' => 'defaultDepartment',
            'Street' => 'defaultStreet',
            'City' => 'defaultCity',
            'Phone' => 'defaultPhoneNumber'
        );

        foreach ($keyMap as $key => $defaultKey) {
            if (empty($this->parameters[$key])) {
                $this->parameters[$key] = $ldapDefaults[$defaultKey];
            }
        }
    }

    /**
     * Do not really delete the $key, but change its type.
     * @param string $key
     */
    protected function processDelete($key)
    {
        $accountDb = $this->getPlatform()->getDatabase('accounts');
        $accountDb->setType($key, 'user-deleted');
        $this->getPlatform()->signalEvent('delete', array($key));
        parent::processDelete($key);
    }

    protected function onParametersSaved($changedParameters)
    {
        if ($this->getIdentifier() !== 'delete') {
            $this->getPlatform()->signalEvent(sprintf('user-%s@post-process', $this->getIdentifier()), array($this->parameters['username']));
        }
    }

}

