<?php

echo $view->header()->setAttribute('template', $T('Organization contacts'));

$warningPanel = $view->panel()
    ->setAttribute('class', 'organization_module_warning wspreline ui-state-highlight labeled-control')
    ->setAttribute('tag', 'div')
    ->insert($view->literal('<i class="fa"></i>'))
    ->insert($view->textLabel('warning'));

if($view['warning']) {
    echo $warningPanel;
}

echo $view->panel()
    ->insert($view->textInput('Company'))
    ->insert($view->textInput('City'))
    ->insert($view->textInput('Department'))
    ->insert($view->textInput('PhoneNumber'))
    ->insert($view->textInput('Street'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);

$view->includeCSS("
  .organization_module_warning {
     margin-bottom: 8px;
     padding: .8em;
     display: flex;
  }

  .organization_module_warning > i:before { content: \"\\f071\"; font-size: 1.2em; }
  .organization_module_warning > span { padding-left: .8em }

");