<?php

echo $view->header()->setAttribute('template', $T('UpstreamProxy_header'));

echo $view->panel()
    ->insert($view->textInput('host'))
    ->insert($view->textInput('port'))
    ->insert($view->textInput('username'))
    ->insert($view->textInput('password'))
;

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);
