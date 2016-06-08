<?php

include "WizHeader.php";

echo $view->fieldsetSwitch('UpstreamProxyStatus', 'enabled', $view::FIELDSETSWITCH_CHECKBOX | $view::FIELDSETSWITCH_EXPANDABLE)
    ->setAttribute('uncheckedValue', 'disabled')
    ->insert($view->textInput('host'))
    ->insert($view->textInput('port'))
    ->insert($view->textInput('username'))
    ->insert($view->textInput('password'))
;

include "WizFooter.php";
