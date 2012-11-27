<?php

/* @var $view \Nethgui\Renderer\Xhtml */

$form = $view->form();

foreach ($view->getModule()->getChildren() as $childModule) {
    $form->insert($view->inset($childModule->getIdentifier()));
}

$form->insert($view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP));

echo $view->panel()
    ->setAttribute('class', 'Controller')
    ->setAttribute('id', $view->getModule()->getIdentifier())
    ->insert($view->panel()->setAttribute('class', 'Action')->insert($form))
;




