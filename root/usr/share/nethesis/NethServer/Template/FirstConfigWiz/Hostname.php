<?php

include 'WizHeader.php';

echo $view->panel()
     ->insert($view->textInput('FQDN'))
;

include 'WizFooter.php';

