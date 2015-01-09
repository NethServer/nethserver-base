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
class RootPassword extends \NethServer\Tool\ChangePassword
{

    public $wizardPosition = 20;

    const DEFAULT_PASSWORD = 'Nethesis,1234';

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return new \NethServer\Tool\CustomModuleAttributesProvider($attributes, array('languageCatalog' => 'NethServer_Module_UserProfile'));
    }

    public function initialize()
    {
        parent::initialize();
        $this->setAdapter($this->getPlatform()->getTableAdapter('accounts', 'user'));
        if ( ! $this->hasDefaultPassword()) {
            $this->wizardPosition = NULL;
        }
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        if ( ! $request->getUser()->getCredential('username') === 'root') {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1417136236);
        }
        $this->setUserName('root');
        parent::bind($request);
    }

    public function process()
    {
        if ($this->getRequest()->isMutation()) {
            $this->stash->setAutoUnlink(FALSE)->store($this->parameters['newPassword']);
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

    private function hasDefaultPassword()
    {

        $sessDb = $this->getPlatform()->getDatabase('SESSION');
        if ( ! $sessDb->getType(__CLASS__)) {
            $v = new \NethServer\Tool\PamValidator('root');
            if ($v->evaluate(self::DEFAULT_PASSWORD)) {
                $sessDb->setType(__CLASS__, 'mustchangepw');
            } else {
                $sessDb->setType(__CLASS__, 'pwchanged');
            }
        }
        return $sessDb->getType(__CLASS__) === 'mustchangepw';
    }

    public function nextPath()
    {
        if ($this->getRequest()->hasParameter('skip') || $this->getRequest()->isMutation()) {
            $successor = $this->getParent()->getSuccessor($this);
            return $successor ? $successor->getIdentifier() : 'Review';
        }
        return parent::nextPath();
    }

}
