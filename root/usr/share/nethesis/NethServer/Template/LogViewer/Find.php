<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);

echo $view->header()->setAttribute('template', $T('Find_Title'));

$form = $view->form()->setAttribute('method', 'get');

$form->insert($view->textInput('q', $view::LABEL_NONE))
    ->insert($view->literal(' '))
      ->insert($view->button('Find'))
;

echo $form;

$resultsTarget = $view->getClientEventTarget('results');
$consoleTarget = $view->getClientEventTarget('../Read/console');
$headerTarget = $view->getClientEventTarget('../Read/logFile');

echo sprintf('<div class="LogViewerResults %s">', $resultsTarget);
if($view['results']) {
    echo '<ul>';
    
    foreach($view['results'] as $result) {

        if($view['q']) {
            $fileName = strtr($result['f'], array($view['q'] => '<b>' . $view['q'] . '</b>'));
        } else {
            $fileName = $result['f'];
        }

        echo sprintf('<li><a href="%s">%s</a> %s</li>', $result['h'], $fileName, $result['m'] ? " <b>(${result['m']})</b>" : '');
    }
    echo '</ul>';
}
echo '</div>';

$jsCode = <<<JSCODE
/*
 * NethServer\Module\LogViewer
 */
(function ( $ ) {
    var resultsWidget = $('.${resultsTarget}');
    var consoleWidget = $('.${consoleTarget}');

    var openLog = function(e) {
        var url = $(this).attr('href');
        var logFile = $(this).text();

        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: url,
            freezeElement: resultsWidget,
            formatSuffix: 'txt',
            isCacheEnabled: false,
            dispatchResponse: function (value, selector, jqXHR) {
                var query = $('#LogViewer_Find_q').val();
                if(query) {
                    query = ' (' + query + ')';
                }
                if(typeof value === 'string') {                    
                    $('.${headerTarget}').triggerHandler('nethguiupdateview', [logFile + query, selector, jqXHR]);
                    consoleWidget.triggerHandler('nethguiupdateview', [value, selector, jqXHR]);
                }
            }
        });
        return false;
    };

    resultsWidget.on('nethguiupdateview', function (e, results) {
        var widget = $(this);

        widget.empty();

        if( ! $.isArray(results) || results.length === 0 ) {
             return;
        }

        var container = $('<ul/>');

        $.each(results, function(i, r) {
             $('<li/>').appendTo(container)
                 .append($('<a />', {href: r.h}).text(r.f).on('click', openLog))
                 .append( r.m > 0 ? ' <b>(' + r.m + ')</b>' : '');
        });

        container.appendTo(widget);
     });
     
     $(document).ready(function() {
        resultsWidget.find('a').each(function () { $(this).on('click', openLog) });
     });

} ( jQuery ));
JSCODE;

$cssCode = <<<CSSCODE
.LogViewerResults {
    margin: 1em 0;
    line-height: 180%;
}
CSSCODE;

$view->includeCss($cssCode);
$view->includeJavascript($jsCode);