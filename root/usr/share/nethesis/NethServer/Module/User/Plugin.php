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
 * User update action can be extended adding plugins to this collection
 */
class Plugin extends \Nethgui\Controller\ListComposite implements \Nethgui\Adapter\AdapterAggregateInterface
{

    public function initialize()
    {
        $this->loadChildren(array('*\Ssh', '*\Samba'));
        parent::initialize();
    }

    public function hasAdapter()
    {
        return $this->getAdapter() instanceof \Nethgui\Adapter\AdapterInterface;
    }

    public function getAdapter()
    {
        if ( ! $this->getParent() instanceof \Nethgui\Adapter\AdapterAggregateInterface) {
            throw new \LogicException(sprintf('%s: the parent module must implement \Nethgui\Adapter\AdapterAggregateInterface', __CLASS__), 1326732823);
        }
        return $this->getParent()->getAdapter();
    }

    /**
     * Propagate the current selected record key to all plugin instances:
     * @param string $key
     * @see PluginInterface::setKey()
     * @return Plugin
     */
    public function setKey($key)
    {
        foreach ($this->getChildren() as $child) {
            if ($child instanceof PluginInterface) {
                $child->setKey($key);
            }
        }
        return $this;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view->setTemplate(FALSE);
    }

}
