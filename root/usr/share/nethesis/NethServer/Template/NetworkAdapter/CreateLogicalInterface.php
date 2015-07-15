<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('CreateLogicalInterface_header'));


echo $view->selector('role', $view::SELECTOR_DROPDOWN);

echo $view
    //->panel()
    ->fieldset()->setAttribute('template', $T('InterfaceType_label'))
    ->insert($view->fieldsetSwitch('type', 'bond', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->selector('bond', $view::SELECTOR_MULTIPLE | $view::LABEL_NONE))
    )
    ->insert($view->fieldsetSwitch('type', 'bridge', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->selector('bridge', $view::SELECTOR_MULTIPLE | $view::LABEL_NONE))
    )
    ->insert($view->fieldsetSwitch('type', 'vlan', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('vlanTag'))
        ->insert($view->selector('vlan', $view::SELECTOR_DROPDOWN)->setAttribute('choices', 'bondDatasource'))
    )
    ->insert($view->radioButton('type', 'xdsl'))
;

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('Next', $view::BUTTON_SUBMIT))
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
;
