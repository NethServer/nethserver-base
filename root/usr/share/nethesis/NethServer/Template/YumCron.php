<?php
/* @var $view Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('YumCron_header'));

echo $view->panel()
->insert($view->literal('<div class="labeled-control generated-url-title">'.$T('WhenUpdatesAreAvailable').'</div>'))
->insert($view->fieldsetSwitch('download', 'yes', $view::FIELDSETSWITCH_CHECKBOX | $view::FIELDSETSWITCH_EXPANDABLE)
    ->setAttribute('uncheckedValue', 'no')
    ->insert($view->checkBox('applyUpdate', 'yes')->setAttribute('uncheckedValue', 'no')))

->insert($view->fieldsetSwitch('messages', 'yes', $view::FIELDSETSWITCH_CHECKBOX | $view::FIELDSETSWITCH_EXPANDABLE)
    ->setAttribute('uncheckedValue', 'no')
    ->insert($view->textArea('customMail', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30')))
;

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
