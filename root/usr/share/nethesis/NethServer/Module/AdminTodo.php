<?php

namespace NethServer\Module;

/*
 * Copyright (C) 2015 Nethesis Srl
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
 * Description of AdminTodo
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class AdminTodo extends \Nethgui\Controller\AbstractController implements \Nethgui\Component\DependencyConsumer
{

    const TEMPLATE = '<i class="fa fa-li fa-check fa-{{icon}}"></i> {{#action}}<a class="link" href="{{url}}">{{label}}</a><br/>{{/action}} {{text}}';

    /**
     *
     * @var \Nethgui\Model\UserNotifications
     */
    private $notifications;

    public $emitNotifications = FALSE;

    /**
     *
     * @var \Nethgui\Controller\RequestInterface
     */
    private $origRequest;

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $this->emitNotifications = $request->hasParameter('notifications');
    }
    
    private function readTodos()
    {
        // FIXME: language mapping must be provided by Nethgui framework!
        $langCode = $this->origRequest->getLanguageCode();
        $langMap = array(
            'it' => 'it_IT.UTF-8',
            'en' => 'en_US.UTF-8'
        );
        $lang = isset($langMap[$langCode]) ? $langMap[$langCode] : 'en_US.UTF-8';
        $data = json_decode($this->getPlatform()->exec(sprintf("/bin/env LANG=%s /usr/bin/sudo -n /usr/libexec/nethserver/admin-todos", $lang))->getOutput(), TRUE);
        return $data === NULL ? array() : $data;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);       
        $this->notifications->defineTemplate('adminTodo', self::TEMPLATE, 'bg-yellow');
        $view['todos'] = array_map(function ($todo) use ($view) {
            if(isset($todo['action']['url'])) {
                $todo['action']['url'] = $view->getModuleUrl($todo['action']['url']);
            }
            return $todo;
        }, $this->readTodos());

        if($this->emitNotifications) {
            foreach($view['todos'] as $todo) {
                $this->notifications->adminTodo($todo);
            }              
        }
    }

    public function setUserNotifications(\Nethgui\Model\UserNotifications $n)
    {
        $this->notifications = $n;
        return $this;
    }

    public function setOriginalRequest(\Nethgui\Controller\RequestInterface $request)
    {
        $this->origRequest = $request;
        return $this;
    }

    public function getDependencySetters()
    {
        return array(
            'UserNotifications' => array($this, 'setUserNotifications'),
            'OriginalRequest' => array($this, 'setOriginalRequest'),
            );
    }


}
