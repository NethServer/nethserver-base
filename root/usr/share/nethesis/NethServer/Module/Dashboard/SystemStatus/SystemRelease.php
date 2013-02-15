<?php
namespace NethServer\Module\Dashboard\SystemStatus;

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
 * Retrieve system release reading /etc/nethserver-release
 *
 * @author Giacomo Sanchietti
 */
class SystemRelease extends \Nethgui\Controller\AbstractController
{

    private $release = "";

    private function readRelease()
    {
        if (file_exists("/etc/nethserver-release")) {
            return trim(file_get_contents("/etc/nethserver-release"));
        }
        return "";
    }

    public function process()
    {
        $this->release = $this->readRelease();
    }
 
    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if (!$this->release) {
            $this->release = $this->readRelease();
        }

        $view['release'] = $this->release;
    }
}
