<?php

namespace NethServer\Module\Pki;

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
class Generate extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $validator = $this->createValidator()->regexp('/^[^\/,]+$/', 'valid_x509_field');
        $this->declareParameter('C', $this->orEmptyValidator($this->createValidator()->maxLength(2)->minLength(2)), array('configuration', 'pki', 'CountryCode'));
        $this->declareParameter('ST', $this->orEmptyValidator($validator), array('configuration', 'pki', 'State'));
        $this->declareParameter('L', $this->orEmptyValidator($validator), array('configuration', 'pki', 'Locality'));
        $this->declareParameter('O', $this->orEmptyValidator($validator), array('configuration', 'pki', 'Organization'));
        $this->declareParameter('OU', $this->orEmptyValidator($validator), array('configuration', 'pki', 'OrganizationalUnitName'));
        $this->declareParameter('CN', $this->orEmptyValidator($validator), array('configuration', 'pki', 'CommonName'));
        $this->declareParameter('EmailAddress', $this->orEmptyValidator(\Nethgui\System\PlatformInterface::EMAIL), array('configuration', 'pki', 'EmailAddress'));
        $this->declareParameter('SubjectAltName', $this->createValidator(\Nethgui\System\PlatformInterface::ANYTHING), array('configuration', 'pki', 'SubjectAltName'));
        $this->declareParameter('CertificateDuration', $this->createValidator()->greatThan(10), array('configuration', 'pki', 'CertificateDuration'));
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);

        $defaults = $this->getCertDefaults();
        foreach ($request->getParameterNames() as $p) {
            if ( ! isset($defaults[$p . '_default'])) {
                continue;
            }
            if ( ! $this->parameters[$p]) {
                $this->parameters[$p] = $defaults[$p . '_default'];
            }
        }
    }

    public function readSubjectAltName($v)
    {
        return implode("\n", explode(",", $v));
    }

    public function writeSubjectAltName($p)
    {
        return array(implode(',', array_filter(preg_split("/[,\s]+/", $p))));
    }

    private function orEmptyValidator($v)
    {
        if ($v instanceof \Nethgui\System\ValidatorInterface) {
            return $this->createValidator()->orValidator($v, $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING));
        }
        return $this->createValidator()->orValidator($this->createValidator($v), $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING));
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        $hostNames = array_filter(preg_split("/[,\s]+/", $this->parameters['SubjectAltName']), function ($v) {
            return preg_match("/[a-zA-Z]/", $v);
        });

        $validator = $this->createValidator()->hostname(1);

        foreach ($hostNames as $origHostName) {
            $hostName = trim($origHostName);
            if (substr($hostName, 0, 2) === '*.') {
                $hostName = substr($hostName, 2);
            }
            if ( ! $validator->evaluate($hostName)) {
                $report->addValidationErrorMessage($this, 'SubjectAltName', 'valid_hostname_instance', array($origHostName));
                break;
            }
        }
    }

    private function getCertDefaults()
    {
        static $defaults;

        if ( ! isset($defaults)) {
            $cdb = $this->getPlatform()->getDatabase('configuration');

            $org = $cdb->getKey('OrganizationContact');
            $adm = $cdb->getKey('root');

            $defaults = array(
                'C_default' => isset($org['CountryCode']) && $org['CountryCode'] ? $org['CountryCode'] : '--',
                'ST_default' => isset($org['State']) && $org['State'] ? $org['State'] : 'SomeState',
                'L_default' => isset($org['City']) && $org['City'] ? $org['City'] : 'Hometown',
                'O_default' => isset($org['Company']) && $org['Company'] ? $org['Company'] : 'Example',
                'OU_default' => isset($org['Department']) && $org['Department'] ? $org['Department'] : 'Main',
                'CN_default' => 'NethServer', 
                'EmailAddress_default' => isset($adm['EmailAddress']) && $adm['EmailAddress'] ? $adm['EmailAddress'] : '',
                'SubjectAltName_default' => sprintf('*.%s', $cdb->getType('DomainName')),
            );
        }

        return $defaults;
    }

    public function process()
    {
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->signalEvent('certificate-update &');
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view->copyFrom($this->getCertDefaults());
    }

}
