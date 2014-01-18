<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->buttonList()
    ->insert($view->button('ApplySelection', $view::BUTTON_SUBMIT))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

// 'groups' contains an array of views..
echo $view->objectsCollection('groups')
    ->setAttribute('template', function ($view) use ($T) {
            return $view->fieldsetSwitch('status', 'installed', $view::FIELDSETSWITCH_CHECKBOX)
                ->setAttribute('uncheckedValue', 'available')
                ->setAttribute('labelSource', 'name')
                ->setAttribute('label', '${0}')
                ->insert($view->panel()
                    ->setAttribute('class', 'labeled-control')
                    ->insert($view->textLabel('description'))
                    ->insert($view->textList('mpackages')
                        ->setAttribute('tag', 'div/span/span')
                        ->setAttribute('separator', ', ')))
            ;
        })
    ->setAttribute('key', 'id');

echo $view->buttonList()
    ->insert($view->button('ApplySelection', $view::BUTTON_SUBMIT))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

$groupsTarget = $view->getClientEventTarget('groups');

$view->includeCss("
.${groupsTarget} .TextList.mpackages {font-size: .9em}
.${groupsTarget} .TextLabel.name {font-size: 1.2em}
.${groupsTarget} .FieldsetSwitchPanel {position: relative; top: -0.5em}
    ");

