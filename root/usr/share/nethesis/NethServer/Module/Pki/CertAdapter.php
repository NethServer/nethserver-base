<?php

namespace NethServer\Module\Pki;

/*
 * Copyright (C) 2016 Nethesis Srl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of CertAdapter
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class CertAdapter extends \Nethgui\Adapter\LazyLoaderAdapter
{

    private $platform;

    public function __construct(\Nethgui\System\PlatformInterface $p)
    {
        $this->platform = $p;
        parent::__construct(array($this, 'fetchCertificatesDatabase'));
    }

    public function fetchCertificatesDatabase()
    {

        $data = json_decode($this->platform->exec('/usr/bin/sudo /usr/libexec/nethserver/cert-list')->getOutput(), TRUE);
        if ($data === FALSE) {
            return new \ArrayObject();
        }

        $db = array();

        foreach ($data as $k => $v) {
            $db[$k] = array(
                'Name' => $k,
                'Issuer' => $v['issuer'],
                'ExpireDate' => strftime('%Y-%m-%d', $v['expiration_t']),
                'Default' => $v['default']
            );
        }

        return new \ArrayObject($db);
    }

}
