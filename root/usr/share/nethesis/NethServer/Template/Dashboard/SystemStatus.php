<?php

foreach($view->getModule()->getChildren() as $child) {
    echo $view->inset($child->getIdentifier());
}
