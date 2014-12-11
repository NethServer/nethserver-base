<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('Pki_Generate_header'));

echo $view->fieldset()
    ->insert($view->textInput('C')->setAttribute('placeholder', $view['C_default']))
    ->insert($view->textInput('ST')->setAttribute('placeholder', $view['ST_default']))
    ->insert($view->textInput('L')->setAttribute('placeholder', $view['L_default']))
    ->insert($view->textInput('O')->setAttribute('placeholder', $view['O_default']))
    ->insert($view->textInput('OU')->setAttribute('placeholder', $view['OU_default']))
    ->insert($view->textInput('CN')->setAttribute('placeholder', $view['CN_default']))
    ->insert($view->textInput('EmailAddress')->setAttribute('placeholder', $view['EmailAddress_default']))
    ->insert($view->textArea('SubjectAltName', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30')->setAttribute('placeholder', $view['SubjectAltName_default']))
    ;

echo $view->fieldset()
    ->insert($view->textInput('CertificateDuration'))
    ;

echo $view->buttonList()
    ->insert($view->button('Generate', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;
