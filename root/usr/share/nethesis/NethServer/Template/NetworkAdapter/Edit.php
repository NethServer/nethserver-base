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

echo $view->fieldset()->setAttribute('template', $T('multiwan_label'))
    ->insert($view->textInput('ProviderName')->setAttribute('placeholder', $view['defaultProviderName']))
    ->insert($view->textInput('Weight')->setAttribute('placeholder', $view['defaultWeight']));

echo $view->fieldset()->setAttribute('template', $T('trafficshaping_label'))
    ->insert($view->textInput('FwInBandwidth'))
    ->insert($view->textInput('FwOutBandwidth'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

$view->includeCSS("
  tr.free td:first-child {
      font-weight: normal;
      color: #333;
  }
  tr td:first-child  {
      font-weight: bold;
      color: #333;
  }
  tr.red td:first-child, .row.configured .red  {
       color: red;
  }
  tr.blue td:first-child, .row.configured .blue  {
       color: blue;
  }
  tr.orange td:first-child, .row.configured .orange  {
       color: orange;
  }
  tr.green td:first-child, .row.configured .green  {
       color: green;
  }
  tr.grey td:first-child, .row.configured .grey  {
       color: grey;
  }
");

$roleId = $view->getUniqueId('role');
$bootprotoId = $view->getClientEventTarget('bootproto');
$gatewayId = $view->getUniqueId('gateway');
$MultiwanNameId = $view->getUniqueId('ProviderName');
$MultiwanWeightId = $view->getUniqueId('Weight');
$TrafficShapingId = $view->getUniqueId('FwInBandwidth');
$view->includeJavascript("
(function ( $ ) {
    var updateFormState = function () {

        // hide `gateway`, DHCP/Static fields if role is not green or red
        if ($('#${roleId}').val().indexOf('green') !== -1 || $('#${roleId}').val().indexOf('red') !== -1) {
            $('#${gatewayId}').prop('disabled', false).parent().show();
            $('#${gatewayId}').closest('fieldset').css('margin-left', '');
            $('.${bootprotoId}[value=none]').trigger('click').parent().show();
            $('.${bootprotoId}[value=dhcp]').prop('disabled', false).parent().show();
        } else {
            $('#${gatewayId}').prop('disabled', true).parent().hide();
            $('#${gatewayId}').closest('fieldset').css('margin-left', 0);
            $('.${bootprotoId}[value=none]').trigger('click').parent().hide();
            $('.${bootprotoId}[value=dhcp]').prop('disabled', true).parent().hide();
        }

        // show Multiwan and TrafficShaping options parameters only for red role
        if ($('#${roleId}').val().indexOf('red') !== -1) {
            $('.${MultiwanNameId}').prop('disabled', false).parents('fieldset').first().show();
            $('.${TrafficShapingId}').prop('disabled', false).parents('fieldset').first().show();
        } else {
            $('.${MultiwanNameId}').prop('disabled', true).parents('fieldset').first().hide();
            $('.${TrafficShapingId}').prop('disabled', true).parents('fieldset').first().hide();
        }
    };

    // bind the updateFormState method after widgets have been created:
    $('.${roleId}').on('nethguicreate', function() {
        $('#${roleId}').on('nethguiupdateview change', updateFormState);
    });
} ( jQuery ));
");
