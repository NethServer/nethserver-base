<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('SetIpAddress_header'));

echo $view->radioButton('bootproto', 'dhcp');

echo $view->fieldsetSwitch('bootproto', 'none', $view::FIELDSETSWITCH_EXPANDABLE)
    ->insert($view->textInput('ipaddr'))
    ->insert($view->textInput('netmask'))
    ->insert($view->textInput('gateway'));

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('Next', $view::BUTTON_SUBMIT))
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
;

echo $view->hidden('role');

$roleId = $view->getClientEventTarget('role');
$bootprotoId = $view->getClientEventTarget('bootproto');
$view->includeJavascript("
(function ( $ ) {
    function toggleDHCP(e, value) {
       if(value === undefined) {
            value = $('.${roleId}').val();
       }
       if (value === 'red') {
           $('.${bootprotoId}[value=dhcp]').prop('disabled',false);
       } else {
           // role is not red
           $('.${bootprotoId}[value=static]').trigger('click');
           $('.${bootprotoId}[value=dhcp]').prop('disabled',true);
       }
    }
    $(document).ready(function() {
       toggleDHCP();
       $('.${roleId}').on('nethguiupdateview', toggleDHCP);
    });

} ( jQuery ));
");