<?php

namespace NethServer\Module\FirstConfigWiz;

/*
 * Copyright (C) 2014  Nethesis S.r.l.
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
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.6
 */
class RootPassword extends \NethServer\Tool\ChangePassword implements \Nethgui\Component\DependencyConsumer
{

    const WIZARD_POSITION = 20;

    public $wizardPosition = self::WIZARD_POSITION;

    /**
     *
     * @var UserInterface;
     */
    private $user;

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return new \NethServer\Tool\CustomModuleAttributesProvider($attributes, array('languageCatalog' => 'NethServer_Module_UserProfile'));
    }

    public function initialize()
    {
        parent::initialize();
        $this->setAdapter($this->getPlatform()->getTableAdapter('accounts', 'user'));
        $this->wizardPosition = array($this, 'getWizardPosition');
    }

    /**
     *  show this page only if the root user has still the default password:
     *  @return mixed The integer position, or NULL if this page must be hidden
     */
    public function getWizardPosition() {
        return $this->user->getCredential('hasDefaultPassword') === TRUE ? self::WIZARD_POSITION : NULL;
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        if ( ! $request->getUser()->getCredential('username') === 'root') {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1417136236);
        }
        $this->setUserName('root');
        parent::bind($request);
        $this->stash->setAutoUnlink(FALSE);
    }

    public function process()
    {
        if ($this->getRequest()->isMutation()) {
            $this->getParent()->storeAction(array(
                'message' => array(
                    'module' => $this->getIdentifier(),
                    'id' => 'RootPassword_Action',
                    'args' => array()
                ),
                'events' => array(sprintf('password-modify %s %s', 'root', $this->stash->getFilePath()))
            ));
        }
    }

    public function nextPath()
    {
        if ($this->getRequest()->hasParameter('skip') || $this->getRequest()->isMutation()) {
            $successor = $this->getParent()->getSuccessor($this);
            return $successor ? $successor->getIdentifier() : 'Review';
        }
        return parent::nextPath();
    }

    public function getDependencySetters()
    {
        $user = &$this->user;
        return array(
          'User' => function($u) use (&$user) {
                $user = $u;
          }
        );
    }

}
