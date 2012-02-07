<?php

// echo $view->header()->setAttribute('template', 'Pptp service');

echo $view->fieldsetSwitch('status', 'disabled');

echo $view->fieldsetSwitch('status', 'enabled')
    ->insert($view->textInput('client'));

echo $view->buttonList($view::BUTTON_SUBMIT);
