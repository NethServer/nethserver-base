<?php

$view->includeFile('NethServer/Js/jquery.masonry.min.js');
$view->useFile("css/jquery.jqplot.min.css");
$view->useFile("js/jquery.jqplot.min.js");
$view->useFile("js/jqplot.pieRenderer.min.js");

$view->includeCSS("
  div.dashboard-item {
    margin: 5px;
    padding: 5px;
    border: 1px solid #ccc;
    float: left;
    width: 33%;
  }

  .dashboard-item dt {
    float: left;
    clear: left;
    width: 100px;
    text-align: right;
    font-weight: bold;
  }
  .dashboard-item dt:after {
    content: \":\";
  }
  .dashboard-item dd {
    margin: 0 0 0 110px;
    padding: 0 0 0.5em 0;
  }
  .dashboard-item table.jqplot-table-legend {
    width:auto;
 }
");

echo "<div class='dashboard-item'>";
echo "<dl>";
echo $view->header()->setAttribute('template',$T('general_title'));
echo "<dt>".$T('load1_label')."</dt><dd>"; echo $view->textLabel('load1'); echo "</dd>";
echo "<dt>".$T('load5_label')."</dt><dd>"; echo $view->textLabel('load5'); echo "</dd>";
echo "<dt>".$T('load15_label')."</dt><dd>"; echo $view->textLabel('load15'); echo "</dd>";
echo "<dt>".$T('cpu_num_label')."</dt><dd>"; echo $view->textLabel('cpu_num'); echo "</dd>";
echo "<dt>".$T('uptime_label')."</dt>";
echo "<dd>";
echo $view->textLabel('days').$T('days_label')." ";
echo $view->textLabel('hours').$T('hours_label')." ";
echo $view->textLabel('minutes').$T('minutes_label')." ";
echo $view->textLabel('seconds').$T('seconds_label');
echo "</dd>";
echo "</div>";


$memory_title = $T('memory_title');
$phys_memory_title = $T('phys_memory_title');
$memory_id = $view->getClientEventTarget('memory');
$swap_id = $view->getClientEventTarget('swap');
$swap_title = $T('swap_title');
echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$memory_title);
echo "<dl class='$memory_id'></dl>";
echo "<div id='memory_plot' style='height:250px;width:250px'></div>";
echo "<dl class='$swap_id'></dl>";
echo "<div id='swap_plot' style='height:250px;width:250px'></div>";
echo "</div>";


$root_title = $T('root_title');
$root_df_id = $view->getClientEventTarget('root_df');
echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$root_title);
echo "<dl class='$root_df_id'></dl>";
echo "<div id='root_plot' style='height:250px;width:250px'></div>";
echo "</div>";

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
 

    $(document).ready(function() {
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: '/Dashboard/SystemStatus'
        });

        $('.$root_df_id').on('nethguiupdateview', function(event, value, httpStatusCode) { 
            // draw plot excluding total field
            var root_plot = jQuery.jqplot ('root_plot', [value.slice(1,3)], 
            { 
                seriesDefaults: {
                    renderer: jQuery.jqplot.PieRenderer, 
                    rendererOptions: { showDataLabels: true }
                }, 
                legend: { show:true, location: 's' },
                title: '$root_title'
            });
 
            //add text label
            for (var i=0; i<value.length; i++) {
                $(this).append('<dt>'+value[i][0]+'</dt><dd>'+format_df(value[i][1])+'</dd>');
            }

            // force layout refresh
            if ($(window).width() > 500) {
                $('#Dashboard_SystemStatus').masonry('reload'); 
            }

        }); 


        $('.$memory_id').on('nethguiupdateview', function(event, value, httpStatusCode) { 
            // draw plot excluding total field
            var memory_plot = jQuery.jqplot ('memory_plot', [value.slice(1,3)], 
            { 
                seriesDefaults: {
                    renderer: jQuery.jqplot.PieRenderer, 
                    rendererOptions: { showDataLabels: true }
                }, 
                legend: { show:true, location: 's' },
                title: '$phys_memory_title'
            });
 
            //add text label
            for (var i=0; i<value.length; i++) {
                $(this).append('<dt>'+value[i][0]+'</dt><dd>'+value[i][1]+' MB</dd>');
            }
        }); 

        $('.$swap_id').on('nethguiupdateview', function(event, value, httpStatusCode) {
            // draw plot excluding total field
            var swap_plot = jQuery.jqplot ('swap_plot', [value.slice(1,3)],
            {
                seriesDefaults: {
                    renderer: jQuery.jqplot.PieRenderer,
                    rendererOptions: { showDataLabels: true }
                },
                legend: { show:true, location: 's' },
                title: '$swap_title'
            });

            //add text label
            for (var i=0; i<value.length; i++) {
                $(this).append('<dt>'+value[i][0]+'</dt><dd>'+value[i][1]+' MB</dd>');
            }
        });

        if ($(window).width() > 500) {
            $('#Dashboard_SystemStatus').masonry({ itemSelector: '.dashboard-item' }); 
        }
    });
} ( jQuery ));
");   
