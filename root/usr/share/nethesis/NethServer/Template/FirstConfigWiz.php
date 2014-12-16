<?php
/* @var $view \Nethgui\Renderer\Xhtml */

$wizId = $view->getUniqueId();
$stepsTarget = $view->getClientEventTarget('steps');
$stepsTemplate = '<div class="steps"><ol>{{#steps}}<li title="{{description}}" class="ui-widget-header ui-corner-all ui-helper-clearfix{{#current?}} current{{/current?}}">{{title}}</li>{{/steps}}</ol></div>';
$stepsTemplateEncoded = json_encode($stepsTemplate);

$view->rejectFlag($view::INSET_FORM);
$view->includeCss("
.primaryContent { margin: 0; }
#${wizId} > div.steps li.current { font-weight: bold }
#${wizId} > div.steps li { padding: .5em; background-color: #eee; margin-bottom: .5em; font-weight: normal; border: none; list-style: decimal inside }

@media screen and (min-width: 40em) {

#${wizId} { display: flex; align-content }
#${wizId} > div.Controller { flex-grow: 1; flex-shrink: 1; order: 2 }

#${wizId} > div.Controller > .Action {position: relative; min-height: 20em; padding-bottom: 4em}
#${wizId} > div.Controller > .Action .Buttonlist { position: absolute; bottom: 0; width: 100% }

#${wizId} > div.steps { flex-grow: 0; flex-shrink: 0; order: 1; margin-right: 1em; width: 15em }
#${wizId} > div.steps ol { margin-top: 3em}
}
");

$view->includeJavascript("
(function($) {
    $('.${stepsTarget}').on('nethguiupdateview', function (e, steps) {       
       var template = ${stepsTemplateEncoded};
       $(this).find('ol').first().replaceWith(Mustache.render(template, {'steps': steps}));
    });
}(jQuery));
");

$mustache = new \Mustache_Engine();
$stepsOutput = $mustache->render($stepsTemplate, $view);

echo "<div id=\"${wizId}\" class=\"${stepsTarget}\">" . $view['content'] . $stepsOutput . "</div>";



