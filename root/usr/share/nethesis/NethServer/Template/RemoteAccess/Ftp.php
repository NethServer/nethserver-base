<?php

// echo $view->header()->setAttribute('template', 'Ftp service');

foreach ($view['statusOptions'] as $value) {
    $fieldset = $view->fieldsetSwitch('status', $value);

    if ($value == 'anyNetwork') {
        $fieldset->insert($view->checkbox('acceptPasswordFromAnyNetwork', '1'));
    }

    echo $fieldset;
}

echo $view->buttonList($view::BUTTON_SUBMIT);

