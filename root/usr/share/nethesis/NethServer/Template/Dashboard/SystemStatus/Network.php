<?php
echo "<div class='dashboard-item'>";
echo "<dl>";
echo $view->header()->setAttribute('template',$T('network_title'));
echo "<dt>".$T('hostname_label')."</dt><dd>"; echo $view->textLabel('hostname'); echo "</dd>";
echo "<dt>".$T('domain_label')."</dt><dd>"; echo $view->textLabel('domain'); echo "</dd>";
echo "<dt>".$T('gateway_label')."</dt><dd>"; echo $view->textLabel('gateway'); echo "</dd>";
echo "<dt>".$T('dns_label')."</dt><dd>"; echo $view->textLabel('dns'); echo "</dd>";
echo "</dl>";
echo "</div>";

$interfaces_title = $T('interfaces_title');
$interfaces_id = $view->getClientEventTarget('interfaces');
$moduleUrl = json_encode($view->getModuleUrl("/Dashboard/SystemStatus/Network"));
echo "<div class='dashboard-item interface-item'>";
echo $view->header()->setAttribute('template',$interfaces_title);
echo "<div class='{$interfaces_id}'></div>";
echo "</div>";

$view->includeJavascript("
(function ( $ ) {

   var last_RX = {};
   var last_TX = {};
   var last_run = new Date().getTime() / 1000;

   function loadPage() {
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: $moduleUrl
        });
    } 
 
   $(document).ready(function() {

       $('.$interfaces_id').on('nethguiupdateview', function(event, value, httpStatusCode) {
           var now = new Date().getTime() / 1000;
           var delta = now - last_run;
           last_run = now;
           if (delta <= 0) delta = 1;
           $(this).empty();
           for (var i=0; i<value.length; i++) {
                var str = '<dl>';
                var name = '';
                var stats;
                var role;
                var link;
                var speed;
                for (var j=0; j<value[i].length; j++) {
                     if (value[i][j][0] == 'role') {
                         role = value[i][j][1];
                         var res = role.match(/\d/g);
                         role = role.replace(res,'')
                     } else if (value[i][j][0].toLowerCase() == 'name') {
                         name = value[i][j][1];
                     } else if (value[i][j][0].toLowerCase() == 'link') {
                         link = value[i][j][1];
                     } else if (value[i][j][0].toLowerCase() == 'speed') {
                         speed = value[i][j][1];
                     } else if (value[i][j][0].toLowerCase() == 'stats') {
                         stats = value[i][j][1];
                     } else {
                         str = str + '<dt>'+value[i][j][0]+'</dt><dd>'+value[i][j][1]+'</dd>';
                     }
                }
                if (typeof last_RX[name] == 'undefined') {
                    last_RX[name] = stats.RX_bytes;
                    last_TX[name] = stats.TX_bytes;
                }
                var recv = Math.ceil(((stats.RX_bytes-last_RX[name])/delta)/1024);
                var sent = Math.ceil(((stats.TX_bytes-last_TX[name])/delta)/1024);
                if (link) {
                    str = str + '<dt>Link</dt><dd><span class=\"green\">OK</span> (' + speed +') </dd>';
                    str += '<span class=\"bold\">RX: </span>'+recv+' KB/s <span class=\"spacer-left bold\">TX: </span>'+sent+' KB/s</dd>';
                } else {
                    str = str + '<dt>Link</dt><dd> - </dd>';
                }
                str = '<div><h2 class=\'interface-'+role+'\'>'+name+'</h2><dl>'+str+'</dl></div>';
                $(this).append(str);
           }
       });

   });
})( jQuery);
");

$view->includeCss("
    .interface-green {
        color: green; 
    }
    .interface-red {
        color: red;
    }
    .green {
        color: green;
        font-weight: bold;
        margin-right: 10px;,
    }
    .bold {
        font-weight: bold;
    }
    .interface-item {
        min-height: 100px;
    }
    .spacer-left {
        margin-left: 10px;
    }

");

