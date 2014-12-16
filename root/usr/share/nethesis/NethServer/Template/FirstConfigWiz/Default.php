<?php
/* @var $view \Nethgui\Renderer\Xhtml */

include "WizHeader.php";

echo $view->textLabel()->setAttribute('template', $T($view->getModule()->getIdentifier() . '_content'));

include "WizFooter.php";




