<?php
if ($view->getModule()->getIdentifier() == 'update') {
    $headerText = 'Update group `${0}`';
} else {
    $headerText = 'Create a new group';
}

echo $view->panel()
    ->insert($view->header('groupname')->setAttribute('template', $headerText))
    ->insert($view->textInput('groupname', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0)))
    ->insert($view->textInput('Description'))
    ->insert($view->objectPicker('Members')
        ->setAttribute('objects', 'MembersDatasource')
        ->setAttribute('template', 'Members')
        ->setAttribute('objectLabel', 1));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);
