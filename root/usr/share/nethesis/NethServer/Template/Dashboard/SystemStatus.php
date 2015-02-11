<?php
$view->useFile('js/jquery.masonry.min.js');

$view->includeCSS("
  div.dashboard-item {
    margin: 5px;
    padding: 5px;
    border: 1px solid #ccc;
    width: 300px;
    min-height: 50px;
    background: #fff;
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
    border-radius: 0px;
    margin: -5px -5px 5px -5px;
    background: #ccc;
  }
");

foreach($view->getModule()->getChildren() as $child) {
    echo $view->inset($child->getIdentifier());
}

$module1Url = json_encode($view->getModuleUrl("/AdminTodo?notifications"));
$module2Url = json_encode($view->getModuleUrl("/Dashboard/Services"));

$view->includeJavascript("
(function ( $ ) {

  function loadPage() {
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: $module1Url
        });
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: $module2Url
        });

  } 

  $(document).ready(function() {
      loadPage();
  });

})( jQuery);
");

$view->includeCss("
    tr.running td:nth-child(3n+3) {
        color: green;
    }
    tr.stopped td:nth-child(3n+3) {
        color: red;
        font-weight: bold;
    }
");
