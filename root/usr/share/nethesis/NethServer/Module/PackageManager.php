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
 * Manage group and package installation/removal
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class PackageManager extends \Nethgui\Controller\TabsController implements \Nethgui\Utility\SessionConsumerInterface
{
    /**
     *
     * @var \Nethgui\Utility\SessionInterface
     */
    private $session;

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($attributes, 'Configuration', 16);
    }

    public function initialize()
    {
        $this->loadChildrenDirectory();
        foreach ($this->getChildren() as $child) {
            if ($child instanceof \Nethgui\Utility\SessionConsumerInterface) {
                $child->setSession($this->session);
            }
        }
        parent::initialize();
    }

    public function setSession(\Nethgui\Utility\SessionInterface $session)
    {
        $this->session = $session;
        return $this;
    }

}
