<?php
namespace NethServer\Module;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
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
 * Change current logged in user settings
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class UserProfile extends \Nethgui\Controller\ListComposite
{
    /**
     *
     * @var \Nethgui\Adapter\AdapterInterface
     */
    private $adapter;

    public function initialize()
    {
        parent::initialize();
        $this->loadChildrenDirectory($this);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $userName = $request->getUser()->getCredential('username');

        if ($userName === 'admin') {
            $this->adapter = $this->getPlatform()->getTableAdapter('configuration', 'configuration');
        } else {
            $this->adapter = $this->getPlatform()->getTableAdapter('accounts', 'user');
        }

        $recordAdapter = new \Nethgui\Adapter\RecordAdapter($this->adapter);
        $recordAdapter->setKeyValue($userName);

        foreach ($this->getChildren() as $child) {
            $child->setAdapter($recordAdapter);
        }
        parent::bind($request);
    }

    public function process()
    {
        parent::process();
        $this->adapter->save();
    }

}