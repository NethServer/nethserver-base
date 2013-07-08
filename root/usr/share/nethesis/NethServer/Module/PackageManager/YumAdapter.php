<?php
namespace NethServer\Module\PackageManager;

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
 * Expose yum package groups as a database table. All props but Status and
 * SelectedOptionalPackages are readonly.
 *
 * - Status can be set to "installed" or "available"
 * - SelectedOptionalPackages can be set to a comma separated list of packages.
 *   These packages must be chosen from the AvailableOptionalPackages list.
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class YumAdapter implements \Nethgui\Adapter\AdapterInterface, \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     *
     * @var \Nethgui\System\PlatformInterface
     */
    private $platform;

    /**
     *
     * @var \ArrayObject
     */
    private $data;
    private $queueAdd, $queueRemove;

    private $language = 'en';

    public function __construct(\Nethgui\System\PlatformInterface $platform)
    {
        $this->platform = $platform;
        $this->queueAdd = array();
        $this->queueRemove = array();
    }

    public function setLanguage($lang) {
        $this->language = $lang;
        return $this;
    }

    /**
     *
     *
     * @return \Nethgui\System\PlatformInterface
     */
    protected function getPlatform()
    {
        return $this->platform;
    }

    public function isModified()
    {
        return count($this->queueAdd) > 0 || count($this->queueRemove) > 0;
    }

    public function get()
    {
        if ( ! isset($this->data)) {
            $this->lazyInitialization();
        }

        return $this->data;
    }

    public function offsetExists($offset)
    {
        if ( ! isset($this->data)) {
            $this->lazyInitialization();
        }

        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if ( ! isset($this->data)) {
            $this->lazyInitialization();
        }

        return $this->data[$offset];
    }

    public function count()
    {
        if ( ! isset($this->data)) {
            $this->lazyInitialization();
        }

        return $this->data->count();
    }

    public function getIterator()
    {
        if ( ! isset($this->data)) {
            $this->lazyInitialization();
        }
        return $this->data->getIterator();
    }

    public function offsetSet($offset, $value)
    {
        if ( ! isset($this->data)) {
            $this->lazyInitialization();
        }

        $currentRecord = $this->data[$offset];

        if (is_array($currentRecord) && $currentRecord['Status'] !== $value['Status']) {
            if ($value['Status'] === 'installed') {
                $this->queueAddPackageGroup($offset, $value);
            } elseif ($value['Status'] === 'available') {
                $this->queueRemovePackageGroup($offset, $value);
            }
            return;
        }
        throw new \LogicException(sprintf("%s: read-only adapter, %s() method is not allowed", __CLASS__, __METHOD__), 1351072309);
    }

    private function queueAddPackageGroup($groupId, $record)
    {
        $this->queueAdd[] = '@' . $groupId;
        if ($record['SelectedOptionalPackages']) {
            $selectedOptionalPackages = array_filter(explode(',', $record['SelectedOptionalPackages']));
            $validOptionalPackages = array_filter(explode(',', $record['AvailableOptionalPackages']));

            foreach ($selectedOptionalPackages as $optionalPackage) {
                if ( ! in_array($optionalPackage, $validOptionalPackages)) {
                    throw new \UnexpectedValueException(
                        sprintf(
                            "%s: Cannot install package %s as optional package of group %s", __CLASS__, $optionalPackage, $groupId),
                        1351174719
                    );
                }
                $this->queueAdd[] = $optionalPackage;
            }
        }
        $this->data[$groupId]['Status'] = 'installing';
    }

    private function queueRemovePackageGroup($groupId, $record)
    {
        $this->queueRemove[] = '@' . $groupId;
        $this->data[$groupId]['Status'] = 'removing';
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException(sprintf("%s: read-only adapter, %s() method is not allowed", __CLASS__, __METHOD__), 1351072310);
    }

    public function save()
    {
        if ( ! $this->isModified()) {
            return FALSE;
        }

        $runningProcess = $this->getPlatform()->getDetachedProcess('PackageManager');
        if ($runningProcess !== FALSE && $runningProcess->readExecutionState() === \Nethgui\System\ProcessInterface::STATE_RUNNING) {
            throw new \UnexpectedValueException(sprintf("%s: cannot start pkgaction process, another instance is still present.", __CLASS__), 1351181599);
        }

        if (count($this->queueAdd) > 0) {
            $process = $this->getPlatform()->exec('/usr/bin/sudo /sbin/e-smith/pkgaction install ${@}', $this->queueAdd, TRUE);
        } elseif (count($this->queueRemove) > 0) {
            $process = $this->getPlatform()->exec('/usr/bin/sudo /sbin/e-smith/pkgaction remove ${@}', $this->queueRemove, TRUE);
        }

        $process->setIdentifier('PackageManager');

        return TRUE;
    }

    public function set($value)
    {
        throw new \LogicException(sprintf("%s: read-only adapter, %s() method is not allowed", __CLASS__, __METHOD__), 1351072308);
    }

    public function delete()
    {
        throw new \LogicException(sprintf("%s: read-only adapter, %s() method is not allowed", __CLASS__, __METHOD__), 1351072306);
    }

    private function lazyInitialization()
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

        $this->data = new \ArrayObject();

        // Flatten the data structure:
        foreach (array('installed', 'available') as $dState) {
            if ( ! isset($data[$dState])) {
                continue;
            }
            foreach ($data[$dState] as $dGroup) {
                $this->data[$dGroup['id']] = array(
                    'Id' => $dGroup['id'],
                    'Status' => $dState,
                    'Name' => isset($dGroup['translated_name'][$this->language]) ? $dGroup['translated_name'][$this->language] : $dGroup['name'],
                    'Description' => isset($dGroup['translated_description'][$this->language]) ? $dGroup['translated_description'][$this->language] : $dGroup['description'],
                    'AvailableOptionalPackages' => implode(',', $dGroup['optional_packages']),
                    'SelectedOptionalPackages' => '',
                    'AvailableDefaultPackages' => implode(',', $dGroup['default_packages']),
                    'AvailableMandatoryPackages' => implode(',', $dGroup['mandatory_packages']),
                    'AvailableConditionalPackages' => implode(',', $dGroup['conditional_packages']),
                );
            }
        }
    }

}