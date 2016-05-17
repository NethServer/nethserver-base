<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('username')->setAttribute('template', $T('UserProfile_Header'));

echo $view->textInput('FullName', $view::STATE_DISABLED | $view::STATE_READONLY);

if($view['username'] === 'root') {
    echo $view->textInput('EmailAddress');
    $buttons = $view::BUTTON_SUBMIT | $view::BUTTON_HELP;
} else {
    $buttons = $view::BUTTON_HELP;
}

echo $view->buttonList($buttons)
    ->insert($view->button('ChangePassword', $view::BUTTON_LINK))
;
