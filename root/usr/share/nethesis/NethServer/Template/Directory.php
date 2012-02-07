<?php

echo $view->header()->setAttribute('template', $T('Directory configuration'));

echo $view->panel()
    ->insert($view->textInput('defaulCompany'))
    ->insert($view->textInput('defaulCity'))
    ->insert($view->textInput('defaulDepartment'))
    ->insert($view->textInput('defaulPhoneNumber'))
    ->insert($view->textInput('defaulStreet'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
