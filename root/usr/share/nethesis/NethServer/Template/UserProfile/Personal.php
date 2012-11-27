<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('username')->setAttribute('template', $T('UserProfile_Header'));

echo $view->fieldset()
    ->setAttribute('template', $T('BasicInfo_Title'))
    ->insert($view->textInput('FirstName'))
    ->insert($view->textInput('LastName'))
    ->insert($view->textInput('EmailAddress'))
;

if ($view['username'] !== 'admin') {

    echo $view->fieldset()
        ->setAttribute('template', $T('ExtraInfo_Title'))
        ->insert($view->textInput('Company'))
        ->insert($view->textInput('Dept'))
        ->insert($view->textInput('Street'))
        ->insert($view->textInput('City'))
        ->insert($view->textInput('Phone'));
}