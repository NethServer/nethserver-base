<?php
namespace NethServer\Module\PackageManager;

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

use Nethgui\System\PlatformInterface as Validate;
use Nethgui\Controller\Table\Modify as Table;

/**
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Operation extends \Nethgui\Controller\Table\RowAbstractAction
{
    private $availableOptionalPackages;

    public function initialize()
    {
        parent::initialize();

        $this->availableOptionalPackages = array();
        $this->availableDefaultPackages = "";
        $this->availableMandatoryPackages = "";

        $this->setSchema(array(
            array('Id', $this->createValidator(Validate::ANYTHING), Table::KEY),
            array('Status', $this->createValidator()->equalTo('installed'), Table::FIELD),
            array('Description', FALSE, Table::FIELD),
            array('SelectedOptionalPackages', $this->createValidator(), Table::FIELD, 'SelectedOptionalPackages', ','),
        ));
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $packageId = \Nethgui\array_head($request->getPath());
        $allowedKeys = array_keys(iterator_to_array($this->getParent()->getAdapter()));

        if ( ! in_array($packageId, $allowedKeys)) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1351161007);
        }

        $this->getAdapter()->setKeyValue($packageId);
        parent::bind($request);
        $this->availableOptionalPackages = array_filter(explode(',', $this->getAdapter()->offsetGet('AvailableOptionalPackages')));
        $this->availableDefaultPackages = $this->getAdapter()->offsetGet('AvailableDefaultPackages');
        $this->availableMandatoryPackages = $this->getAdapter()->offsetGet('AvailableMandatoryPackages');
        if ($request->isMutation()) {
            if ($this->getIdentifier() === 'Add') {
                $this->parameters['Status'] = 'installed';
            } else {
                $this->parameters['Status'] = 'available';
            }
        }
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        // Limit selected packages to those available:
        $this->getValidator('SelectedOptionalPackages')->orValidator(
            $this->createValidator()->isEmpty(), $this->createValidator()->collectionValidator($this->createValidator()->memberOf($this->availableOptionalPackages)
            )
        );
        parent::validate($report);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view->setTemplate('NethServer\Template\PackageManager\\' . $this->getIdentifier());

        $view['SelectedOptionalPackagesDatasource'] = array_map(
            function ($package) {
                return array($package, $package);
            }, $this->availableOptionalPackages
        );

        $view['MandatoryDefaultPackages'] = array_filter(array_merge(
            explode(',', $this->availableMandatoryPackages), explode(',', $this->availableDefaultPackages)
        ));

        if ($this->getRequest()->isMutation()) {
            $view->getCommandList()->sendQuery($view->getModuleUrl('../StatusTracker'));
        }
    }

}
