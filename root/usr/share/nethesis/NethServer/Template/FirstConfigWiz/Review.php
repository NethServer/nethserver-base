<?php
/* @var $view \Nethgui\Renderer\Xhtml */

include "WizHeader.php";

echo $T('Changes_label');
echo $view->textList('changes')->setAttribute('tag', 'div/ul.disc/li');

echo "<div style='margin-top: 10px'>";
echo $view->textLabel('redirect');
echo "</div>";

echo $view->buttonList($view::BUTTON_HELP)
        ->insert($view->button('Back', $view::BUTTON_CANCEL))
        ->insert($view->button('Apply',  $view::BUTTON_SUBMIT))
;

