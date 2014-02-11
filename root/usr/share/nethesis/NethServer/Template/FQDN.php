<?php

echo $view->header('FQDN')->setAttribute('template', $T('FQDN_header'));

echo "<div id='fqdn_module_warning' class='ui-state-highlight'><span class='ui-icon ui-icon-info'></span>".$T('FQDN_warning')."</div>";
echo $view->panel()
     ->insert($view->textInput('SystemName'))
     ->insert($view->textInput('DomainName'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);

$view->includeCSS("
  #fqdn_module_warning {
     margin-bottom: 8px;
     padding: 8px;
  }

  #fqdn_module_warning .ui-icon {
     float: left;
     margin-right: 3px;
  }
");
