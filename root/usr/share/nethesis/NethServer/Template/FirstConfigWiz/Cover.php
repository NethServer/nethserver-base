<?php
/* @var $view \Nethgui\Renderer\Xhtml */

include "WizHeader.php";

echo $T("Welcome_body");

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('Next', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../Cover?skip')))
;

