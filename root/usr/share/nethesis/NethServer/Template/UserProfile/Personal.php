<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('username')->setAttribute('template', $T('UserProfile_Header'));

echo $view->textInput('FullName', $view::STATE_DISABLED | $view::STATE_READONLY);

if($view['username'] === 'root') {
    echo $view->textInput('EmailAddress');
    $buttonList = $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
    $buttonList->insert($view->button('ChangePassword', $view::BUTTON_LINK));
} else {
    $buttonList = $view->buttonList($view::BUTTON_HELP);
    if (!$view['readOnly']) {
        $buttonList->insert($view->button('ChangePassword', $view::BUTTON_LINK));
    }
}
echo $buttonList;
