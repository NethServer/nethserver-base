<?php

namespace NethServer\Module\PackageManager;

/*
 * Copyright (C) 2013 Nethesis S.r.l.
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
 * Review the groups selection, choosing additional packages to install
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Review extends \Nethgui\Controller\Collection\AbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('removeGroup', \Nethgui\System\PlatformInterface::ANYTHING);
    }

    public function process()
    {
        parent::process();
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }
        $args = array();
        $txOrder = $this->getTransactionOrder();

        if ( ! empty($txOrder['addGroups'])) {
            $args[] = '--install';
            $args[] = implode(',', array_map(function($i) {
                        return '@' . $i;
                    }, $txOrder['addGroups']));
        }

        if ( ! empty($txOrder['removeGroups'])) {
            $args[] = '--remove';
            $args[] = implode(',', array_map(function($i) {
                        return '@' . $i;
                    }, $txOrder['removeGroups']));
        }

        if ( ! empty($txOrder['addPackages'])) {
            $args[] = '--install';
            $args[] = implode(',', $txOrder['addPackages']);
        }

        if ( ! empty($txOrder['removePackages'])) {
            $args[] = '--remove';
            $args[] = implode(',', $txOrder['removePackages']);
        }

        if (count($args) > 0) {
            $this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/pkgaction ${@}', $args, TRUE);
        }
    }

    private function getPackagesToRemove($group) {
        static $packages;
        if(isset($packages)) {
            return $packages;
        }
        $p = $this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/yum-packages-to-remove ${@}', array('@'.$group));
        $packages = $p->getOutputArray();

        if ( ! $packages[0]) {
                return;
        }

        return $packages;
    }

    private function getPackagesToKeep($group) {
        static $packages;
        if (isset($packages)) {
                return $packages;
        }
	$p = $this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/yum-packages-to-keep ${@}', array('@'.$group));
        $kpackages = $p->getOutputArray();

        if ( ! $kpackages[0]) {
                return;
        }

        foreach ($kpackages as $line) {
                $packages[]=array_combine( array('keep', 'requiredBy'), explode(' ', $line) );
        }

	return $packages;
    }

    private function getTransactionOrder()
    {
        $a = array('addGroups' => array(), 'removeGroups' => array(), 'addPackages' => array(), 'removePackages' => array(), 'keepPackages' => array());

        if ($this->parameters['removeGroup']) {
            $a['removeGroups'][] = $this->parameters['removeGroup'];
            $a['removePackages'] = $this->getPackagesToRemove($this->parameters['removeGroup']);
            $a['keepPackages'] = $this->getPackagesToKeep($this->parameters['removeGroup']);
        } else {
            $order = $this->getPlatform()->getDatabase('SESSION')->getProp('NethServer\Module\PackageManager\Modules', 'groups');
        }

        if ($this->getRequest()->isMutation()) {
            // Destroy the session storage:
            $this->getPlatform()->getDatabase('SESSION')->deleteKey('NethServer\Module\PackageManager\Modules');
        }

        if ( ! is_array($order)) {
            $order = array();
        }

        foreach ($order as $grp => $sel) {
            if ($sel['action'] === 'install') {
                $a['addGroups'][] = $grp;
            } elseif ($sel['action'] === 'remove') {
                $a['removeGroups'][] = $grp;
                $a['removePackages'] = array_merge($a['removePackages'], array());
                //$a['keepPackages'] = array_merge($a['keepPackages'], array());
            }
            if (isset($sel['opackages_selected']) && is_array($sel['opackages_selected'])) {
                $a['addPackages'] = array_merge($a['addPackages'], $sel['opackages_selected']);
            }
        }

        return $a;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $view['Back'] = $view->getModuleUrl('../Select');

        if ( ! $this->getRequest()->isMutation()) {

            $a = $this->getTransactionOrder();
            $a['addGroups?'] = count($a['addGroups']) > 0;
            $a['removeGroups?'] = count($a['removeGroups']) > 0;
            $a['addPackages?'] = count($a['addPackages']) > 0;
            $a['removePackages?'] = count($a['removePackages']) > 0;
            $a['keepPackages?'] = count($a['keepPackages']) > 0;

            $view['messages'] = $a;
        } 

        if ($this->getRequest()->isValidated()) {
            $view->getCommandList()->show();
        }
    }

    public function nextPath()
    {
        if ($this->getRequest()->isMutation()) {
            return 'Modules';
        }
        return parent::nextPath();
    }

}
