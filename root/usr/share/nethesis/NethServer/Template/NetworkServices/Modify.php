<?php

echo $view->header('name')->setAttribute('template', $T('Update_Service_Header'));

echo $view->panel()->insert($view->panel()
    ->insert($view->fieldset()->setAttribute('template', $T('zones_label'))
        ->insert($view->selector('access', $view::SELECTOR_MULTIPLE | $view::LABEL_NONE))
    )
);


echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

