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
class Review extends \Nethgui\Controller\AbstractController implements \Nethgui\Component\DependencyConsumer {

    public $wizardPosition = 200;

    /**
     *
     * @var \Nethgui\Utility\SessionInterface
     */
    private $session;

    /**
     *
     * @var string
     */
    private $defaultModule = '';

    private $redirectModule = 'NetworkAdapter';

    public function prepareView(\Nethgui\View\ViewInterface $view) {
        parent::prepareView($view);

        $changes = array();
        $steps = $this->getPlatform()->getDatabase('SESSION')->getType(get_class($this->getParent()));
        foreach ((is_array($steps) ? $steps : array()) as $job) {
            $message = $job['message'];
            $module = $this->getParent()->getAction($message['module']);
            $changes[] = $view->getTranslator()->translate($module ? $module : $this, $message['id'], $message['args']);
        }
        $view['changes'] = $changes;

        // TODO: fetch the module title instead of the module identifier
        $view['redirect']  = $view->translate('redirect_message', array('moduleTitle' => $this->redirectModule));

        if($this->getRequest()->isMutation()) {
            $this->getPlatform()->setDetachedProcessCondition('success', array(
                    'location' => array(
                        'url' => $view->getModuleUrl('../Review?redirect'),
                        'freeze' => TRUE,
                )));
        }
    }

    public function process() {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $tempname = $this->getPhpWrapper()->tempnam(FALSE, 'ng-');
            $fh = $this->getPhpWrapper()->fopen($tempname, 'w');
            if ($fh === FALSE) {
                throw new \Nethgui\Exception\HttpException(sprintf("%s: could not open a temporary file", __CLASS__), 500, 1420718703);
            }

            $steps = $this->getPlatform()->getDatabase('SESSION')->getType(get_class($this->getParent()));
            foreach ($steps as $job) {
                $this->getPhpWrapper()->fwrite($fh, implode("\n", $job['events']) . "\n");
            }
            $this->getPhpWrapper()->fclose($fh);
            $this->getPlatform()->exec('/usr/bin/sudo -n /usr/libexec/nethserver/sigev-batch ${@}', array($tempname), TRUE);

            $confDb = $this->getPlatform()->getDatabase('configuration');
            // Disable the forced redirection to FirstConfigWiz:
            if ($confDb->getProp('httpd-admin', 'ForcedLoginModule')) {
                $confDb->setProp('httpd-admin', array('ForcedLoginModule' => ''));
            }
            //$this->session->logout();
        }
    }

    public function setDefaultModule($id) {
        $this->defaultModule = $id;
        return $this;
    }

    public function setSession($s) {
        $this->session = $s;
        return $this;
    }

    public function getDependencySetters() {
        return array(
            'Session' => array($this, 'setSession'),
            'main.default_module' => array($this, 'setDefaultModule')
        );
    }

    public function nextPath() {
        if ($this->getRequest()->hasParameter('redirect')) {
            return '/' . $this->redirectModule;
        }
        return parent::nextPath();
    }

}
