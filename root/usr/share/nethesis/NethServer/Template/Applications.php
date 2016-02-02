<?php
$view->includeCSS("
  div.app-item {
    margin: 5px;
    border: 2px solid #ccc;
    width: 300px;
    height: 80px;
    float: left;
  }
  h2.app-title, .app-item .app-title {
    font-size: 120%;
    font-weight: bold;
    padding: 10px;
    background: #ccc;
  }
  .app-item .app-text {
      height: 15px;
  }
  .app-item .ButtonContainer {
  }
  .app-item .Buttonlist {
       float: right;
       margin-right: 20px;
   }
  .app-item .button.link.ui-state-hover:first-child,
  .app-item .button.link:first-child {
    color: white;
    border: 1px solid #3079ED;
    text-transform: uppercase;
    background: #4D90FE;
    padding: 10px;
    text-decoration: none;
  }
  .app-reset {
    clear: both;
  }
");

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
