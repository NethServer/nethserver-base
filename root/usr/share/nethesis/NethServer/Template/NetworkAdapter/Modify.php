<?php
$role_id = $view->getClientEventTarget('role');
$bootproto_id = $view->getClientEventTarget('bootproto');
$type_id = $view->getClientEventTarget('type');
$viewid = $view->getUniqueId();
$panel = $view->panel();

if ($view->getModule()->getIdentifier() == 'update') {
    echo $view->header()->setAttribute('template',$T('update_header_label'));

    echo "<div class='network_adatper'>";
    echo "<dl>";
    echo "<dt>".$T('link_label')."</dt><dd>".$view->textLabel('link')."</dd>";
    echo "<dt>".$T('model_label')."</dt><dd>".$view->textLabel('model')."</dd>";
    echo "<dt>".$T('speed_label')."</dt><dd>".$view->textLabel('speed')." Mb/s</dd>";
    echo "<dt>".$T('driver_label')."</dt><dd>".$view->textLabel('driver')."</dd>";
    echo "<dt>".$T('bus_label')."</dt><dd>".$view->textLabel('bus')."</dd>";
    echo "</dl>";
    echo "</div>";
        

    $panel->insert($view->textInput('device', $view::STATE_READONLY))
        ->insert($view->fieldsetSwitch('type', 'ethernet', $view::FIELDSETSWITCH_EXPANDABLE)
            ->insert($view->textInput('hwaddr', $view::STATE_READONLY)));

    
    // hide all types different from the selected one
    $view->includeJavascript('
    (function ( $ ) {
        var t =  $(".$type_id:checked");
        $(".'.$type_id.'[value!=\'+t+\']").parent().hide();
        if ( t != "ethernet" ) {
            $(".network_adatper").hide();
        }
    } ( jQuery ));
    ');

} else {
    echo $view->header()->setAttribute('template',$T('create_header_label'));
}

    $panel->insert($view->fieldsetSwitch('type', 'alias', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->selector('aliasInterface', $view::SELECTOR_DROPDOWN))
    )
    ->insert($view->fieldsetSwitch('type', 'bond', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->selector('bondInterface', $view::SELECTOR_MULTIPLE))
    )
    ->insert($view->fieldsetSwitch('type', 'bridge', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->selector('bridgeInterface', $view::SELECTOR_MULTIPLE))
    )
    ->insert($view->fieldsetSwitch('type', 'vlan', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('tag'))
        ->insert($view->selector('vlanInterface', $view::SELECTOR_DROPDOWN))
    )
    ->insert($view->selector('role', $view::SELECTOR_DROPDOWN))
    ->insert($view->fieldsetSwitch('bootproto', 'static', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('ipaddr'))
        ->insert($view->textInput('netmask'))
        ->insert($view->textInput('gateway'))
     )
    ->insert($view->fieldsetSwitch('bootproto', 'dhcp'));

echo $panel;
echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL);

$view->includeCSS("
  .network_adatper dl {
    padding: 0.2em;
    margin-bottom: 0.5em;
  }
  .network_adatper dt {
    float: left;
    clear: left;
    width: 100px;
    text-align: right;
    font-weight: bold;
  }
  .network_adatper dt:after {
    content: ':';
  }
  .network_adatper dd {
    margin: 0 0 0 110px;
    padding: 0 0 0.2em 0;
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
