<?php
namespace NethServer\Module\Dashboard;

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
 * Dashboard with plugin behaviour
 * First tab is always present and has plugin behaviour. All other tabs can be plugins.
 */
class SystemStatus extends \Nethgui\Controller\ListComposite
{
  
    public $sortId = 00;

    public function initialize()
    {
        parent::initialize();
        $this->loadChildrenDirectory();
        $this->sortChildren(array($this,"sortPlugin"));
    }

    public function sortPlugin($a, $b)
    {
        if ($a->sortId == $b->sortId) {
            return 0;
        } else if ($a->sortId  < $b->sortId) {
            return -1;
        } else {
            return 1;
        }
    }
}
