<?php
/* @var $view \Nethgui\Renderer\Xhtml */

$view->requireFlag($view::INSET_DIALOG);
echo $view->header('device')->setAttribute('template', $T('ReleasePhysicalInterface_header'));

echo $view->textLabel('message')->setAttribute('tag', 'div')->setAttribute('class', 'labeled-control wspreline');

echo $view->buttonList()
    ->insert($view->button('Release', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;
