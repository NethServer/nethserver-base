<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('username')->setAttribute('template', $T('UserProfile_Header'));

echo $view->fieldset()
    ->setAttribute('template', $T('BasicInfo_Title'))
    ->insert($view->textInput('FirstName'))
    ->insert($view->textInput('LastName'))
    ->insert($view->textInput('EmailAddress'))
;

if ($view['username'] !== 'root')
    echo $view->fieldset()
        ->setAttribute('template', $T('ExtraInfo_Title'))
        ->insert($view->textInput('Company')->setAttribute('placeholder', $view['Default_Company']))
        ->insert($view->textInput('Dept')->setAttribute('placeholder', $view['Default_Dept']))
        ->insert($view->textInput('Street')->setAttribute('placeholder', $view['Default_Street']))
        ->insert($view->textInput('City')->setAttribute('placeholder', $view['Default_City']))
        ->insert($view->textInput('Phone')->setAttribute('placeholder', $view['Default_Phone']))
    ;

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP)
    ->insert($view->button('ChangePassword', $view::BUTTON_LINK))
;
