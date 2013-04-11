<?php

if ($view->getModule()->getIdentifier() == 'update') {
    $headerText = 'update_header_label';
} else {
    $headerText = 'create_header_label';
}

$role_id = $view->getClientEventTarget('role');
$bootproto_id = $view->getClientEventTarget('bootproto');

echo $view->header()->setAttribute('template',$T($headerText));

echo "<div>";
echo "<dl>";
echo "<dt>".$T('link_label')."</dt><dd>".$view->textLabel('link')."</dd>";
echo "<dt>".$T('model_label')."</dt><dd>".$view->textLabel('model')."</dd>";
echo "<dt>".$T('speed_label')."</dt><dd>".$view->textLabel('speed')."</dd>";
echo "<dt>".$T('driver_label')."</dt><dd>".$view->textLabel('driver')."</dd>";
echo "<dt>".$T('bus_label')."</dt><dd>".$view->textLabel('bus')."</dd>";
echo "</dl>";
echo "</div>";

echo $view->panel()
    ->insert($view->textInput('device', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0)))
    ->insert($view->textInput('hwaddr', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0)))
    ->insert($view->selector('role', $view::SELECTOR_DROPDOWN))
    ->insert($view->fieldsetSwitch('bootproto', 'static', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('ipaddr'))
        ->insert($view->textInput('netmask'))
        ->insert($view->textInput('gateway'))
     )
    ->insert($view->fieldsetSwitch('bootproto', 'dhcp'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL);

$view->includeCSS("
  dl {
    padding: 0.2em;
    margin-bottom: 0.5em;
  }
  dt {
    float: left;
    clear: left;
    width: 100px;
    text-align: right;
    font-weight: bold;
  }
  dt:after {
    content: ':';
  }
  dd {
    margin: 0 0 0 110px;
    padding: 0 0 0.2em 0;
  }
");

$view->includeJavascript("
(function ( $ ) {
    // FIXME: define in jquery.nethgui.base.js -- A translator helper:
    var T = function () {
        return $.Nethgui.Translator.translate.apply($.Nethgui.Translator, Array.prototype.slice.call(arguments, 0));
    };

    function toggleDHCP() {
       if ($('.$role_id').val().indexOf('red') !== -1) {
           //$('.".$bootproto_id."[value=dhcp]').removeAttr('disabled');
           $('.".$bootproto_id."[value=dhcp]').parent().parent().FieldsetSwitch('enable');
       } else { //not red
           //$('.".$bootproto_id."[value=dhcp]').attr('disabled','disabled');
          // $('.".$bootproto_id."[value=static]').attr('checked',true);
          //   $('.".$bootproto_id."[value=static]').parent().parent().FieldsetSwitch('enable');
           $('.".$bootproto_id."[value=static]').trigger('click');
           $('.".$bootproto_id."[value=dhcp]').parent().parent().FieldsetSwitch('disable');
           $('.".$bootproto_id."[value=dhcp]').attr('disabled','disabled');
console.log('.".$bootproto_id."[value=dhcp]');
       }
    }


    $(document).ready(function() {
       toggleDHCP();
       $('.$role_id').change(function() {
           toggleDHCP();
       });

       $('.$role_id').on('nethguiupdateview', function(event, value, httpStatusCode) {
           toggleDHCP();
       });
    });

} ( jQuery ));
");
