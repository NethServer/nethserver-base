<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('Pki_Show_header'));

echo $view->textLabel('x509text')->setAttribute('class', 'labeled-control x509text ui-corner-all')->setAttribute('tag', 'div');

$view->includeCss("
.x509text { white-space: pre-wrap; padding: 4px; border: 1px solid #c2c2c2; background: #e2e2r2; color: #4b4b4b; }
");

echo $view->buttonList()
        ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
        ->insert($view->button('Help', $view::BUTTON_HELP))
        ->insert($view->button('SetDefault', $view::BUTTON_LINK));