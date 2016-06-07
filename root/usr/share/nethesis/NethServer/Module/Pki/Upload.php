<?php

namespace NethServer\Module\Pki;

/*
 * Copyright (C) 2016 Nethesis Srl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of Upload
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class Upload extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('UploadName', \Nethgui\System\PlatformInterface::HOSTNAME_SIMPLE);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        if( ! $this->getRequest()->isMutation()) {
            return;
        }

        $crtValidator = $this->createValidator()->platform('pem-certificate');
        $keyValidator = $this->createValidator()->platform('rsa-key');

        if( ! $crtValidator->evaluate($_FILES['crt']['tmp_name'])) {
            $report->addValidationError($this, 'UploadCrt', $crtValidator);
        }

        if( ! $keyValidator->evaluate($_FILES['key']['tmp_name'])) {
            $report->addValidationError($this, 'UploadKey', $keyValidator);
        }

    }

    public function process()
    {
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->signalEvent('certificate-upload', array($this->parameters['UploadName'], $_FILES['crt']['tmp_name'], $_FILES['key']['tmp_name'], $_FILES['chain']['tmp_name']));
        }
    }

}
