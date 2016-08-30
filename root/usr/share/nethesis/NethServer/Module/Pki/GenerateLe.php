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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Generate a new Letsencrypt certificate
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class GenerateLe extends \Nethgui\Controller\AbstractController
{

    public function initialize()
    {
        $mailValidator = $this->createValidator()->orValidator($this->createValidator(Validate::EMAIL), $this->createValidator(Validate::EMPTYSTRING));
        parent::initialize();
        $this->declareParameter('LetsEncryptMail', $mailValidator, array('configuration', 'pki', 'LetsEncryptMail'));
        $this->declareParameter('LetsEncryptDomains', Validate::ANYTHING, array('configuration', 'pki', 'LetsEncryptDomains'));
    }

    public function readLetsEncryptDomains($v)
    {
        return implode("\n", explode(",", $v));
    }

    public function writeLetsEncryptDomains($p)
    {
        return array(implode(',', array_filter(preg_split("/[,\s]+/", $p))));
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        if (!$this->getRequest()->isMutation()) {
            return;
        }

        $hostnameValidator = $this->createValidator(Validate::HOSTNAME_FQDN);
        $domains=array_map('trim',preg_split('/[\s,\t\n]+/',$this->parameters['LetsEncryptDomains']));
        foreach ($domains as $domain){
            if( ! $hostnameValidator->evaluate($domain)) {
                $report->addValidationErrorMessage($this, 'LetsEncryptDomains', "Domain $domain is not a valid domain FQDN");
            }
        }
        if (! $report->hasValidationErrors()){
            //test with LetsEncrypt script. Use -d DOMAINS because LetsEncryptDomains isn't written yet
            $cmd="/usr/bin/sudo /usr/libexec/nethserver/letsencrypt-certs -t -d ".join(",",$domains);
            $this->getPlatform()->getLog()->notice("Testing LetsEncrypt: $cmd");
            $p = $this->getPlatform()->exec($cmd);
            $stderr=$p->getErrorOutput();
            $stdout=$p->getOutput();
            $ret=$p->getexitCode();
            if ($ret!=0) {
                $report->addValidationErrorMessage($this, 'LetsEncryptDomains', $stderr);
                $this->getPlatform()->getLog()->notice("Error testing LetsEncrypt: ".$stderr);
            }
        }
    }

    public function process()
    {
        parent::process();
        if (!$this->getRequest()->isMutation()) {
            return;
        }
        /*Launch LetsEncrypt helper command*/
        $p = $this->getPlatform()->exec("/usr/bin/sudo /usr/libexec/nethserver/letsencrypt-certs -f");
        $this->exitCode = $p->getExitCode();
        $this->output = $p->getOutput(true);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        if ($this->getRequest()->isValidated()) {
            $view->getCommandList()->show();
            if($view['LetsEncryptDomains'] === '') {
                $view['LetsEncryptDomains'] = \gethostname();
            }
        }
    }

    public function nextPath()
    {
        return $this->getRequest()->isMutation() ? 'read' : $this->getIdentifier();
    }
}
