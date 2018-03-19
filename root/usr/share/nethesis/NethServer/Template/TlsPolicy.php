<?php

echo $view->header()->setAttribute('template', $T('TlsPolicy_Description'));

echo $view->panel()
    ->insert($view->selector('policy', $view::SELECTOR_DROPDOWN));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);

