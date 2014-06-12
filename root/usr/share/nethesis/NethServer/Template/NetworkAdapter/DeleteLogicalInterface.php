<?php
/* @var $view \Nethgui\Renderer\Xhtml */

$view->requireFlag($view::INSET_DIALOG);
echo $view->header('device')->setAttribute('template', $T('DeleteLogicalInterface_header'));

echo $view->textLabel('message')->setAttribute('tag', 'div')->setAttribute('class', 'labeled-control wspreline');
echo $view->selector('successor', $view::SELECTOR_DROPDOWN);

echo $view->buttonList()
    ->insert($view->button('DeleteLogicalInterface', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;
