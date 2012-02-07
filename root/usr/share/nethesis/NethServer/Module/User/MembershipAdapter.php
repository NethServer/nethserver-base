<?php
namespace NethServer\Module\User;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
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
 * Handles the User-Group MM relation on the User side.
 *
 * An adapter interface to the "Members" prop of the "group" database type.
 *
 * The "value" handled by this adapter is an array of group names
 * where the actual user is added/removed.
 *
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class MembershipAdapter implements \Nethgui\Adapter\AdapterInterface
{

    /**
     * @var string
     */
    private $username;
    /**
     * The implementation creates a private array of adapters where each element
     * is bound to a group "Members" prop.
     * 
     * @var array
     */
    private $innerAdapters;
    /**
     *
     * @var array
     */
    private $value;
    /**
     *
     * @var boolean
     */
    private $modified;
    /**
     *
     * @var \Nethgui\System\PlatformInterface
     */
    private $platform;
    private $groups = array();

    public function __construct($username, \Nethgui\System\PlatformInterface $platform)
    {
        $this->username = $username;
        $this->platform = $platform;
    }

    public function delete()
    {
        $this->set(array());
    }

    public function get()
    {
        if (is_null($this->modified)) {
            $this->lazyInitialization();
        }

        return $this->value;
    }

    public function isModified()
    {
        return $this->modified === TRUE;
    }

    public function save()
    {
        if ( ! $this->isModified()) {
            return 0;
        }

        $this->updateUserGroups($this->value);

        $changes = 0;

        foreach ($this->getInnerAdapters() as $a) {
            $changes += $a->save();
        }

        $this->modified = FALSE;

        return $changes;
    }

    private function updateUserGroups($groups)
    {
        foreach ($this->getInnerAdapters() as $groupName => $groupMembersAdapter) {
            $members = $groupMembersAdapter->get()->getArrayCopy();

            if (in_array($groupName, $groups)) {
                // user is member of the group
                if ( ! in_array($this->username, $members)) {
                    $groupMembersAdapter[] = $this->username;
                }
            } else {
                // user is not member of the group
                if (in_array($this->username, $members)) {

                    $index = array_search($this->username, $members);
                    if ($index !== FALSE) {
                        unset($members[$index]);
                    }

                    $groupMembersAdapter->set($members);
                }
            }
        }
    }

    public function set($value)
    {
        if (empty($value)) {
            $value = array();
        }

        if (is_null($this->modified)) {
            $this->lazyInitialization();
        }

        if ($value !== $this->value) {
            $this->modified = TRUE;
            $this->value = $value;
        }
    }

    private function lazyInitialization()
    {

        $this->modified = FALSE;

        $this->value = array();

        foreach ($this->getInnerAdapters() as $groupName => $groupMembersAdapter) {
            $members = $groupMembersAdapter->get()->getArrayCopy();
            if (in_array($this->username, $members)) {
                $this->value[] = $groupName;
            }
        }
    }

    /**
     * Lazy loader for `membership` relation
     */
    private function getInnerAdapters()
    {
        if ( ! isset($this->innerAdapters))
        {
            $this->innerAdapters = array();

            $this->groups = $this->platform->getDatabase('accounts')->getAll('group');

            // Get an identity adapter for each group that points to the Members prop.
            foreach ($this->groups as $keyName => $props) {
                $this->innerAdapters[$keyName] = $this->platform->getIdentityAdapter('accounts', $keyName, 'Members', ',');
            }
        }

        return $this->innerAdapters;
    }

    public function provideGroupsDatasource()
    {
        $values = array();

        if ( ! isset($this->innerAdapters)) {
            $this->getInnerAdapters();
        }

        foreach ($this->groups as $groupName => $props) {
            if (isset($props['Description']) && ! empty($props['Description'])) {
                $description = sprintf('%s (%s)', $props['Description'], $groupName);
            } else {
                $description = $groupName;
            }

            $values[] = array($groupName, $description);
        }

        return $values;
    }

}
