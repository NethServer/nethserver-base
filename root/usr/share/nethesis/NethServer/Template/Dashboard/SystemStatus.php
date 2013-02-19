<?php
$view->includeFile('NethServer/Js/jquery.masonry.min.js');
$view->useFile("css/jquery.jqplot.min.css");
$view->useFile("js/jquery.jqplot.min.js");

$view->includeCSS("
  div.dashboard-item {
    margin: 5px;
    padding: 5px;
    border: 1px solid #ccc;
    width: 300px;
  }
  .dashboard-item dt {
    float: left;
    clear: left;
    width: 100px;
    text-align: right;
    font-weight: bold;
  }
  .dashboard-item dt:after {
    content: \":\";
  }
  .dashboard-item dd {
    margin: 0 0 0 110px;
    padding: 0 0 0.5em 0;
  }
  .dashboard-item table.jqplot-table-legend {
    width:auto;
  }
");

foreach($view->getModule()->getChildren() as $child) {
    echo $view->inset($child->getIdentifier());
}
