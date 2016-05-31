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
 * Description of SetDefault
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class SetDefault extends \Nethgui\Controller\Table\RowAbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $parameterSchema = array(
            array('name', FALSE, \Nethgui\Controller\Table\Modify::KEY),
        );
        $this->setSchema($parameterSchema);
    }
    public function process()
    {
        if ( $this->getRequest()->isMutation()) {
            $db = $this->getPlatform()->getDatabase('configuration');
            $name = \Nethgui\array_end($this->getRequest()->getPath());
            $certificates = json_decode($this->getPlatform()->exec('/usr/bin/sudo /usr/libexec/nethserver/cert-list')->getOutput(), TRUE);
            foreach ($certificates as $key => $props) {
                 if ($key == $name) {
                      $db->setProp('pki', array('CrtFile' => $props['file'], 'KeyFile' => $props['key'], 'ChainFile' => $props['chain']));
                      $this->getPlatform()->signalEvent('certificate-update &');
                 }
            }
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['cert'] = \Nethgui\array_end($this->getRequest()->getPath());
    }
}
