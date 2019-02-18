<?php

/* @var $view Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('Configuration_header'));

echo $view->fieldset('')->setAttribute('template', $T('YumCron_label'))
    ->insert($view->fieldsetSwitch('download', 'yes', $view::FIELDSETSWITCH_CHECKBOX | $view::FIELDSETSWITCH_EXPANDABLE)->setAttribute('uncheckedValue', 'no')
        ->insert($view->checkBox('applyUpdate', 'yes')->setAttribute('uncheckedValue', 'no')))
    ->insert($view->fieldsetSwitch('messages', 'yes', $view::FIELDSETSWITCH_CHECKBOX | $view::FIELDSETSWITCH_EXPANDABLE)->setAttribute('uncheckedValue', 'no')
        ->insert($view->textArea('customMail', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30')))
;

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('BackToModules', $view::BUTTON_LINK))
    ->insert($view->button('Save', $view::BUTTON_SUBMIT))
;

$view->includeCss('
.policy-description {
    max-width: 40em;
}
');