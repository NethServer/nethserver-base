<?php

echo $view->header('Id')->setAttribute('template', $T('Remove ${0}'));
echo $view->buttonList()
    ->insert($view->button('Remove', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL));