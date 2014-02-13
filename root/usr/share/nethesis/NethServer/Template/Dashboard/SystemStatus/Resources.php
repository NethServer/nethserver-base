<?php

function formatDF($val, $printUnit = false) {
    $unit = 'MB';
    $val = $val / 1024; //MB
    if ($val >= 1024) {
        $val = $val / 1024; //GB
        $unit = 'GB';
    }
    return number_format($val,2) . ($printUnit?" $unit":'');
}

echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$T('general_title'));
echo "<dl>";
echo "<dt>".$T('load_label')."</dt><dd>"; echo $view->textLabel('load1').' / '.$view->textLabel('load5').' / '.$view->textLabel('load15'); echo "</dd>";
echo "<dt>".$T('uptime_label')."</dt>";
echo "<dd>";
echo $view->textLabel('hours')." ".$T('hours_label')." ";
echo $view->textLabel('minutes')." ".$T('minutes_label')." ";
echo "</dd>";
echo "<dt>".$T('time_label')."</dt><dd>"; echo $view->textLabel('time'); echo "</dd>";
echo "</dl>";
echo "</div>";

echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$T('hardware_title'));
echo "<dl>";
echo "<dt>".$T('sys_vendor_label')."</dt><dd>"; echo $view->textLabel('sys_vendor'); echo "</dd>";
echo "<dt>".$T('product_name_label')."</dt><dd>"; echo $view->textLabel('product_name'); echo "</dd>";
echo "<dt>".$T('cpu_model_label')."</dt><dd> "; echo $view->textLabel('cpu_num'); 
echo " x "; echo $view->textLabel('cpu_model'); echo "</dd>";
echo "</dl>";
echo "</div>";



$memory_title = $T('memory_title');
$phys_memory_title = $T('phys_memory_title');
$swap_title = $T('swap_title');
echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$memory_title);
echo "<dl>";
echo "<dt>".$T('usage_label')."</dt><dd>{$view['memory']['used']} / {$view['memory']['total']} MB</dd>";
echo "<dt>".$T('mem_free_label')."</dt><dd>{$view['memory']['free']} MB</dd>";
echo "</dl>";
echo "<div id='memory_plot' value='{$view['memory']['used']}' max='{$view['memory']['total']}'><div id='memory_label' class='progress-label'></div></div>";
echo "<dl>";
echo "<dt>".$T('usage_label')."</dt><dd>{$view['swap']['used']} / {$view['swap']['total']} MB</dd>";
echo "<dt>".$T('swap_free_label')."</dt><dd>{$view['swap']['free']} MB</dd>";
echo "</dl>";
echo "<div id='swap_plot' value='{$view['swap']['used']}' max='{$view['swap']['total']}'><div id='swap_label' class='progress-label'></div></div>";
echo "</div>";


$root_title = $T('root_title');
$root_df_id = $view->getClientEventTarget('root_df');
$moduleUrl = json_encode($view->getModuleUrl("/Dashboard/SystemStatus/Resources"));
echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$root_title);
echo "<dl>";
echo "<dt>".$T('usage_label')."</dt><dd>".formatDF($view['df']['/']['used']) ." / " . formatDF($view['df']['/']['total'],1)."</dd>";
echo "<dt>".$T('avail_label')."</dt><dd>".formatDF($view['df']['/']['free'],1)."</dd>";
echo "</dl>";
echo "<div id='root_plot' value='{$view['df']['/']['used']}' max='{$view['df']['/']['total']}' ><div id='root_label' class='progress-label'></div></div>";
echo "</div>";

$view->includeCSS("
    .dashboard-item .progress-label {
        position: absolute;
        left: 50%;
        top: 4px;
    }
    .dashboard-item .ui-progressbar {
        position: relative;
    }
");

$view->includeJavascript("
(function ( $ ) {

    function format_df(val)
    {
      val = val / 1024; //MB
      if (val >= 1024) {
        val = val / 1024; //GB
        return val.toFixed(2)+' GB';
      } else {
        return val.toFixed(2)+' GB';
      }
    }

    function refreshMasonry() {
        if ($(window).width() > 500) {
            $('#Dashboard_SystemStatus').masonry({ 
                itemSelector: '.dashboard-item',
                isAnimated: false,
            });
        }
    }

    function updateProgress(name, max, value, colorize) {
         p = value/max*100;
         $('#'+name+'_plot').progressbar( { value: p });
         $('#'+name+'_label').text(p.toPrecision(2)+'%');
         progressbarValue = $('#'+name+'_plot').find('.ui-progressbar-value');
    
         if (!colorize) {
             progressbarValue.css({ 'background': '#eee' });
             return;
         }

         color = 'green';
         if (p < 70) {
             color = 'green';
         } else if (p >=70 && p<=80) {
             color = 'yellow';
         } else if (p>80 && p<90) {
             color = 'orange';
         } else {
             color = 'red';
         }
         progressbarValue.css({ 'background': color });
         
    }
   
    $(document).ready(function() {
        updateProgress('memory', $('#memory_plot').attr('max'), $('#memory_plot').attr('value'), 0) ;
        updateProgress('swap', $('#swap_plot').attr('max'), $('#swap_plot').attr('value'), 1) ;
        updateProgress('root', $('#root_plot').attr('max'), $('#root_plot').attr('value'), 1) ;
        setTimeout(refreshMasonry,100);
        $( '#Dashboard' ).bind( 'tabsshow', function(event, ui) {
            if (ui.panel.id == 'Dashboard_SystemStatus') {
                refreshMasonry();
            }
        });       
       
    });
} ( jQuery ));
");   
