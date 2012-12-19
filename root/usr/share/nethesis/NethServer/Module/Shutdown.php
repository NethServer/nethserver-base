<?php
namespace NethServer\Module;

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
 * Reboots and switch off the machine
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Shutdown extends \Nethgui\Controller\AbstractController
{
    /**
     *
     *
     * @var \Nethgui\System\ProcessInterface
     */
    private $process;

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Administration', 20);
    }

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('Action', $this->createValidator()->memberOf('poweroff', 'reboot'));
    }

    public function process()
    {
        parent::process();

        if ($this->getRequest()->isMutation()) {
            if ($this->parameters['Action'] === 'poweroff') {
                $cmd = '/sbin/poweroff';
            } else {
                $cmd = '/sbin/reboot';
            }

            $this->process = $this->getPlatform()->exec('/usr/bin/sudo ${1}', array($cmd));
            //$this->process = $this->getPlatform()->exec('/bin/date');

        } elseif ($this->getRequest()->hasArgument('wait')) {
            // parse /sbin/runlevel output to get the current runlevel value:
            $this->process = $this->getPlatform()->exec('/usr/bin/sudo ${1}', array('/sbin/runlevel'));
            $runlevel = \Nethgui\array_end(explode(' ', $this->process->getOutput()));
            NETHGUI_DEBUG && $this->getLog()->notice($runlevel . ' ' . join(', ', $this->getRequest()->getArgumentNames()));

            // runlevel validation:
            if ($runlevel === '0' || $runlevel === '6') {
                NETHGUI_DEBUG && $this->getLog()->notice('Sleeping 10 seconds..');
                sleep(10);
            } else {
                // wait argument is allowed only on reboot and halt runlevels!
                throw new \Nethgui\Exception\HttpException('Forbidden', 403, 1355301177);
                sleep(2);
            }
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if ( ! isset($this->process)) {
            return;
        }
        
        if ($this->getRequest()->isMutation()) {
            if ($this->process->getExitCode() === 0) {
                $view->getCommandList()
                    ->shutdown($view->getModuleUrl() . '?wait=0', $this->parameters['Action'], array($view->translate('shutdown_' . $this->parameters['Action']), $view->translate('test')));
                ;
            } else {
                $view->getCommandList('/Notification')
                    ->showMessage("error " . $this->process->getOutput(), \Nethgui\Module\Notification\AbstractNotification::NOTIFY_ERROR)
                ;
            }
        }
    }

}