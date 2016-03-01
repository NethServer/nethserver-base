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
 * Manage group and package installation/removal
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class PackageManager extends \Nethgui\Controller\CompositeController implements \Nethgui\Component\DependencyConsumer
{

    /**
     *
     * @var string
     */
    private $language = 'en';

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($attributes, 'Administration', 16);
    }

    public function initialize()
    {
        $this->addChild(new \NethServer\Module\PackageManager\Modules());
        $this->addChild(new \NethServer\Module\PackageManager\Review());
        $this->addChild(new \NethServer\Module\PackageManager\Packages());
        $this->addChild(new \NethServer\Module\PackageManager\EditModule());
        $this->addChild(new \NethServer\Module\PackageManager\ClearYumCache());
        parent::initialize();
    }

    private function readYumCompsDump()
    {
        static $data;
        if ( ! isset($data)) {
            $process = $this->getPlatform()->exec("/usr/bin/sudo /sbin/e-smith/pkginfo grouplist");
            if ($process->getExitCode() !== 0) {
                $this->getLog()->error($process->getOutput());
            }
            $data = json_decode($process->getOutput(), TRUE);
        }
        return $data;
    }

    public function yumGroups()
    {
        $data = $this->readYumCompsDump();
        return isset($data['groups']) ? $data['groups'] : array();
    }

    public function yumCategories()
    {
        $data = $this->readYumCompsDump();
        return isset($data['categories']) ? $data['categories'] : array();
    }

    public function yumGroupsLoader()
    {
        /*
         * NOTE on package groups; packages:
         *  - "optional" are not automatic but can be checked
         *  - "default" are, but can be unchecked in a gui tool
         *  - "mandatory" are always brought in (if group is selected),
         *    and not visible in the Package Selection dialog.
         *  - "conditional" are brought in if their requires package is
         *    installed
         *
         * See http://fedoraproject.org/wiki/How_to_use_and_edit_comps.xml_for_package_groups
         */
        $yumGroups = $this->yumGroups();
        $yumCategories = $this->yumCategories();

        $categories = function ($groupId) use ($yumCategories) {
            $matches = array();
            foreach ($yumCategories as $c) {
                if (in_array($groupId, $c['groups'])) {
                    $matches[] = $c['id'];
                }
            }
            return $matches;
        };

        $loader = new \ArrayObject();
        foreach ($yumGroups as $dGroup) {
            $loader[$dGroup['id']] = array(
                'id' => $dGroup['id'],
                'name' => $dGroup['name'],
                'description' => $dGroup['description'],
                'status' => $dGroup['installed'] ? 'installed' : 'available',
                'mpackages' => $dGroup['mandatory_packages'],
                'opackages' => $dGroup['optional_packages'],
                'cpackages' => $dGroup['conditional_packages'],
                'dpackages' => $dGroup['default_packages'],
                'categories' => implode(' ', $categories($dGroup['id']))
            );
        }

        return $loader;
    }

    public function setTranslator(\Nethgui\View\TranslatorInterface $t)
    {
        $this->language = $t->getLanguageCode();
        return $this;
    }

    public function setUserNotifications(\Nethgui\Model\UserNotifications $n)
    {
        $this->notifications = $n;
        return $this;
    }

    public function getDependencySetters()
    {
        return array(
            'Translator' => array($this, 'setTranslator'),
            'UserNotifications' => array($this, 'setUserNotifications')
        );
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if ($this->getRequest()->isMutation()) {
            $this->getPlatform()->setDetachedProcessCondition('success', array(
                'location' => array(
                    'url' => $view->getModuleUrl('Modules?installSuccess'),
                    'freeze' => TRUE,
            )));
            $this->getPlatform()->setDetachedProcessCondition('failure', array(
                'location' => array(
                    'url' => $view->getModuleUrl('Modules?installFailure&taskId={taskId}'),
                    'freeze' => TRUE,
            )));
        }
        parent::prepareView($view);
    }
}
