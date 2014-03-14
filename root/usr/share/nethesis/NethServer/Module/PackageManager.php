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
class PackageManager extends \Nethgui\Controller\CompositeController
{
    /**
     *
     * @var string
     */
    private $language;

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($attributes, 'Configuration', 16);
    }

    public function initialize()
    {
        $this->addChild(new \NethServer\Module\PackageManager\Groups());
        $this->addChild(new \NethServer\Module\PackageManager\Packages());
        parent::initialize();
    }

    private function mapLang($lang)
    {
        $langMap = array(
            'en' => 'en_GB',
            'it' => 'it_IT',
            '' => 'en_GB',
        );
        if (isset($langMap[$lang])) {
            return $langMap[$lang];
        }
        return 'C';
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        $this->language = $view->getTranslator()->getLanguageCode();
        parent::prepareView($view);
    }

    private function readYumCompsDump()
    {
        static $data;
        if ( ! isset($data)) {
            $lang = $this->getRequest()->getLanguageCode() ? $this->getRequest()->getLanguageCode() : $this->language;
            $data = json_decode($this->getPlatform()->exec(sprintf("/bin/env LANG=%s /usr/bin/sudo /sbin/e-smith/pkginfo grouplist", $this->mapLang($lang)))->getOutput(), TRUE);
        }
        return $data;
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
        $data = $this->readYumCompsDump();
        $loader = new \ArrayObject();

        // Flatten the data structure:
        foreach (array('installed', 'available') as $dState) {
            if ( ! isset($data[$dState])) {
                continue;
            }

            foreach ($data[$dState] as $dGroup) {
                $loader[$dGroup['id']] = array(
                    'id' => $dGroup['id'],
                    'name' => $dGroup['name'],
                    'description' => $dGroup['description'],
                    'status' => $dState,
                    'mpackages' => $dGroup['mandatory_packages'],
                    'opackages' => $dGroup['optional_packages'],
                    'cpackages' => $dGroup['conditional_packages'],
                    'dpackages' => $dGroup['default_packages'],
                );
            }
        }

        return $loader;
    }

}