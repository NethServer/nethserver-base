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
echo "<div class='dashboard-item'>";
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
            url: '/Dashboard/SystemStatus/Network'
        });
    } 
 
   $(document).ready(function() {
       loadPage();
       setTimeout(loadPage,1000); // populate data for the first time 
       // reload page after 30 seconds
       setInterval(loadPage,30000);

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
                for (var j=0; j<value[i].length; j++) {
                     if (value[i][j][0] == 'name') {
                         name = value[i][j][1];
                     } else if (value[i][j][0] == 'stats') {
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
                str += '<dt>RX</dt><dd>'+recv+' KB/s</dd>';
                str += '<dt>TX</dt><dd>'+sent+' KB/s</dd>';
                str = '<div><h2>'+name+'</h2><dl>'+str+'</dl></div>';
                $(this).append(str);
           }
       });

   });
})( jQuery);
");
