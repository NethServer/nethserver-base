<?php
namespace NethServer\Tool;

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

/**
 * Validate user's password
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class PamValidator implements \Nethgui\System\ValidatorInterface, \Nethgui\Utility\PhpConsumerInterface, \Nethgui\Log\LogConsumerInterface
{
    /**
     *
     * @var string
     */
    private $username;

    /**
     *
     * @var \Nethgui\Log\LogInterface
     */
    private $log;

    /**
     *
     * @var \Nethgui\Utility\PhpWrapper
     */
    private $php;

    /**
     *
     * @var \Nethgui\Utility\PamAuthenticator
     */
    private $authenticator;
    private $failure;

    public function __construct($username = NULL)
    {
        $this->username = $username;
        $this->authenticator = new \Nethgui\Utility\PamAuthenticator();
        $this->failure = array();
    }

    /**
     * Set the user name for which the password will be checked.
     *
     * @param string $userName
     * @return \NethServer\Tool\PamValidator
     */
    public function setUserName($userName) {
        $this->username = $userName;
        return $this;
    }

    public function evaluate($value)
    {
        $credentials = array();

        if ( ! isset($this->username)) {
            throw new \LogicException(sprintf('%s: the username has not been set', __CLASS__), 1354179502);
        }

        $authenticated = $this->authenticator->authenticate($this->username, $value, $credentials);

        if ( ! $authenticated) {
            $this->failure[] = array('InvalidPassword', array($this->username));
        }

        return $authenticated;
    }

    public function getFailureInfo()
    {
        return $this->failure;
    }

    public function getLog()
    {
        if ( ! isset($this->log)) {
            $this->log = new \Nethgui\Log\Nullog();
        }
        return $this->log;
    }

    public function setLog(\Nethgui\Log\LogInterface $log)
    {
        $this->log = $log;
        return $this;
    }

    public function setPhpWrapper(\Nethgui\Utility\PhpWrapper $object)
    {
        $this->php = $object;
        return $this;
    }

}
