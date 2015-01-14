<?php

namespace NethServer\Module;

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
 * This wizard is executed on the first admin's login. Other packages may extend
 * it, by adding their controller(s) to FirstConfigWiz/ directory.
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.6
 */
class FirstConfigWiz extends \Nethgui\Controller\CompositeController implements \Nethgui\Component\DependencyConsumer
{

    /**
     *
     * @var \Nethgui\View\ViewInterface
     */
    private $wizDecorator;

    /**
     *
     * @var \ArrayObject
     */
    private $xhtmlDecoratorParams;

    public function initialize()
    {
        $this->optimizeNextView = FALSE;
        parent::initialize();
        $this->loadChildrenDirectory();

        $sortf = function(\Nethgui\Module\ModuleInterface $a, \Nethgui\Module\ModuleInterface $b) {
            if ($a instanceof \NethServer\Module\FirstConfigWiz\Cover) {
                return -1;
            }
            if ($b instanceof \NethServer\Module\FirstConfigWiz\Cover) {
                return 1;
            }
            return 0;
        };

        $this->sortChildren($sortf);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $curAction = $this->establishCurrentActionId();
        $action = $this->getAction($curAction);
        if ($action instanceof \Nethgui\Module\ModuleInterface && ! isset($action->wizardPosition)) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1420822725);
        }
    }

    public function setXhtmlDecoratorParams(\ArrayAccess $params)
    {
        $this->xhtmlDecoratorParams = &$params;
        return $this;
    }

    public function getDependencySetters()
    {
        return array(
            'decorator.xhtml.params' => array($this, 'setXhtmlDecoratorParams')
        );
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $this->wizDecorator = $view->spawnView($this, 'Decorator');

        if (isset($this->currentAction)) {
            $nextAction = $this->getSuccessor($this->currentAction);
        } else {
            $nextAction = $this->getAction('Cover');
        }

        $this->wizDecorator['steps'] = $this->getSteps($view, $nextAction ? $nextAction->getIdentifier() : '');

        $this->wizDecorator->setTemplate('NethServer\Template\FirstConfigWiz');
        if ($this->getRequest()->isValidated()) {
            $this->xhtmlDecoratorParams['disableMenu'] = TRUE;
        }
    }

    public function storeAction($decl)
    {
        $v = $this->getPlatform()->getDatabase('SESSION')->getType(__CLASS__);
        if ( ! is_array($v)) {
            $v = array();
        }
        if (isset($decl['message']['module'])) {
            $v[$decl['message']['module']] = $decl;
        } else {
            $v[] = $decl;
        }
        $this->getPlatform()->getDatabase('SESSION')->setType(__CLASS__, $v);
        return $this;
    }

    public function getSteps(\Nethgui\View\ViewInterface $view, $currentModuleIdentifier)
    {
        $steps = array();
        foreach ($this->getStepList() as $child) {
            $steps[] = array(
                'target' => $view->getUniqueId($child->getIdentifier()),
                'title' => $view->getTranslator()->translate($child, $child->getAttributesProvider()->getTitle()),
                'description' => $view->getTranslator()->translate($child, $child->getAttributesProvider()->getDescription()),
                'current?' => $child->getIdentifier() === $currentModuleIdentifier
            );
        }
        return $steps;
    }

    private function getStepList()
    {
        static $pos;
        if(isset($pos)) {
            return $pos;
        }

        $pos = array();
        foreach ($this->getChildren() as $m) {
            $position = is_callable($m->wizardPosition) ? call_user_func($m->wizardPosition) : $m->wizardPosition;
            if (isset($position) && $position >= 0) {
                $pos[$position] = $m;
            }
        }

        ksort($pos, \SORT_NUMERIC);
        return $pos;
    }

    public function getSuccessor(\Nethgui\Module\ModuleInterface $m)
    {
        $successor = NULL;

        $pos = $this->getStepList();

        while ($child = array_shift($pos)) {
            if ($m->getIdentifier() === $child->getIdentifier()) {
                $successor = array_shift($pos);
                break;
            }
        }

        return $successor;
    }

    public function renderCurrentAction(\Nethgui\Renderer\Xhtml $view)
    {
        $this->wizDecorator['content'] = parent::renderCurrentAction($view);
        return $view->inset('Decorator');
    }

    public function renderIndex(\Nethgui\Renderer\Xhtml $renderer)
    {
        $this->wizDecorator['content'] = parent::renderIndex($renderer);
        return $renderer->inset('Decorator');
    }

}
