<?php

echo $view->header()->setAttribute('template', $T('TlsPolicy_Description'));

echo $view->panel()
    ->insert($view->selector('policy', $view::SELECTOR_DROPDOWN))
    ->insert($view->fieldset()->setAttribute('template', $T('ServiceExemption_label')))
    ->insert($view->checkbox('HttpdAdminExemptionStatus','enabled')->setAttribute('uncheckedValue', 'disabled'))
    ;

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);

