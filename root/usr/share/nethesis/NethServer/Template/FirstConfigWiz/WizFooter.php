<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
    ->insert($view->button('Next', $view::BUTTON_SUBMIT))
;


