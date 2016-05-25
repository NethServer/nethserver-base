<?php

echo $view->header()->setAttribute('template', $T('Shutdown_Title'));

/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->radioButton('Action', 'reboot', $view::STATE_CHECKED);
echo $view->radioButton('Action', 'poweroff');

echo $view->buttonList()
    ->insert($view->button('Shutdown', $view::BUTTON_SUBMIT))
    ->insert($view->button('Help', $view::BUTTON_HELP));
