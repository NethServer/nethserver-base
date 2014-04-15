<?php

$view->requireFlag($view::INSET_DIALOG);

echo $view->header()->setAttribute('template', $T('apply_header'));

echo "<div class='external_page'>".$T('apply_message')."</div>";

echo $view->buttonList()
    ->insert($view->button('Apply', $view::BUTTON_SUBMIT))
    ->insert($view->button('Close', $view::BUTTON_CANCEL))
;

$view->includeJavascript("
(function ( $ ) {


} ( jQuery ));
");

