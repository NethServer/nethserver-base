<?php
/* @var $view \Nethgui\Renderer\Xhtml */

include "WizHeader.php";

echo $T("Welcome_body", iterator_to_array($view));

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('Next', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../Cover?skip')))
;

