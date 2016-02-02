<?php
namespace NethServer\Module\Dashboard\Interfaces;

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
 * Describe an application which will be visibile in the application tab.
 *
 * @author Giacomo Sanchietti
 */
interface ApplicationInterface
{
    /**
    * Return an associative array in the format $key => $value.
    * If a key starts with the 'url' string, the value will be formatted as a link
    **/
    public function getInfo();

    /**
    * Return the name of the module. The name is used for sorting.
    */
    public function getName();

}
