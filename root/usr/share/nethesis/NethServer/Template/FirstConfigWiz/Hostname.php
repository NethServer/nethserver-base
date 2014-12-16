<?php

include 'WizHeader.php';

echo $view->panel()
     ->insert($view->textInput('SystemName'))
     ->insert($view->textInput('DomainName'));

include 'WizFooter.php';

