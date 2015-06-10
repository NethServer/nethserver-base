<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);

echo $view->header()->setAttribute('template', $T('Find_Title'));

$form = $view->form()->setAttribute('method', 'get');

$form->insert($view->textInput('q', $view::LABEL_NONE))
    ->insert($view->literal(' '))
    ->insert($view->buttonList()
             ->insert($view->button('Find'))
             ->insert($view->button('Clear', $view::BUTTON_CUSTOM))
             ->insert($view->button('Help', $view::BUTTON_HELP))
        )
;

echo $form;

$resultsTarget = $view->getClientEventTarget('results');
$consoleTarget = $view->getClientEventTarget('../Read/console');
$headerTarget = $view->getClientEventTarget('../Read/logFile');
$formTarget = $view->getClientEventTarget('../Read/FormAction');
$actionId = $view->getUniqueId();
$clearTarget = $view->getClientEventTarget('Clear');

echo sprintf('<div class="LogViewerResults %s">', $resultsTarget);
if($view['results']) {
    echo '<ul>';
    
    foreach($view['results'] as $result) {

        if($view['q']) {
            $fileName = strtr($result['f'], array($view['q'] => '<b>' . $view['q'] . '</b>'));
        } else {
            $fileName = $result['f'];
        }

        echo sprintf('<li><a href="%s">%s</a>%s</li>', htmlspecialchars($result['p']), htmlspecialchars($fileName), $result['m'] ? sprintf(' (<a href="%s">%s</a>)', htmlspecialchars($result['p'] . '?' . http_build_query($result['q'])), htmlspecialchars($T('Results_Filtered_label', array($result['m'])))) : '');
    }
    echo '</ul>';
}
echo '</div>';

$view->includeTranslations(array('Results_Filtered_label', 'Result_Filtered_label'));


$jsCode = <<<JSCODE
/*
 * NethServer\Module\LogViewer
 */
(function ( $ ) {
    var T = $.Nethgui.T;
    var resultsWidget = $('.${resultsTarget}');
    var consoleWidget = $('.${consoleTarget}');

    var openLog = function(e) {
        var url = $(this).attr('href');
        var result = $(this).data('result') ? $(this).data('result') : {f: $(this).text()};

        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: url,
            freezeElement: resultsWidget,
            formatSuffix: 'txt',
            isCacheEnabled: false,
            dispatchResponse: function (value, selector, jqXHR) {
                var logFile = result.f;
                var query = result.q ? result.q.q : '';
                if(query) {
                    query = ' (' + query + ')';
                }
                if(typeof value === 'string') {                    
                    $('.${headerTarget}').triggerHandler('nethguiupdateview', [logFile + query, selector, jqXHR]);
                    $('.${formTarget}').triggerHandler('nethguiupdateview', [url, selector, jqXHR]);
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
                 .append( r.m > 0 ? r.f : $('<a />', {href: r.p}).text(r.f).on('click', openLog).data('result', {f: r.f, p: r.p, m: 0, q: {q: ''}}))
                 .append(' ')
                 .append( r.m > 0 ? $('<a />', {href: r.p + '?q=' + encodeURIComponent(r.q.q)}).text(T(r.m === 1 ? 'Result_Filtered_label' : 'Results_Filtered_label', r.m)).on('click', openLog).data('result', r) : '')
        });

        container.appendTo(widget);
     });
     
     $(document).ready(function() {
        resultsWidget.find('a').each(function () { $(this).on('click', openLog) });
     });

    $('.${clearTarget}').on('click', function () {
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: $('#${actionId} form').attr('action'),
            freezeElement: resultsWidget
        });
    });

} ( jQuery ));
JSCODE;

$cssCode = <<<CSSCODE
.LogViewerResults {
    margin: 1em 0;
    line-height: 180%;
}
.LogViewerResults a {
    color: #002F64;
    text-decoration: none;
}
.LogViewerResults a:hover {
    text-decoration: underline;
}
.LogViewerResults a:visited {
    color: #002F64
}
CSSCODE;

$view->includeCss($cssCode);
$view->includeJavascript($jsCode);