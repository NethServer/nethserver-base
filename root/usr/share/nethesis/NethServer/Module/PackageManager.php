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
class PackageManager extends \Nethgui\Controller\TabsController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($attributes, 'Configuration', 16);
    }

    public function initialize()
    {
        $this->loadChildrenDirectory();
        parent::initialize();
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

        $data = json_decode($this->getPlatform()->exec('/usr/bin/sudo /sbin/e-smith/pkginfo grouplist')->getOutput(), TRUE);

        $loader = new \ArrayObject();

        $lang = $this->getRequest()->getLanguageCode();

        // Flatten the data structure:
        foreach (array('installed', 'available') as $dState) {
            if ( ! isset($data[$dState])) {
                continue;
            }

            $translate = function($dGroup, $field) use ($lang) {
                    $tfield = 'translated_' . $field;
                    return isset($dGroup[$tfield][$lang]) ? $dGroup[$tfield][$lang] : $dGroup[$field];
                };

            foreach ($data[$dState] as $dGroup) {
                $loader[$dGroup['id']] = array(
                    'id' => $dGroup['id'],
                    'name' => $translate($dGroup, 'name'),
                    'description' => $translate($dGroup, 'description'),
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