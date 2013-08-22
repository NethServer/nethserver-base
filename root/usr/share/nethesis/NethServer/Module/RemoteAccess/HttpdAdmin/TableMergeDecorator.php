<?php
namespace NethServer\Module\RemoteAccess\HttpdAdmin;

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
 * Merge one table value adapter with a table adapter. First one will be used in rw mode, the second one in read-only mode.
 *
 */
class TableMergeDecorator implements \Nethgui\Adapter\AdapterInterface, \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     *
     * @var TableAdapter
     */
    private $rw_table;
    
    /**
     *
     * @var TableAdapter
     */
    private $ro_table;

    private $mergeData;

    public function __construct(\Nethgui\Adapter\TabularValueAdapter $rw_table, \Nethgui\Adapter\TableAdapter $ro_table)
    {
        $this->rw_table = $rw_table;
        $this->ro_table = $ro_table;
    }

    private function lazyInitialization()
    {
        $tmp = array();
        foreach ($this->rw_table as $k => $props) {
            $tmp[$k] = array($props[0], 'editable' => true);
        }
        foreach ($this->ro_table as $k => $props) {
            if(!isset($tmp[$k])) { //avoid override
                $tmp[$k] = array($props['Mask'],'editable' => false);
            }
        }
        $this->mergeData = new \ArrayObject($tmp);
    }

    public function count()
    {
        return $this->rw_table->count();
    }

    public function delete()
    {
        $this->rw_table->delete();
    }

    public function get()
    {
        return $this->rw_table->get() ;
    }

    public function set($value)
    {
        return $this->rw_table->set($value);
    }

    public function save()
    {
        return $this->rw_table->save();
    }

    public function getIterator()
    {
        if ( ! isset($this->mergeData)) {
            $this->lazyInitialization();
        }

        return $this->mergeData->getIterator();
    }

    public function isModified()
    {
        return $this->rw_table->isModified();
    }

    public function offsetExists($offset)
    {
        return $this->rw_table->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->rw_table->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->rw_table->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->rw_table->offsetUnset($offset);
    }

}
