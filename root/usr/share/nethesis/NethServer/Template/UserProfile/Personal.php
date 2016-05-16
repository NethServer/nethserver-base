<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('username')->setAttribute('template', $T('UserProfile_Header'));

echo $view->textInput('FullName', $view::STATE_DISABLED | $view::STATE_READONLY);

if($view['username'] === 'root') {
    echo $view->textInput('EmailAddress');
}

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP)
    ->insert($view->button('ChangePassword', $view::BUTTON_LINK))
;
