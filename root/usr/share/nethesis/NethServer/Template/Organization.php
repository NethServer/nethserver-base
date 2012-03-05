<?php

echo $view->header()->setAttribute('template', $T('Organization contacts'));

echo $view->panel()
    ->insert($view->textInput('Company'))
    ->insert($view->textInput('City'))
    ->insert($view->textInput('Department'))
    ->insert($view->textInput('PhoneNumber'))
    ->insert($view->textInput('Street'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
