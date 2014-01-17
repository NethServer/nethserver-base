<?php
$view->useFile('js/jquery.masonry.min.js');

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
    text-align: right;
    font-weight: bold;
    margin-right: 0.5em;
    padding: 0.1em;
  }
  .dashboard-item dt:after {
    content: \":\";
  }
  .dashboard-item dd {
    text-align: right;
    padding: 0.1em;
  }
  .dashboard-graph {
    margin-top: 0.2em;
    height:250px;
    width:250px;
  }
  .dashboard-item h2 {
    font-weight: bold;
    font-size: 120%;
    text-align: center;
    padding: 0.2em;
  }
");

foreach($view->getModule()->getChildren() as $child) {
    echo $view->inset($child->getIdentifier());
}
