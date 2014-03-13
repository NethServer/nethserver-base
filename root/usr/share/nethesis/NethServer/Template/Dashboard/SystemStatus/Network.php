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
$moduleUrl = json_encode($view->getModuleUrl("/Dashboard/SystemStatus/Network"));
echo "<div class='dashboard-item interface-item'>";

echo $view->header()->setAttribute('template',$interfaces_title);

echo "<div id='interfaces-tabs'>";
echo "<ul>";
foreach ($view['interfaces'] as $interface) {
     echo "<li><a href='#interface-info-{$interface['name']}' class='{$interface['role']}'>{$interface['name']}</a></li>";
}
echo "</ul>";
foreach ($view['interfaces'] as $interface) {
     echo "<div id='interface-info-{$interface['name']}'>";
     echo "<dt>".$T('hwaddr_label')."</dt><dd>{$interface['hwaddr']}</dd>";
     echo "<dt>".$T('link_label')."</dt><dd>";
     if ($interface['link']) {
         echo "<span class='green'>OK</span> ({$interface['speed']})";
     } else {
         echo "-";
     }
     echo "</dd>";
     if ($interface['role']) {
         echo "<dt>".$T('ipaddr_label')." / ".$T('netmask_label')."</dt><dd>";
         if($interface['ipaddr']) {
             echo "{$interface['ipaddr']} / {$interface['netmask']}";
         } else {
             echo "-";
         }
         echo "</dd>";
         echo "<dt>".$T('bootproto_label')."</dt><dd>{$interface['bootproto']}</dd>";
     } 
     echo "</div>";
}
echo "</div>";

echo "</div>";

$view->includeJavascript("
(function ( $ ) {
 $( '#interfaces-tabs' ).tabs();
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

    #interfaces-tabs a {
        padding-left: 4px;
        padding-right: 4px;
        padding-bottom: 2px;
        padding-top: 4px;
    }
");

