<?php
/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);

$actionUrl = $view->getModuleUrl();

echo "<form action=\"{$actionUrl}\" method=\"post\" enctype=\"multipart/form-data\">";

echo $view->header()->setAttribute('template', $T('Upload_Header'));

$idCrt = $view->getUniqueId('crt');
$idKey = $view->getUniqueId('key');
$idChain = $view->getUniqueId('chain');

echo "<div class=\"labeled-control label-above\"><label for=\"{$idCrt}\">" . \htmlspecialchars($T('UploadCrt_label')) . "</label><input type=\"file\" name=\"crt\" id=\"{$idCrt}\" /></div>";

echo "<div class=\"labeled-control label-above\"><label for=\"{$idKey}\">" . \htmlspecialchars($T('UploadKey_label')) . "</label><input type=\"file\" name=\"key\" id=\"{$idKey}\" /></div>";

echo "<div class=\"labeled-control label-above\"><label for=\"{$idChain}\">" . \htmlspecialchars($T('UploadChain_label')) . "</label><input type=\"file\" name=\"chain\" id=\"{$idChain}\" /></div>";

echo $view->textInput('UploadName');

echo $view->buttonList()
    ->insert($view->button('Upload', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

echo "</form>";

$idView = $view->getUniqueId();
$idUploadName = $view->getUniqueId('UploadName');

$view->includeJavascript("
(function ( $ ) {
    var formTag = $('#{$idView} form');
    var uploadNameInput = $('#{$idUploadName}');
    $('#{$idCrt}').on('change', function (e) {
        if( ! uploadNameInput.val()) {
            var name = (new FormData(formTag[0])).get('crt').name;
            uploadNameInput.val(name.replace(/\..*\$/, ''));
        }
    });
} ( jQuery ));
");
