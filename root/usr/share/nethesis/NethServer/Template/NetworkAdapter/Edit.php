<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('deviceInfos')->setAttribute('template', $T('Edit_header'));
echo $view->textLabel('deviceInfos')->setAttribute('template', $T('Edit_description'))->setAttribute('tag', 'div')->setAttribute('class', 'labeled-control wspreline');
echo $view->selector('role', $view::SELECTOR_DROPDOWN);
echo $view->radioButton('bootproto', 'dhcp');
echo $view->fieldsetSwitch('bootproto', 'none', $view::FIELDSETSWITCH_EXPANDABLE)
    ->insert($view->textInput('ipaddr'))
    ->insert($view->textInput('netmask'))
    ->insert($view->textInput('gateway'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

$view->includeCSS("
  tr.free td:first-child  {
      font-weight: normal;
      color: #333;
  }
  tr td:first-child  {
      font-weight: bold;
      color: #333;
  }
  tr.red td:first-child  {
       color: red;
  }
  tr.blue td:first-child  {
       color: blue;
  }
  tr.orange td:first-child  {
       color: orange;
  }
  tr.green td:first-child  {
       color: green;
  }
  tr.grey td:first-child  {
       color: grey;
  }
");

$roleId = $view->getUniqueId('role');
$bootprotoId = $view->getClientEventTarget('bootproto');
$gatewayId = $view->getUniqueId('gateway');
$view->includeJavascript("
(function ( $ ) {
    var updateFormState = function () {

        // hide `gateway` field if role is not green or red
        if ($('#${roleId}').val().indexOf('green') !== -1 || $('#${roleId}').val().indexOf('red') !== -1) {
            $('#${gatewayId}').prop('disabled', false).parent().show();
        } else {
            $('#${gatewayId}').prop('disabled', true).parent().hide();
        }

        // show DHCP/Static radio buttons only for red role
        if ($('#${roleId}').val().indexOf('red') !== -1) {
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
        $('#${roleId}').on('nethguiupdateview change', updateFormState);
    });
} ( jQuery ));
");
