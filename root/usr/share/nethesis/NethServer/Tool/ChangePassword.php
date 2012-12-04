<?php
namespace NethServer\Tool;

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

/**
 * Change password for a specific user
 *
 * @todo Run a system validator to check the password quality
 */
class ChangePassword extends \Nethgui\Controller\Table\AbstractAction
{
    /**
     * @var \NethServer\Tool\PasswordStash
     */
    private $stash;

    /**
     * The user we want to change the password to
     * @var string
     */
    private $userName;

    const ACTION_CHANGE_PASSWORD = 'CHANGE_PASSWORD';

    public function __construct($identifier = NULL)
    {
        parent::__construct($identifier);
        $this->stash = new \NethServer\Tool\PasswordStash();
    }

    protected function setUserName($userName)
    {
        $this->userName = $userName;
        return $this;
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('newPassword', $this->getPlatform()->createValidator()->platform('password-strength', 'Users'));
        $this->declareParameter('confirmNewPassword', Validate::ANYTHING);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);

        $userExists = strlen($this->userName) > 0
            && ($this->userName === 'admin'
            || $this->getPlatform()->getDatabase('accounts')->getType($this->userName) === 'user');

        if ( ! $userExists) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1322148399);
        }

        // The resource the current user is acting on is another user or
        // the current user oneself.  The policy decision point is delegated
        // to decide whether the current user has enough rights to change
        // the other user's password.  Refs #1580
        $currentUser = $this->getRequest()->getUser()->getCredential('username');
        $resource = $currentUser === $this->userName ? 'Oneself' : 'SomeoneElse';

        $response = $this->getPolicyDecisionPoint()->authorize($this->getRequest()->getUser(), $resource, self::ACTION_CHANGE_PASSWORD);
        if ($response->isDenied()) {
            throw $response->asException(1354619038);
        } elseif ($request->isMutation()) {
            $this->getLog()->notice(sprintf("%s: %s is changing password to %s (%s). %s", __CLASS__, $currentUser, $resource, $this->userName, $response->getMessage()));
        }
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        if ( ! $report->hasValidationErrors()) {
            if ($this->parameters['newPassword'] !== $this->parameters['confirmNewPassword']) {
                $report->addValidationErrorMessage($this, 'confirmNewPassword', 'ConfirmNoMatch_label');
            }
        }
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $this->stash->store($this->parameters['newPassword']);
            $this->getPlatform()->signalEvent('password-modify@post-process', array($this->userName, $this->stash->getFilePath()));
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['username'] = $this->userName;
    }

}
