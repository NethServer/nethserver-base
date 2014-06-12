<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('values')->setAttribute('template', $T('Confirm_header'));
echo $view->textList('actions')->setAttribute('class', 'labeled-control wspreline')->setAttribute('class', 'labeled-control HelpDocument');

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('CreateLogicalInterface', $view::BUTTON_SUBMIT))
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
;
