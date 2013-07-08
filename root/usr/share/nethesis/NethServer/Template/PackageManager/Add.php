<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('Id')->setAttribute('template', $T('Add ${0}'));

echo $view->textLabel('Description')->setAttribute('tag', 'p');

echo $view->fieldset()
    ->setAttribute('template', $T('mandatory_packages'))
    ->insert(
        $view->textList('MandatoryDefaultPackages')
        ->setAttribute('tag', 'div/ul.disc/li')
    )
;

echo $view->selector('SelectedOptionalPackages', $view::SELECTOR_MULTIPLE);

echo $view->buttonList()
    ->insert($view->button('Add', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL));
