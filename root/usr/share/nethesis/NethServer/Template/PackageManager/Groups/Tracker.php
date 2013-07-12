<?php

/* @var $view \Nethgui\Renderer\Xhtml */

$view->requireFlag($view::INSET_DIALOG);

$imagesUrl = $view->getPathUrl() . 'images';
$pleaseWaitLabel = htmlspecialchars($T('Please_wait_label'));

echo $view->panel()
//    ->setAttribute('class', 'Dialog noclose')
    ->insert($view->literal("<img src='${imagesUrl}/ajax-loader.gif' alt='${pleaseWaitLabel}' /> ")->setAttribute('escapeHtml', FALSE))
    ->insert($view->literal($T('Please_wait_label')))
;
