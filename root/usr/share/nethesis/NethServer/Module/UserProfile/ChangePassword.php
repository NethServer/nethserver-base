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

/**
 * Change the current user password
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class ChangePassword extends \NethServer\Tool\ChangePassword
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('oldPassword', \Nethgui\System\PlatformInterface::ANYTHING);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        if ($this->getRequest()->isMutation()) {
            $v = new \NethServer\Tool\PamValidator();
            $v->setLog($this->getLog());
            $v->setPhpWrapper($this->getPhpWrapper());
            if ( ! $v->evaluate(array($this->getRequest()->getUser()->getCredential('username'), $this->parameters['oldPassword']))) {
                $report->addValidationError($this, 'oldPassword', $v);
            }
        }
    }

    public function nextPath()
    {
        return $this->getRequest()->isMutation() ? 'Personal' : parent::nextPath();
    }

}
