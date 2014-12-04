<?php

echo $view->header('FQDN')->setAttribute('template', $T('FQDN_header'));

echo $view->panel()
     ->insert($view->textInput('SystemName'))
     ->insert($view->textInput('DomainName'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);

