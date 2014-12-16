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
            $pa = isset($a->wizardPosition) ? (int) $a->wizardPosition : NULL;
            $pb = isset($b->wizardPosition) ? (int) $b->wizardPosition : NULL;
            return $pa - $pb;
        };

        $this->sortChildren($sortf);
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

        $curAction = $this->currentAction ? $this->currentAction : $this->getAction('Cover');
        $curView = $view->spawnView($curAction);

        $this->wizDecorator = $view->spawnView($this, 'Decorator');
        $this->wizDecorator['wizardPosition'] = $curAction->wizardPosition;

        if ($curAction->getRequest()->hasParameter('skip') || $this->getRequest()->isMutation()) {
            $nextAction = $this->getSuccessor($curAction);
        } else {
            $nextAction = $curAction;
        }

        $this->wizDecorator['steps'] = $this->getSteps($view, $nextAction ? $nextAction->getIdentifier() : '');

        $this->wizDecorator->setTemplate('NethServer\Template\FirstConfigWiz');
        if ($this->getRequest()->isValidated()) {
            $this->xhtmlDecoratorParams['disableMenu'] = TRUE;
        }
    }

    public function getSteps(\Nethgui\View\ViewInterface $view, $currentModuleIdentifier)
    {
        $steps = array();
        foreach (array_filter($this->getChildren(), function($m) {
            return isset($m->wizardPosition) ? TRUE : FALSE;
        }) as $child) {
            $steps[] = array(
                'id' => $child->getIdentifier(),
                'title' => $view->getTranslator()->translate($child, $child->getAttributesProvider()->getTitle()),
                'description' => $view->getTranslator()->translate($child, $child->getAttributesProvider()->getDescription()),
                'current?' => $child->getIdentifier() === $currentModuleIdentifier
            );
        }
        return $steps;
    }

    public function getSuccessor(\Nethgui\Module\ModuleInterface $m)
    {
        $successor = NULL;

        $modules = array_filter($this->getChildren(), function($m) {
            return isset($m->wizardPosition) ? TRUE : FALSE;
        });

        while ($child = array_shift($modules)) {
            if ($m->getIdentifier() === $child->getIdentifier()) {
                $successor = array_shift($modules);
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