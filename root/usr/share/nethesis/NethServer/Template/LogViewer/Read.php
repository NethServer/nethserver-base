<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);



echo $view->header('logFile')->setAttribute('template', $T('Read_Title'));
$form = $view->form()->setAttribute('method', 'get');
$form->insert($view->buttonList()
    ->insert($view->button('Close', $view::BUTTON_CANCEL))
    ->insert($view->button('follow', $view::STATE_DISABLED))
    );
echo $form;

$consoleTarget = $view->getClientEventTarget('console');
$actionId = $view->getUniqueId();

echo sprintf('<div class="LogViewerConsole %s"></div>', $consoleTarget);

$jsCode = <<<JSCODE
/*
 * NethServer\Module\LogViewer
 */
(function ( $ ) {

    var consoleWidget = $('.${consoleTarget}');

    consoleWidget.on('nethguiupdateview', function (e, value) {
         consoleWidget.html(value);
         $('#${actionId}').trigger('nethguishow');
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
