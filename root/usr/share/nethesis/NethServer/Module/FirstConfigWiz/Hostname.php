<?php

namespace NethServer\Module\FirstConfigWiz;

/*
 * Copyright (C) 2014  Nethesis S.r.l.
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
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.6
 */
class Hostname extends \NethServer\Module\FQDN
{
    public $wizardPosition = 40;

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return new \NethServer\Tool\CustomModuleAttributesProvider($attributes, array('languageCatalog' => 'NethServer_Module_FQDN'));
    }

    protected function onParametersSaved($changes)
    {
        $this->getPlatform()->getDatabase('SESSION')->setKey(get_class($this->getParent()), __CLASS__, 'hostname-modify');
    }
    
    public function nextPath()
    {
        if ($this->getRequest()->hasParameter('skip') ||  $this->getRequest()->isMutation()) {
            $successor = $this->getParent()->getSuccessor($this);
            return $successor ? $successor->getIdentifier() : '/Dashboard';
        }
        return parent::nextPath();
    }
}