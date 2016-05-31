<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('Pki_Title'));

$view->requireFlag($view::INSET_DIALOG);

echo "<div style='margin-bottom: 5px'>".$view->translate('certificate_label').": ";
echo $view->textLabel('cert');
echo "</div>";
echo $view->translate('default_cert_label');

echo $view->buttonList()
    ->insert($view->button('Confirm', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;

