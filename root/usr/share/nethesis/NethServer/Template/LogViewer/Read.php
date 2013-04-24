<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);



echo $view->header('logFile')->setAttribute('template', $T('Read_Title'));
$form = $view->form()->setAttribute('method', 'get');
$form->insert($view->buttonList()
    ->insert($view->button('Close', $view::BUTTON_CANCEL))
    ->insert($view->button('follow', $view::STATE_DISABLED)->setAttribute('receiver', 'follow'))
    );
echo $form;

$consoleTarget = $view->getClientEventTarget('console');

$followId = $view->getUniqueId('follow');
$actionId = $view->getUniqueId();
$formTarget = $view->getClientEventTarget('FormAction');


echo sprintf('<div class="LogViewerConsole %s"></div>', $consoleTarget);

$jsCode = <<<JSCODE
/*
 * NethServer\Module\LogViewer
 */
(function ( $ ) {

    var consoleWidget = $('.${consoleTarget}');
    var buttonFollow = $('#${followId}');

    var offset = 0;

    // follow state:
    var follow = false;

    // timer period:
    var period = 10000;

    // when period has elapsed this is true:
    var ready = true;

    // is an ajax query pending?
    var pending = false;

    // the timerID counting period
    var timer;

    var remoteRead = function () {
        if( ! ready || ! follow) {
            return;
        }

        ready = false;

        // restart request if none is pending:
        timer = window.setTimeout(function() { ready = true; if( ! pending) remoteRead(); }, period);

        var url = $('.${formTarget}').attr('action');

        if(/\?/.test(url)) {
            url = url + '&';
        } else {
            url = url + '?';
        }

        url = url + 'o=' + offset;

        pending = true;
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: url,
            formatSuffix: 'txt',
            isCacheEnabled: false,
            dispatchResponse: function (value, selector, jqXHR) {
                pending = false;
                offset += value.length;
                consoleWidget.append(document.createTextNode(value));
                $('body').animate({scrollTop: $('body').height()}, 1000);
                // restart query:
                remoteRead();
            }
        });
    };

    consoleWidget.on('nethguiupdateview', function (e, value) {
         if(typeof value != 'string') {
             return;
         }
         consoleWidget.html(value);
         offset = value.length;
         buttonFollow.Button('enable');
         $('#${actionId}').trigger('nethguishow');
    });

    buttonFollow.on('click', function () {        
        if( ! follow) {
            $.debug('start follow');
            follow = true;
            //consoleWidget.css('overflow-y', 'scroll').css('height', consoleWidget.height());            
            remoteRead();
        } else {
            $.debug('stop follow');
            follow = false;
            window.clearTimeout(timer);
        }
        return false;
    });



} ( jQuery ));
JSCODE;

$cssCode = <<<CSSCODE
.LogViewerConsole {
    white-space: pre-wrap;
    font-family: monospace;
    padding: 0.5em;
    border: 1px solid #aaa;
    background: #eee;
    margin: 1em 0;
    line-height: 130%;
}
CSSCODE;

$view->includeCss($cssCode);
$view->includeJavascript($jsCode);
