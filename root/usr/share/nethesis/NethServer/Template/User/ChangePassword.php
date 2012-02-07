<?php
echo $view->header('username')->setAttribute('template', 'Change password for user `${0}`');
echo $view->textInput('oldPassword', $view::TEXTINPUT_PASSWORD);
echo $view->textInput('newPassword', $view::TEXTINPUT_PASSWORD);
echo $view->textInput('confirmNewPassword', $view::TEXTINPUT_PASSWORD);
echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL);
