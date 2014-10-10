<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('role_header')->setAttribute('template', $T('SetIpAddress_header'));

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
$gatewayId = $view->getUniqueId('gateway');
$view->includeJavascript("
(function ( $ ) {
    var updateFormState = function () {

        // hide `gateway` field if role is not green or red
        if ($('.${roleId}').val() === 'green' || $('.${roleId}').val() === 'red') {
            $('#${gatewayId}').prop('disabled', false).parent().show();
        } else {
            $('#${gatewayId}').prop('disabled', true).parent().hide();
        }

        // show DHCP/Static radio buttons only for red role
        if ($('.${roleId}').val() === 'red') {
            $('#${gatewayId}').closest('fieldset').css('margin-left', '');
            $('.${bootprotoId}[value=none]').trigger('click').parent().show();
            $('.${bootprotoId}[value=dhcp]').prop('disabled', false).parent().show();
        } else {
            $('#${gatewayId}').closest('fieldset').css('margin-left', 0);
            $('.${bootprotoId}[value=none]').trigger('click').parent().hide();
            $('.${bootprotoId}[value=dhcp]').prop('disabled', true).parent().hide();
        }
    };

    // bind the updateFormState method after widgets have been created:
    $('.${roleId}').on('nethguicreate', function() {
        $('.${roleId}').on('nethguiupdateview', updateFormState);
    });
} ( jQuery ));
");
