<?php

/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header('name')->setAttribute('template', $T('EditModule_header'));

echo $view->selector('components', $view::SELECTOR_MULTIPLE | $view::LABEL_NONE);

echo $view->hidden('name');

echo $view->buttonList()
    ->insert($view->button('Run', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;
