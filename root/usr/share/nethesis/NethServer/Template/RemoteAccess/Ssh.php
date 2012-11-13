<?php

/* @var $view Nethgui\Renderer\Xhtml */

$view->requireFlag($view::INSET_FORM);



echo $view->fieldsetSwitch('status', 'disabled');
$panel = $view->fieldsetSwitch('status', 'enabled');
        

foreach($view as $name => $value) {
    if($value instanceof \Nethgui\View\ViewInterface && $name !== 'Po') {
        $panel->insert($view->inset($name));
    }
}

echo $panel;

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
