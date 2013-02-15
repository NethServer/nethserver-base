<?php
$view->includeFile('NethServer/Js/jquery.nethserver.dashboard.systemstatus.js');

foreach($view->getModule()->getChildren() as $child) {
    echo $view->inset($child->getIdentifier());
}
