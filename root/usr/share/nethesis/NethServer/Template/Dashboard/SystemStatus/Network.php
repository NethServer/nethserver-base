<?php
echo "<div class='dashboard-item'>";
echo "<dl>";
echo $view->header()->setAttribute('template',$T('network_title'));
echo "<dt>".$T('hostname_label')."</dt><dd>"; echo $view->textLabel('hostname'); echo "</dd>";
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
     $name = str_replace('.','_',$interface['name']);
     echo "<li><a href='#interface-info-$name' class='{$interface['role']}'>{$interface['name']}</a></li>";
}
echo "</ul>";
foreach ($view['interfaces'] as $interface) {
     $name = str_replace('.','_',$interface['name']);
     echo "<div id='interface-info-$name'>";
     if ($interface['role']) {
         echo "<dt>".$T('role_label')."</dt><dd>{$interface['role']}</dd>";
     }
     if ($interface['hwaddr']) {
         echo "<dt>".$T('hwaddr_label')."</dt><dd>{$interface['hwaddr']}</dd>";
     }
     if ($interface['link']) {
         echo "<dt>".$T('link_label')."</dt><dd>";
         echo "<span class='green'>OK</span>";
         if ($interface['speed']) {
             echo "<span class='speed'>({$interface['speed']})</span>";
         }
         echo "</dd>";
     }
     if($interface['ipaddr']) {
         echo "<dt>".$T('ipaddr_label')." / ".$T('netmask_label')."</dt><dd>";
         echo "{$interface['ipaddr']}";
         echo "</dd>";
     }
     if(isset($interface['gateway']) && $interface['gateway']) {
         echo "<dt>".$T('gateway_label')."</dt><dd>".$interface['gateway']."</dd>";
     }
     if ($interface['bootproto']) {
         echo "<dt>".$T('bootproto_label')."</dt><dd>".$T($interface['bootproto']."_label")."</dd>";
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
        font-weight: bold;
    }
    .green {
        color: green !important;
        font-weight: bold;
    }
    .orange {
        color: orange !important;
        font-weight: bold;
    }
    .blue {
        color: blue !important;
        font-weight: bold;
    }
    

    span.speed {
        margin-left: 10px;,
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

