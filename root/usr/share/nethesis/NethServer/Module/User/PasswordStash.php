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
 * Store a password in a temporary file
 *
 * The temporary file is not deleted by this class. The password-modify event
 * will take care of deleting it.
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class PasswordStash
{

    private $filePath;

    public function store($password)
    {
        // FIXME: this is a security risk!
        $tmpFilePath = @tempnam(sys_get_temp_dir(), 'ng-');
        if ($tmpFilePath === FALSE) {
            throw new \RuntimeException(sprintf('%s: Could not create temporary file', get_class($this)), 1322149476);
        }
        $this->filePath = $tmpFilePath;

        $tmpFile = fopen($tmpFilePath, 'w');
        fwrite($tmpFile, $password);
        fclose($tmpFile);
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

}
