<?php
/* @var $view \Nethgui\Renderer\Xhtml */

include "WizHeader.php";

echo "<h3>".$T("Welcome_title", iterator_to_array($view))."</h3>";

echo "<div class='wspreline'>";
echo $T("Welcome_body");
echo "</div>";

echo "<div class='welc-next'>";
echo $T("Welcome_next");
echo "</div>";

$url = $view->getModuleUrl('../Cover?skip');
echo $view->buttonList($view::BUTTON_HELP)
->insert($view->literal('<a href="'.$url.'" class="Button link submit">'.$T('Next_label').'</a>'))
;
