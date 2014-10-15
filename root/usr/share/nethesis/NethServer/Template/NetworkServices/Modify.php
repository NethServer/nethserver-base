<?php

echo $view->header('name')->setAttribute('template', $T('Update_Service_Header'));

$allow = $view->panel()
    ->insert($view->textInput('AllowHosts'));
$deny = $view->panel()
    ->insert($view->textInput('DenyHosts'));

$access = $view->panel()
    ->insert($view->fieldsetSwitch('access', 'public', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($allow)
        ->insert($deny)
    )
    ->insert($view->fieldsetSwitch('access', 'private', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($allow)
        ->insert($deny)
    )
    ->insert($view->fieldsetSwitch('access', 'none'));


echo $view->panel()->insert($access);


echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

$view->includeCss("
    .ns-green {
        color: green;
        font-weight: bold;
        padding-right: 5px;
    }
    .ns-red {
        color: red;
        font-weight: bold;
    }
    .ns-black {
        color: black;
        font-weight: bold;
    }
    .ns-grey {
        color: grey;
        font-weight: bold;
    }

");
