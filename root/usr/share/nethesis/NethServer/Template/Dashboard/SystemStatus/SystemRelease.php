<?php

echo $view->panel()
    ->insert($view->textLabel('release')->setAttribute('template',$T('release_label: ${0}')));

