<?php
/* @var $view \Nethgui\Renderer\Xhtml */
$view->requireFlag($view::FORM_ENC_MULTIPART);

echo $view->header()->setAttribute('template', $T('Upload_Header'));

$idCrt = $view->getUniqueId('crt');
$idKey = $view->getUniqueId('key');
$idChain = $view->getUniqueId('chain');

echo $view->fileUpload('UploadCrt')->setAttribute('htmlName', 'crt');
echo $view->fileUpload('UploadKey')->setAttribute('htmlName', 'key');
echo $view->fileUpload('UploadChain')->setAttribute('htmlName', 'chain');
echo $view->textInput('UploadName');

echo $view->buttonList()
    ->insert($view->button('Upload', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

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
