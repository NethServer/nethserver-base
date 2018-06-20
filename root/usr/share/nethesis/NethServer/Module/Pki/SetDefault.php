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
 * Description of SetDefault
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class SetDefault extends \Nethgui\Controller\Table\RowAbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $parameterSchema = array(
            array('name', FALSE, \Nethgui\Controller\Table\Modify::KEY),
        );
        $this->setSchema($parameterSchema);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        $certificates = $this->getCertificates();
        $key = $this->getCertPath();
        if(isset($certificates[$key]['key'])) {
            $tlsPolicy = $this->getPlatform()->getDatabase('configuration')->getProp('tls', 'policy');
            $validator = $this->createValidator()->platform('tlspolicy-safetyguard', $certificates[$key]['key']);
            if( ! $validator->evaluate($tlsPolicy)) {
                $report->addValidationError($this, 'cert_safetyguard', $validator);
            }
        }
    }

    private function getCertPath()
    {
        return '/' . implode('/', $this->getRequest()->getPath());
    }
    
    private function getCertificates()
    {
        static $certificates;
        if( ! isset($certificates)) {
            $certificates = json_decode($this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/cert-list')->getOutput(), TRUE);
        }
        return $certificates;
    }

    public function process()
    {
        if ( $this->getRequest()->isMutation()) {
            $db = $this->getPlatform()->getDatabase('configuration');
            $name = $this->getCertPath();
            foreach ($this->getCertificates() as $key => $props) {
                 if ($key == $name) {
                      $db->setProp('pki', array('CrtFile' => $props['file'], 'KeyFile' => $props['key'], 'ChainFile' => $props['chain']));
                      $this->getPlatform()->signalEvent('certificate-update &');
                 }
            }
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if($this->getRequest()->isValidated()) {
            $view['cert'] = $this->getCertPath();
        }
    }
}
