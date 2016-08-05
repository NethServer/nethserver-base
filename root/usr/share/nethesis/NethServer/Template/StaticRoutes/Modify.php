<?php

if ($view->getModule()->getIdentifier() == 'update') {
    $headerText = 'Update static route `${0}`';
} else {
    $headerText = 'Create a new static route';
}

echo $view->panel()
    ->insert($view->header('network')->setAttribute('template', $T($headerText)))
    ->insert($view->textInput('network', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0)))
    ->insert($view->textInput('Router'))
    ->insert($view->selector('Device', $view::SELECTOR_DROPDOWN))
    ->insert($view->textInput('Description'));

echo $view->fieldset('')->setAttribute('template', $T('Advanced_label'))
    ->insert($view->textInput('Metric'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP | $view::BUTTON_CANCEL);
