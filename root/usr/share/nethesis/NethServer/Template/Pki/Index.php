<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('Pki_Title'));

echo $view->buttonList($view::BUTTON_HELP)
        ->insert(

$view->buttonList()->setAttribute('class', 'Buttonset v1 inlineblock')
    ->insert($view->button('GenerateLe', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../GenerateLe')))
    ->insert($view->button('Generate', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../Generate')))
    ->insert($view->button('Upload', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../Upload')))
);

require __DIR__ . '/../../../Nethgui/Template/Table/Read.php';
        
