<?php
namespace NethServer\Module\RemoteAccess;

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
 * Control ssh access to the system
 * 
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class Ssh extends \Nethgui\Controller\ListComposite
{
    /**
     *
     * @var \Nethgui\Adapter\AdapterInterface
     */
    private $status;

    /**
     * If the nethserver-base-save event must be signalled 
     * @var bool
     */
    private $saveEvent = FALSE;

    private $validators = array();

    public function initialize()
    {
        parent::initialize();

        $this->loadChildrenDirectory($this, 'SshPlugins');

        $this->sortChildren(function(\Nethgui\Module\ModuleInterface $a, \Nethgui\Module\ModuleInterface $b) {
                $pa = $a->getAttributesProvider()->getMenuPosition();
                $pb = $b->getAttributesProvider()->getMenuPosition();
                return strcmp($pa, $pb);
            });

            $this->status = $this->getPlatform()->getIdentityAdapter('configuration', 'sshd', 'status');
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        if ($request->isMutation()) {
            $this->status->set($request->getParameter('status'));
        }
        if($request->hasParameter('status')) {
            $this->validators[] = array($request->getParameter('status'), $this->getPlatform()->createValidator(\Nethgui\System\NethPlatform::SERVICESTATUS), 'status');
        }
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
        foreach($this->validators as $check) {
            if( ! $check[1]->evaluate($check[0])) {
                $report->addValidationError($this, $check[2], $check[1]);
            }
        }
    }

    public function process()
    {
        parent::process();
        if($this->status->isModified()) {
            $this->status->save();
            $this->setSaveEvent(TRUE);
        }

        if($this->saveEvent) {
            $this->getPlatform()->signalEvent('nethserver-base-save@post-process');
        }
    }

    public function setSaveEvent($value) {
        $this->saveEvent = (bool) $value;
        return $this;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['status'] = $this->status->get();
    }

}
