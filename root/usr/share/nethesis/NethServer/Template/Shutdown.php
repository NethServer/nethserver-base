<?php

/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->radioButton('Action', 'reboot', $view::STATE_CHECKED);
echo $view->radioButton('Action', 'poweroff');

echo $view->buttonList()
    ->insert($view->button('Shutdown', $view::BUTTON_SUBMIT))
    ->insert($view->button('Help', $view::BUTTON_HELP));

$actionId = $view->getUniqueId();
$imagesUrl = $view->getPathUrl() . 'images';
$pleaseWaitLabel = $T('Please_wait_label');

$view->includeTranslations(array(
    'Dialog_reboot_title',
    'Dialog_reboot_label',
    'Dialog_poweroff_title',
    'Dialog_poweroff_label',
    'Dialog_wait_label',
    'Dialog_completed_label',
    'Dialog_switchedoff_label',
    'Close_label'
));


$view->includeCss("
#shutdownDialog { padding-top: 1em }
#shutdownDialog img { display: block; float: left; padding-bottom: 2em; margin-right: 1ex }
");

$view->includeJavascript("
(function ( $ ) {
    // FIXME: define in jquery.nethgui.base.js -- A translator helper:
    var T = function () {
        return $.Nethgui.Translator.translate.apply($.Nethgui.Translator, Array.prototype.slice.call(arguments, 0));
    };

    var _url;
    var _shutdownStatus = 0;
    var _server = $.Nethgui.Server;

    var state = {
        INIT: 0,
        REBOOT: 1,
        POWEROFF: 2,
        WAIT: 3,
        COMPLETED: 4,
        SWITCHEDOFF: 5,
    };

    var serverCall = function() {
         // start polling the server:
        _server.ajaxMessage({
            url: _url,
            dispatchResponse: dispatchResponse,
            dispatchError: dispatchError
        });
    }

    var _closeButtonSetup = {
        disabled: true,
        text: T('Close_label'),
        click: function() { $(this).dialog('close'); }
    };

    var _dialog = $('<div />', {id: 'shutdownDialog'}).appendTo('body').dialog({
        autoOpen: false,
        modal: true,
        draggable: false,
        resizable: false,
        closeOnEscape: false,
        dialogClass: 'ShutdownDialog',
        buttons: [_closeButtonSetup]
    });

    // add the throbber image:
    _dialog.append('<img src=\"${imagesUrl}/ajax-loader.gif\" alt=\"${pleaseWaitLabel}\" />');

    // add the text label
    _dialog.append('<div class=\"TextLabel\" />');

    var displayMessage = function(text) {
        if(! _dialog.dialog('isOpen')) {
            _dialog.dialog('open');
        }
        _dialog.children('.TextLabel').text(text);
    }

    var doStateTransition = function (newState) {
        if(newState === state.REBOOT) {
            displayMessage(T('Dialog_reboot_label'));
            serverCall();
        } else if(newState === state.POWEROFF) {
            displayMessage(T('Dialog_poweroff_label'));
            serverCall();                        
        } else if(newState === state.WAIT) {
            displayMessage(T('Dialog_wait_label'));
            serverCall();
        } else if(newState === state.COMPLETED) {
            displayMessage(T('Dialog_completed_label'));
            _dialog.dialog('option', 'buttons', [$.extend(_closeButtonSetup, {disabled: false})]);
            _dialog.children('img').hide();
        } else if(newState === state.SWITCHEDOFF) {                        
            $('#$actionId').trigger('nethguifreezeui');
            displayMessage(T('Dialog_switchedoff_label'));
            _dialog.children('img').hide();
            $('.ui-widget-overlay').animate({opacity:0.6});
        }

        _shutdownStatus = newState;
    }

    var dispatchResponse = function (response, status, httpStatusCode) {
        if(_shutdownStatus === state.REBOOT) {
            doStateTransition(state.REBOOT);
        } else if(_shutdownStatus === state.POWEROFF) {
            doStateTransition(state.POWEROFF);
        } else if(_shutdownStatus === state.WAIT) {
            doStateTransition(state.COMPLETED);
        } else {
            throw 'shutdown/dispatchResponse: unexpected state';
        }
    };

    var dispatchError = function(jqXHR, textStatus, errorThrown) {
        window.setTimeout(function () {
            if(_shutdownStatus === state.REBOOT) {
                doStateTransition(state.WAIT);
            } else if(_shutdownStatus === state.POWEROFF) {
                doStateTransition(state.SWITCHEDOFF);
            } else if(_shutdownStatus === state.WAIT) {
                if(jqXHR.status == 403 && errorThrown === 'Forbidden') {
                    doStateTransition(state.COMPLETED);
                } else {
                    doStateTransition(state.WAIT);
                }
            } else {
                throw 'shutdown/dispatchError: unexpected state';
            }
        }, 15000);
        
    };

    $('#${actionId}').on('nethguishutdown', function(e, url, action, labels) {
        _url = url;
        _dialog.children('img').show();
        if(action === 'poweroff') {
            _dialog.dialog('option', 'title', T('Dialog_poweroff_title'));
            doStateTransition(state.POWEROFF);
        } else if(action === 'reboot') {
            _dialog.dialog('option', 'title', T('Dialog_reboot_title'));
            doStateTransition(state.REBOOT);
        }
    });
} ( jQuery ));
");