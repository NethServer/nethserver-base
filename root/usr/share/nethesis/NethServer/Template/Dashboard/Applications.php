<?php
$view->includeCSS("
  div.app-item {
    margin: 5px;
    padding: 5px;
    border: 1px solid #ccc;
    width: auto;
  }

  .app-item dt {
    float: left;
    clear: left;
    width: 100px;
    text-align: right;
    font-weight: bold;
  }
  .app-item dt:after {
    content: \":\";
  }
  .app-item dd {
    margin: 0 0 0 110px;
    padding: 0 0 0.5em 0;
  }
");


foreach($view->getModule()->getChildren() as $child) {
    echo "<div class='app-item'><dl>";
    echo $view->header()->setAttribute('template',$child->getName());
    foreach($child->getInfo() as $k=>$v) {
        echo "<dt>".$T($k)."</dt>";
        if(strpos($k,'url') === 0) {
           echo "<dd><a href='$v' title='$v'>".$child->getName()."</a></dd>";
        } else {
           echo "<dd>$v</dd>";
        } 
    }
    echo "</dl></div>";
}
