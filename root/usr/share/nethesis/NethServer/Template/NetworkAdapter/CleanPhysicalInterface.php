<?php
/* @var $view \Nethgui\Renderer\Xhtml */

$view->requireFlag($view::INSET_DIALOG);
echo $view->header('device')->setAttribute('template', $T('CleanPhysicalInterface_header'));

echo $view->textLabel('device')->setAttribute('tag', 'div')->setAttribute('class', 'labeled-control wspreline')->setAttribute('template', $T('Confirm device ${0} removal'));

echo $view->buttonList()
    ->insert($view->button('Clean', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;
