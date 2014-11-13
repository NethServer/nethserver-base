<?php

echo $view->header('FQDN')->setAttribute('template', $T('FQDN_header'));

echo $view->panel()
    ->setAttribute('class', 'fqdn_module_warning wspreline ui-state-highlight labeled-control')
    ->setAttribute('tag', 'div')
    ->insert($view->literal('<i class="fa"></i>'))
    ->insert($view->textLabel('warning'));

echo $view->panel()
     ->insert($view->textInput('SystemName'))
     ->insert($view->textInput('DomainName'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);

$view->includeCSS("
  .fqdn_module_warning {
     margin-bottom: 8px;
     padding: .8em;
     display: flex;
  }

  .fqdn_module_warning > i:before { content: \"\\f071\"; font-size: 1.2em; }
  .fqdn_module_warning > span { padding-left: .8em }

");
