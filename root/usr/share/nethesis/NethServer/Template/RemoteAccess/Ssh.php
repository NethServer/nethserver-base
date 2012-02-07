<?php

// echo $view->header()->setAttribute('template', $T('Ssh'));

echo $view->fieldsetSwitch('status', 'disabled');

echo $view->fieldsetSwitch('status', 'enabled')
    ->insert($view->fieldset()->setAttribute('template', $view->translate('Connection'))
        ->insert($view->textInput('port'))
        ->insert($view->radioButton('access', 'private'))
        ->insert($view->radioButton('access', 'public'))
    )
    ->insert($view->fieldset()->setAttribute('template', $view->translate('Permissions'))
        ->insert($view->checkBox('rootLogin', 'yes'))
        ->insert($view->checkBox('passwordAuth', 'yes'))
    )

;

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
