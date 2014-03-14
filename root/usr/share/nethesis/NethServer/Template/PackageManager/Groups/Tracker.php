<?php
/* @var $view \Nethgui\Renderer\Xhtml */

$view->requireFlag($view::INSET_DIALOG);

echo $view->header()->setAttribute('template', $T('Tracker_header'));

$imagesUrl = $view->getPathUrl() . 'images';
$pleaseWaitLabel = htmlspecialchars($T('Please_wait_label'));

echo $view->panel()
//    ->setAttribute('class', 'Dialog noclose')
    ->insert($view->panel()->setAttribute('class', 'labeled-control')
        ->insert($view->literal("<img style='vertical-align: middle' src='${imagesUrl}/ajax-loader.gif' alt='${pleaseWaitLabel}' /> ")->setAttribute('escapeHtml', FALSE))
        ->insert($view->literal($T('Please_wait_label')))
    )
    ->insert($view->progressBar('progress'))
    ->insert($view->textLabel('message'))
    ->insert($view->textList('failedEvents'))
;
