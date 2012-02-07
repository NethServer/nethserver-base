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

/**
 * Change password for a specific user
 *
 * @todo Run a system validator to check the password quality
 */
class ChangePassword extends \Nethgui\Controller\Table\AbstractAction
{

    /**
     *
     * @var PasswordStash
     */
    private $stash;

    public function __construct(PasswordStash $stash, $identifier = NULL)
    {
        parent::__construct($identifier);
        $this->stash = $stash;
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('newPassword', $this->createValidator()->minLength(8));
        $this->declareParameter('confirmNewPassword', Validate::ANYTHING);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $this->parameters['username'] = \Nethgui\array_end($request->getPath());

        // FIXME Check if user exists
        if ( ! $this->parameters['username']) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1322148399);
        }
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        if ( ! $report->hasValidationErrors()) {
            if ($this->parameters['newPassword'] !== $this->parameters['confirmNewPassword']) {
                $report->addValidationErrorMessage($this, 'confirmNewPassword', 'Password confirmation does not match');
            }
        }
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $this->stash->store($this->parameters['newPassword']);
            $this->getPlatform()->signalEvent('password-modify@post-process', array($this->parameters['username'], $this->stash->getFilePath()));
        }
    }

}
