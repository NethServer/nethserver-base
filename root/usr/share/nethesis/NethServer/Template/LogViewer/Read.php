<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);



echo $view->header('logFile')->setAttribute('template', $T('Read_Title'));
$form = $view->form()->setAttribute('method', 'get');
$form->insert($view->buttonList()
        ->insert($view->button('Close', $view::BUTTON_CANCEL))
        ->insert($view->button('Follow', $view::STATE_DISABLED)->setAttribute('receiver', 'Follow'))
);
echo $form;

$consoleTarget = $view->getClientEventTarget('console');

$followId = $view->getUniqueId('Follow');
$actionId = $view->getUniqueId();
$formTarget = $view->getClientEventTarget('FormAction');
$actionId = $view->getUniqueId();

$view->includeTranslations(array('Follow_label', 'Stop_label'));

echo sprintf('<div class="LogViewerConsole %s"></div>', $consoleTarget);

$jsCode = <<<JSCODE
/*
 * NethServer\Module\LogViewer
 */
(function ( $ ) {
    var T = $.Nethgui.T;
    var consoleWidget = $('.${consoleTarget}');
    var buttonFollow = $('#${followId}');

    var offset = 0;

    // follow state:
    var follow = false;

    // timer period:
    var period = 5000;

    // jqXHR pending request:
    var pending = null;

    // the timerID counting period
    var timer = null;

    var followAtBottom = false;

    var followStart = function () {
        //consoleWidget.empty();
        buttonFollow.button('option', 'label', T('Stop_label'));
        follow = true;
        followAtBottom = true;
        if( ! timer && ! pending ) remoteRead();
    };

    var followStop = function () {
        buttonFollow.button('option', 'label', T('Follow_label'));
        follow = false;
        followAtBottom = false;
        if(timer) {
            window.clearTimeout(timer);
            timer = null;
        }
        if(pending) {
            pending.abort();
            pending = null;
        }
    };

    var remoteRead = function () {
        if( ! follow ) {
            return;
        }

        // restart request if none is pending:
        timer = window.setTimeout(function() { timer = null; if(pending === null) remoteRead(); }, period);

        var url = $('.${formTarget}').attr('action');

        if(/\?/.test(url)) {
            url = url + '&';
        } else {
            url = url + '?';
        }

        url = url + 'o=' + offset;
        
        pending = $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: url,
            formatSuffix: 'txt',
            isCacheEnabled: false,
            dispatchResponse: function (value, selector, jqXHR) {
                pending = null;
                offset += value.length;
                consoleWidget.append(document.createTextNode(value));
                if(followAtBottom) {
                    $('html, body').animate({scrollTop: Math.max(0, $(document).height() - $(window).height())}, 500);
                }
                // restart query:
                if (timer === null) {
                    remoteRead();
                }
            }
        });
    };

    $(window).on('scroll', function(e) {
        if($(window).scrollTop() < Math.max(0, $(document).height() - $(window).height())) {
            followAtBottom = false;
        } else {
            followAtBottom = true;
        }
        
    });

    $('#${actionId}').on('nethguicancel', function() {
        consoleWidget.empty();
        followStop();
    });

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
        follow ? followStop() : followStart();
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
