<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('alias')->setAttribute('template', $T('CreateIpAlias_header'));

echo $view->textInput('ipaddr');
echo $view->textInput('netmask');

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('CreateIpAlias', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;