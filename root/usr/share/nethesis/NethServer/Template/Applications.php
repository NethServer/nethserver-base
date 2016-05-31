<?php

foreach($view->getModule()->getChildren() as $child) {
    $info = $child->getInfo();
    echo "<div class='app-item'>";
    echo "<h1 class='app-title'>".$child->getName()."</h1>";
    echo "<div class='app-text'></div>";
    echo "<div class='ButtonContainer'>";
    echo '<div class="Buttonlist"><a target="_blank" href="'.$info['url'].'" title="'.$info['url'].'" class="button link ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text">'.$T('Open_label').'</span></a></div>';
    echo "</div>";
    echo "</div>";

}
if (count($view->getModule()->getChildren()) == 0) {
    echo "<h2 class='app-title'>".$T('no_application_installed')."</h2>";
}
echo "<div class='app-reset'></div>";
