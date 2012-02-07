<?php
$view->requireFlag($view::INSET_DIALOG);

if ($view->getModule()->getIdentifier() == 'lock') {
    $headerText = 'Lock user `${0}`';
    $panelText = 'Proceed with user `${0}` lock?';
} else {
    $headerText = 'Unlock user `${0}`';
    $panelText = 'Proceed with user `${0}` unlock?';
}

echo $view->panel()
    ->insert($view->header('username')->setAttribute('template', $headerText))
    ->insert($view->textLabel('username')->setAttribute('template', $view->translate($panelText)))
;

echo $view->buttonList()
    ->insert($view->button('Yes', $view::BUTTON_SUBMIT))
    ->insert($view->button('No', $view::BUTTON_CANCEL)->setAttribute('value', $view['Cancel']))
;

