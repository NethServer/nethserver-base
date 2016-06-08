<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('GenerateLe_Header'));

echo $view->panel()
    ->insert($view->textInput('LetsEncryptMail'))
    ->insert($view->textArea('LetsEncryptDomains', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30'));


echo $view->buttonList()
    ->insert($view->button('GenerateLe', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;
