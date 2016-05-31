<?php
$view->useFile('js/jquery.masonry.min.js');

foreach($view->getModule()->getChildren() as $child) {
    echo $view->inset($child->getIdentifier());
}

$module1Url = json_encode($view->getModuleUrl("/AdminTodo?notifications"));

$view->includeJavascript("
(function ( $ ) {

  function loadPage() {
        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: $module1Url
        });
  } 

  $(document).ready(function() {
      loadPage();
  });

})( jQuery);
");
