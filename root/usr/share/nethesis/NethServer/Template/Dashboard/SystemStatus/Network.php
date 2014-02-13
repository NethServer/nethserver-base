<?php
echo "<div class='dashboard-item'>";
echo "<dl>";
echo $view->header()->setAttribute('template',$T('network_title'));
echo "<dt>".$T('hostname_label')."</dt><dd>"; echo $view->textLabel('hostname'); echo "</dd>";
echo "<dt>".$T('domain_label')."</dt><dd>"; echo $view->textLabel('domain'); echo "</dd>";
echo "<dt>".$T('gateway_label')."</dt><dd>"; echo $view->textLabel('gateway'); echo "</dd>";
if ($view['dns'] != '127.0.0.1') {
    echo "<dt>".$T('dns_label')."</dt><dd>"; echo $view->textLabel('dns'); echo "</dd>";
}
echo "</dl>";
echo "</div>";

$interfaces_title = $T('interfaces_title');
$interfaces_id = $view->getClientEventTarget('interfaces');
$moduleUrl = json_encode($view->getModuleUrl("/Dashboard/SystemStatus/Network"));
echo "<div class='dashboard-item interface-item'>";
echo $view->header()->setAttribute('template',$interfaces_title);
echo "<div>";
foreach ($view['interfaces'] as $interface) {
     echo "<a href='#' data='{$interface['name']}' class='interface-link {$interface['role']}'>{$interface['name']}</a>";
}
echo "</div>";
echo "<div class='{$interfaces_id}'></div>";
foreach ($view['interfaces'] as $interface) {
     echo "<div class='interface-info interface-{$interface['role']}' id='interface-info-{$interface['name']}'>";
     echo "<dt>".$T('hwaddr_label')."</dt><dd>{$interface['hwaddr']}</dd>";
     echo "<dt>".$T('link_label')."</dt><dd>";
     if ($interface['link']) {
         echo "<span class='green'>OK</span> ({$interface['speed']})";
     } else {
         echo "-";
     }
     echo "</dd>";
     if ($interface['role']) {
         echo "<dt>".$T('ipaddr_label')." / ".$T('netmask_label')."</dt><dd>{$interface['ipaddr']} / {$interface['netmask']}</dd>";
         echo "<dt>".$T('bootproto_label')."</dt><dd>{$interface['bootproto']}</dd>";
     } 
     echo "</div>";
}
echo "</div>";

$view->includeJavascript("
(function ( $ ) {
    $('.interface-info').hide();
    $('.interface-green').show();
    $('.interface-link').on('click', function(e) {
        $('.interface-info').hide();
        $('#interface-info-' + $(this).attr('data')).show();
    });
} ( jQuery ));
");   


$view->includeCss("
    .red {
        color: red !important;
    }
    .green {
        color: green !important;
        font-weight: bold;
        margin-right: 10px;,
    }
    .interface-info {
        padding: 5px;
    }

");

