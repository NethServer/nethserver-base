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
    protected $stash;

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
        $this->stash->setAutoUnlink(TRUE);
    }

    protected function setUserName($userName)
    {
        $this->userName = $userName;
        return $this;
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('newPassword', Validate::ANYTHING);
        $this->declareParameter('confirmNewPassword', Validate::ANYTHING);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $currentUser = $this->getRequest()->getUser()->getCredential('username');

        if (isset($this->userName)) {
            $userExists = strlen($this->userName) > 0 && ($this->userName === 'root' || $this->getPlatform()->getDatabase('NethServer::Database::Passwd')->getType($this->userName) === 'passwd');
            if ( ! $userExists) {
                throw new \Nethgui\Exception\HttpException('Not found' . $this->userName, 404, 1322148399);
            }
            // The resource the current user is acting on is another user or
            // the current user oneself.  The policy decision point is delegated
            // to decide whether the current user has enough rights to change
            // the other user's password.  Refs #1580
            $resource = $currentUser === $this->userName ? 'Oneself' : 'SomeoneElse';
        } else {
            $this->userName = $currentUser;
            $resource = 'Oneself';
        }

        $response = $this->getPolicyDecisionPoint()->authorize($this->getRequest()->getUser(), $resource, self::ACTION_CHANGE_PASSWORD);
        if ($response->isDenied()) {
            throw $response->asException(1354619038);
        } elseif ($request->isMutation()) {
            $this->getLog()->notice(sprintf("%s: %s is changing password to %s (%s). %s", __CLASS__, $currentUser, $resource, $this->userName, $response->getMessage()));
            $this->stash->store($this->parameters['newPassword']);
        }
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        if ($report->hasValidationErrors() || ! $this->getRequest()->isMutation()) {
            return;
        }

        $passwordValidator = $this->getPlatform()->createValidator()->platform('password-strength', 'Users');

        if ($this->parameters['newPassword'] !== $this->parameters['confirmNewPassword']) {
            $report->addValidationErrorMessage($this, 'confirmNewPassword', 'ConfirmNoMatch_label');
        } elseif( ! $passwordValidator->evaluate($this->stash->getFilePath())) {
            $report->addValidationError($this, 'newPassword', $passwordValidator);
        }

    }

    private function getRealUser( $user )
    {
        $passwd = file('/etc/passwd');
        foreach ($passwd as $line) {
            if (preg_match("/^$user:/", $line)) { # the user is from passwd
                return $user;
            }
        }
        # return the full user name
        return $user.'@'.$this->getPlatform()->getDatabase('configuration')->getType('DomainName');
    }

    public function process()
    {
        if ($this->getRequest()->isMutation()) {
            $this->stash->setAutoUnlink(FALSE);
            $user = $this->getRealUser($this->userName);
            $this->getPlatform()->signalEvent('password-modify', array($user, $this->stash->getFilePath()));
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['username'] = $this->userName;
    }

}
