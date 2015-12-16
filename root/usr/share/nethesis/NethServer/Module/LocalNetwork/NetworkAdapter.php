<?php
namespace NethServer\Module\LocalNetwork;

/*
 * Copyright (C) 2015 Nethesis S.r.l.
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
 * Manage trusted networks, display green networks
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class NetworkAdapter extends \Nethgui\Adapter\LazyLoaderAdapter
{
    /**
     * @var \Nethgui\System\PlatformInterface
     */
    private $platform;

    /**
     * @var \Nethgui\Adapter\AdapterInterface
     */
    private $innerAdapter;

    public function __construct(\Nethgui\System\PlatformInterface $p)
    {
        $this->platform = $p;
        $this->innerAdapter = $this->platform->getTableAdapter('networks', 'network');
        parent::__construct(array($this, 'readTable'));
    }

    public function isModified()
    {
        return $this->innerAdapter->isModified();
    }

    public function save()
    {
        $s = $this->innerAdapter->save();
        if ($s) {
            $this->lazyInitialization();
        }
        return $s;
    }

    public function offsetSet($offset, $value)
    {
        return $this->innerAdapter->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->innerAdapter->offsetUnset($offset);
    }

    public function readTable()
    {
        $networks = iterator_to_array($this->innerAdapter);

        $elements = json_decode($this->platform->exec('/usr/libexec/nethserver/trusted-networks')->getOutput(), TRUE); 

        if(! is_array($elements)) {
            return new \ArrayObject($networks);
        }

        foreach($elements as $e) {
            list($net, $mask) = explode('/', $e['mask']);

            if( ! isset($networks[$net])) {
                $networks[$net] = array(
                    'Mask' => $mask,
                    'Description' => $e['provider'],
                    'editable' => 0
                );
            }
        }

        return new \ArrayObject($networks);
    }

}
