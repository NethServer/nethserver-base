<?php
namespace NethServer\Module\NetworkAdapter;

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

/**
 * Show all system interfaces
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class InterfaceAdapter extends \Nethgui\Adapter\LazyLoaderAdapter
{
    /**
     * @var \Nethgui\System\PlatformInterface
     */
    private $platform;

    /**
     * @var array of \Nethgui\Adapter\AdapterInterface
     */
    private $adapters;

    public function __construct(\Nethgui\System\PlatformInterface $p)
    {
        $this->platform = $p;
        $this->adapters[] = $this->platform->getTableAdapter('networks', 'ethernet');
        $this->adapters[] = $this->platform->getTableAdapter('networks', 'bridge');
        $this->adapters[] = $this->platform->getTableAdapter('networks', 'bond');
        $this->adapters[] = $this->platform->getTableAdapter('networks', 'vlan');
        $this->adapters[] = $this->platform->getTableAdapter('networks', 'alias');
        parent::__construct(array($this, 'readTable'));
    }

    public function isModified()
    {
        $modified = false;
        foreach ($this->adapters as $adapter) {
            $modified = $modified || $adapter->isModified();
        }
        return $modified;
    }

    public function save()
    {
        foreach ($this->adapters as $adapter) {
            $s = $adapter->innerAdapter->save();
        }
        $this->lazyInitialization();
        return $s;
    }

    public function offsetSet($offset, $value)
    {
        return $this->adapters[0]->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->adapters[0]->offsetUnset($offset);
    }

    public function readTable()
    {
        $ret = array();
        foreach ($this->adapters as $adapter) {
            $ret = array_merge($ret, iterator_to_array($adapter));
        }

        return new \ArrayObject($ret);
    }

}
