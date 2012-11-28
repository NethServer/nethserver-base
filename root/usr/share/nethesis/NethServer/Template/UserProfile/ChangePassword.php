<?php

echo $view->header('username')->setAttribute('template', $T('ChangePassword_Header'));

/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->textInput('oldPassword', $view::TEXTINPUT_PASSWORD);

include dirname(__DIR__) . '/PasswordForm.php';
