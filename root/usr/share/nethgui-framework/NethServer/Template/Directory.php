<?php
echo $view->header()->setAttribute('template', 'Directory configuration');

echo $view->panel()
    ->insert($view->textInput('defaulCity'))
    ->insert($view->textInput('defaulCompany'))
    ->insert($view->textInput('defaulDepartment'))
    ->insert($view->textInput('defaulPhoneNumber'))
    ->insert($view->textInput('defaulStreet'));


echo $view->elementList($view::BUTTON_SUBMIT);
