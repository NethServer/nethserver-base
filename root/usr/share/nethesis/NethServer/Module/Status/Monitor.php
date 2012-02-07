<?php
namespace NethServer\Module\Status;

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
 * Monitor
 *
 * @author Giovanni Bezicheri <giovanni.bezicheri@nethesis.it>
 */

class Monitor extends \Nethgui\Controller\AbstractController
{

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {   
        parent::prepareView($view);
       
        if ($this->getRequest()->isEmpty() || $view->getTargetFormat() !== $view::TARGET_JSON) {
            return;
        }
		
        $action = \Nethgui\array_head($request->getPath());
        if(method_exists($this, $action))
            call_user_func(array($this,$action), $view);
    }

}
