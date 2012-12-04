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
class UserProfile extends \Nethgui\Controller\CompositeController implements \Nethgui\Authorization\PolicyEnforcementPointInterface
{
    /**
     *
     * @var \Nethgui\Adapter\AdapterInterface
     */
    private $adapter;
    private $recordAdapter;

    public function initialize()
    {
        $this->loadChildrenDirectory($this);

        // Create an empty record adapter and set it into child modules, so
        // that child parameters can be declared in initialize():
        $this->recordAdapter = new \Nethgui\Adapter\RecordAdapter();
        foreach ($this->getChildren() as $child) {
            $child->setAdapter($this->recordAdapter);
        }

        // Sort child modules so that "Personal" is actually the first (shown by default).
        $firstModule = 'Personal';
        $this->sortChildren(function(\Nethgui\Module\ModuleInterface $a, \Nethgui\Module\ModuleInterface $b) use ($firstModule) {
                if ($a->getIdentifier() === $firstModule) {
                    return -1;
                } elseif ($b->getIdentifier() === $firstModule) {
                    return 1;
                }
                return 0;
            });

        parent::initialize();
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $userName = $request->getUser()->getCredential('username');

        // The `admin` user needs a different data source:
        if ($userName === 'admin') {
            $this->adapter = $this->getPlatform()->getTableAdapter('configuration', 'configuration');
        } else {
            $this->adapter = $this->getPlatform()->getTableAdapter('accounts', 'user');
        }

        // Inject username-dependent datasource into the record adapter
        $this->recordAdapter->setTableData($this->adapter)->setKeyValue($userName);

        parent::bind($request);
    }

    public function process()
    {
        parent::process();

        // If something has been changed in the original table persist modifications:
        $this->adapter->save();
    }

}