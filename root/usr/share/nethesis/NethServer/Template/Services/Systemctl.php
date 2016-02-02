<?php
$view->requireFlag($view::INSET_DIALOG);

$action = $view->getModule()->getIdentifier();

$headerText = $T(ucfirst($action).'  service `${0}`');
$panelText = $T('Proceed with `${0}` '.$action.'?');

echo $view->panel()
    ->insert($view->header('service')->setAttribute('template', $headerText))
    ->insert($view->textLabel('service')->setAttribute('template', $panelText))
;

echo $view->buttonList()
    ->insert($view->button('Yes', $view::BUTTON_SUBMIT))
    ->insert($view->button('No', $view::BUTTON_CANCEL)->setAttribute('value', $view['Cancel']))
;

