<?php

$view->useFile("js/jqplot.pieRenderer.min.js");

echo "<div id='Dashboard_SystemStatus_Resources_loading'>".$T('Loading')."...</div>";

echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$T('general_title'));
echo "<dl>";
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
echo "<div id='memory_plot' class='dashboard-graph'></div>";
echo "<dl class='$swap_id'></dl>";
echo "<div id='swap_plot' class='dashboard-graph'></div>";
echo "</div>";


$root_title = $T('root_title');
$root_df_id = $view->getClientEventTarget('root_df');
$moduleUrl = json_encode($view->getModuleUrl("/Dashboard/SystemStatus/Resources"));
echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$root_title);
echo "<dl class='$root_df_id'></dl>";
echo "<div id='root_plot' class='dashboard-graph'></div>";
echo "</div>";

$view->includeCSS("
    #Dashboard_SystemStatus_Resources_loading {
        text-align: center;
        font-size: 1.2em;
    }
");

$view->includeJavascript("
(function ( $ ) {
    // FIXME: define in jquery.nethgui.base.js -- A translator helper:
    var T = function () {
        return $.Nethgui.Translator.translate.apply($.Nethgui.Translator, Array.prototype.slice.call(arguments, 0));
    };

    var plots = [];

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

    function refreshPlots() {
        for (var plot in plots) {
            if (typeof plots[plot] !== 'undefined') {
                plots[plot].replot();
            }
        }
    }

    function refreshMasonry() {
        if ($(window).width() > 500) {
            $('#Dashboard_SystemStatus').masonry();
        }
    }

    $(document).ready(function() {
        loadPage();
        // reload page after 30 seconds
        setInterval(loadPage,30000);
        $('.dashboard-item').hide(); 
            
        if ($(window).width() > 500) {
            $('#Dashboard_SystemStatus').masonry({ itemSelector: '.dashboard-item'  }); 
        }

        $( '#Dashboard' ).bind( 'tabsshow', function(event, ui) {
            if (ui.panel.id == 'Dashboard_SystemStatus') {
                refreshMasonry();
                refreshPlots(); 
            }
        });       
        
        // draw plot excluding total field
        var plot = jQuery.jqplot ('root_plot', [['',1]], 
        { 
            seriesDefaults: {
                renderer: jQuery.jqplot.PieRenderer, 
                rendererOptions: { showDataLabels: true }
            }, 
            legend: { show:true, location: 's' },
            title: '$root_title'
         });
        plots['root'] = plot;

        plot = jQuery.jqplot ('memory_plot', [['',1]], 
        { 
            seriesDefaults: {
                renderer: jQuery.jqplot.PieRenderer, 
                rendererOptions: { showDataLabels: true }
            }, 
            legend: { show:true, location: 's' },
            title: '$phys_memory_title'
        });
        plots['memory'] = plot;

        plot = jQuery.jqplot ('swap_plot', [['',1]],
        {
            seriesDefaults: {
                renderer: jQuery.jqplot.PieRenderer,
                rendererOptions: { showDataLabels: true }
            },
            legend: { show:true, location: 's' },
            title: '$swap_title'
        });
        plots['swap'] = plot;
       

        $('.$root_df_id').on('nethguiupdateview', function(event, value, httpStatusCode) {
            $(this).empty();
            $('#Dashboard_SystemStatus_Resources_loading').hide();
            $('.dashboard-item').show(); 
            plots['root'].series[0].data = value.slice(1,3);
            plots['root'].replot();
            //add text label
            for (var i=0; i<value.length; i++) {
                $(this).append('<dt>'+value[i][0]+'</dt><dd>'+format_df(value[i][1])+'</dd>');
            }

            refreshMasonry();
        }); 


        $('.$memory_id').on('nethguiupdateview', function(event, value, httpStatusCode) { 
            $(this).empty();
            $('#Dashboard_SystemStatus_Resources_loading').hide();
            $('.dashboard-item').show(); 
            plots['memory'].series[0].data = value.slice(1,3);
            plots['memory'].replot();
            //add text label
            //add text label
            for (var i=0; i<value.length; i++) {
                $(this).append('<dt>'+value[i][0]+'</dt><dd>'+value[i][1]+' MB</dd>');
            }
        }); 

        $('.$swap_id').on('nethguiupdateview', function(event, value, httpStatusCode) {
            // draw plot excluding total field
            $(this).empty();
            $('#Dashboard_SystemStatus_Resources_loading').hide();
            $('.dashboard-item').show(); 
            plots['swap'].series[0].data = value.slice(1,3);
            plots['swap'].replot();

            //add text label
            for (var i=0; i<value.length; i++) {
                $(this).append('<dt>'+value[i][0]+'</dt><dd>'+value[i][1]+' MB</dd>');
            }
        });

    });
} ( jQuery ));
");   
