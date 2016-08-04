<?php
namespace NethServer\Module\StaticRoutes;

/*
 * Copyright (C) 2016 Nethesis S.r.l.
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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Manage static routes
 */
class Modify extends \Nethgui\Controller\Table\Modify
{

    private $interfaces = NULL;

    protected function listInterfaces()
    {
        if (! $this->interfaces) {
            $this->interfaces = array('');
            foreach ($this->getPlatform()->getDatabase('networks')->getAll() as $key => $props) {
                if ( isset($props['role']) && $props['role']) {
                    $this->interfaces[] = $key;
                }
            
            }
        }
        return $this->interfaces;
    }

    public function initialize()
    {
        $p = $this->getPlatform();
        $vn = $p->createValidator()->orValidator($p->createValidator()->cidrBlock(), $p->createValidator()->memberOf(array('0.0.0.0/0','default')));
        $vd = $p->createValidator()->memberOf($this->listInterfaces());
        $vm = $p->createValidator()->orValidator($p->createValidator(Validate::EMPTYSTRING), $this->createValidator(Validate::NONNEGATIVE_INTEGER));
        $parameterSchema = array(
            array('network', $vn, \Nethgui\Controller\Table\Modify::KEY),
            array('Router', Validate::IPv4, \Nethgui\Controller\Table\Modify::FIELD),
            array('Device', $vd, \Nethgui\Controller\Table\Modify::FIELD),
            array('Metric', $vm, \Nethgui\Controller\Table\Modify::FIELD),
            array('Description', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
        );

        $this->setSchema($parameterSchema);
        parent::initialize();
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['DeviceDatasource'] = array_map(function($fmt) use ($view) {
            if($fmt == '') {
                $label =  $view->translate('any_label');
            } else {
                $label = $fmt;
            }

            return array($fmt,$label);
        }, $this->listInterfaces());
        $templates = array(
            'create' => 'NethServer\Template\StaticRoutes\Modify',
            'update' => 'NethServer\Template\StaticRoutes\Modify',
            'delete' => 'Nethgui\Template\Table\Delete',
        );
        $view->setTemplate($templates[$this->getIdentifier()]);

    }

    protected function onParametersSaved($parameters)
    {
        $this->getPlatform()->signalEvent('static-routes-save &');
    }

}

