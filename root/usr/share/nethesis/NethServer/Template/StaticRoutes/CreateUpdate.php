<?php

if ($view->getModule()->getIdentifier() == 'update') {
    $headerText = 'Update static route `${0}`';
} else {
    $headerText = 'Create a new static route';
}

echo $view->panel()
    ->insert($view->header('network')->setAttribute('template', $T($headerText)))
    ->insert($view->textInput('network', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0)))
    ->insert($view->textInput('Mask', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0)))
    ->insert($view->textInput('Router'))
    ->insert($view->textInput('Description'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL);
