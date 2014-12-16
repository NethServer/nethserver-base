<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('Save', $view::BUTTON_SUBMIT))
    ->insert($view->button('Skip', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl(sprintf('../%s?skip', $view->getModule()->getIdentifier()))))
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
;


