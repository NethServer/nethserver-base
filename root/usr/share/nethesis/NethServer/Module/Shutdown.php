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
        if($this->getRequest()->isMutation()) {
            if($this->parameters['Action'] === 'poweroff') {
                $cmd = '/sbin/poweroff';
            } else {
                $cmd = '/sbin/reboot';
            }
            /* @var $p \Nethgui\System\PlatformInterface */
            $p = $this->getPlatform();
            $p->exec('/usr/bin/sudo ${1}', array($cmd));
        }
    }

}