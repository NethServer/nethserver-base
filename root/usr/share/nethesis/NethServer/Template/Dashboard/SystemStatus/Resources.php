<?php

echo "<div id='Dashboard_SystemStatus_Resources_loading'>".$T('Loading')."...</div>";

echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$T('general_title'));
echo "<dl>";
echo "<dt>".$T('load1_label')."</dt><dd>"; echo $view->textLabel('load1'); echo "</dd>";
echo "<dt>".$T('load5_label')."</dt><dd>"; echo $view->textLabel('load5'); echo "</dd>";
echo "<dt>".$T('load15_label')."</dt><dd>"; echo $view->textLabel('load15'); echo "</dd>";
echo "<dt>".$T('uptime_label')."</dt>";
echo "<dd>";
echo $view->textLabel('days').$T('days_label')." ";
echo $view->textLabel('hours').$T('hours_label')." ";
echo $view->textLabel('minutes').$T('minutes_label')." ";
echo $view->textLabel('seconds').$T('seconds_label');
echo "</dd>";
echo "<dt>".$T('time_label')."</dt><dd>"; echo $view->textLabel('time'); echo "</dd>";
echo "</dl>";
echo "</div>";

echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$T('hardware_title'));
echo "<dl>";
echo "<dt>".$T('sys_vendor_label')."</dt><dd>"; echo $view->textLabel('sys_vendor'); echo "</dd>";
echo "<dt>".$T('product_name_label')."</dt><dd>"; echo $view->textLabel('product_name'); echo "</dd>";
echo "<dt>".$T('cpu_model_label')."</dt><dd>"; echo $view->textLabel('cpu_model'); echo "</dd>";
echo "<dt>".$T('cpu_num_label')."</dt><dd>"; echo $view->textLabel('cpu_num'); echo "</dd>";
echo "</dl>";
echo "</div>";



$memory_title = $T('memory_title');
$phys_memory_title = $T('phys_memory_title');
$memory_id = $view->getClientEventTarget('memory');
$swap_id = $view->getClientEventTarget('swap');
$swap_title = $T('swap_title');
echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$memory_title);
echo "<dl class='$memory_id'></dl>";
echo "<div id='memory_plot'><div id='memory_label' class='progress-label'></div></div>";
echo "<dl class='$swap_id'></dl>";
echo "<div id='swap_plot'><div id='swap_label' class='progress-label'></div></div>";
echo "</div>";


$root_title = $T('root_title');
$root_df_id = $view->getClientEventTarget('root_df');
$moduleUrl = json_encode($view->getModuleUrl("/Dashboard/SystemStatus/Resources"));
echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$root_title);
echo "<dl class='$root_df_id'></dl>";
echo "<div id='root_plot'><div id='root_label' class='progress-label'></div></div>";
echo "</div>";

$view->includeCSS("
    #Dashboard_SystemStatus_Resources_loading {
        text-align: center;
        font-size: 1.2em;
    }
    .dashboard-item .progress-label {
        position: absolute;
        left: 50%;
        top: 4px;
    }
    .dashboard-item .ui-progressbar {
        position: relative;
    }
    .$swap_id {
        margin-top: 8px;
    }
");

$view->includeJavascript("
(function ( $ ) {
    // FIXME: define in jquery.nethgui.base.js -- A translator helper:
    var T = function () {
        return $.Nethgui.Translator.translate.apply($.Nethgui.Translator, Array.prototype.slice.call(arguments, 0));
    };

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

    function loadPage() {
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: $moduleUrl
        });
    } 


    function refreshMasonry() {
        if ($(window).width() > 500) {
            $('#Dashboard_SystemStatus').masonry();
        }
    }

    function printLabels(selector, value, format) {
         if(typeof(format)==='undefined') format = 0;
         $(selector).empty();
         for (index = 0; index < value.length; ++index) {
             if (format) {
                 $(selector).append('<dt>'+value[index][0]+'</dt><dd>'+format_df(value[index][1])+'</dd>');
             } else {
                 $(selector).append('<dt>'+value[index][0]+'</dt><dd>'+value[index][1]+' MB</dd>');
             }
         }
    }

    function updateProgress(name, max, value) {
         $('#'+name+'_plot').progressbar({max: max, value: value });
         p = value/max*100;
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
         $('#'+name+'_label').text(p.toPrecision(2)+'%');
         progressbarValue = $('#'+name+'_plot').find('.ui-progressbar-value');
         progressbarValue.css({ 'background': color });
    }
   
    function refresh() {
         $('#Dashboard_SystemStatus_Resources_loading').hide();
         $('.dashboard-item').show();
         refreshMasonry();
    }

    $(document).ready(function() {
        loadPage();
        $('.dashboard-item').hide(); 
            
        if ($(window).width() > 500) {
            $('#Dashboard_SystemStatus').masonry({ itemSelector: '.dashboard-item'  }); 
        }

        $( '#Dashboard' ).bind( 'tabsshow', function(event, ui) {
            if (ui.panel.id == 'Dashboard_SystemStatus') {
                refreshMasonry();
            }
        });       
       
        $('#Dashboard_SystemStatus_Resources').on('nethguixreload', function (e, arg) { 
            $.debug(e, arg)
            setTimeout(loadPage,arg);
        });
 
        $('.$root_df_id').on('nethguiupdateview', function(event, value, httpStatusCode) {
            printLabels('.$root_df_id',value, 1);
            updateProgress('root', value[0][1], value[1][1]);
            refresh();
        }); 


        $('.$memory_id').on('nethguiupdateview', function(event, value, httpStatusCode) { 
            printLabels('.$memory_id',value);
            $('#memory_plot').progressbar({max: value[0][1], value: value[1][1] });
            p =  value[1][1]/value[0][1]*100;
            $('#memory_label').text(p.toPrecision(2)+'%');
            progressbarValue = $('#memory_plot').find('.ui-progressbar-value');
            refresh();
        }); 

        $('.$swap_id').on('nethguiupdateview', function(event, value, httpStatusCode) {
            printLabels('.$swap_id',value);
            updateProgress('swap', value[0][1], value[1][1]);
            refresh();
        });

    });
} ( jQuery ));
");   
