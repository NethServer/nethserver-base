<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('deviceInfos')->setAttribute('template', $T('Edit_header'));
echo $view->textLabel('deviceInfos')->setAttribute('template', $T('Edit_description'))->setAttribute('tag', 'div')->setAttribute('class', 'labeled-control wspreline');
echo $view->selector('role', $view::SELECTOR_DROPDOWN);
echo $view->radioButton('bootproto', 'dhcp');
echo $view->fieldsetSwitch('bootproto', 'static', $view::FIELDSETSWITCH_EXPANDABLE)
    ->insert($view->textInput('ipaddr'))
    ->insert($view->textInput('netmask'))
    ->insert($view->textInput('gateway'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);