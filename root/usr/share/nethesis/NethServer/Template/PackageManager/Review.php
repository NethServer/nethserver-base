<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('Review_header'));

$template = strtr("<dl>
    {{#addGroups?}}<dt>%install_modules</dt>
    <dd><ul>{{#addGroups}}<li>{{.}}</li>{{/addGroups}}</ul></dd>{{/addGroups?}}
    {{#removeGroups?}}<dt>%remove_modules</dt>
    <dd><ul>{{#removeGroups}}<li>{{.}}</li>{{/removeGroups}}</ul></dd>{{/removeGroups?}}
    {{#addPackages?}}<dt>%install_optionals</dt>
    <dd><ul>{{#addPackages}}<li>{{.}}</li>{{/addPackages}}</ul></dd>{{/addPackages?}}
    {{#removePackages?}}<dt>%remove_packages</dt>
    <dd><ul>{{#removePackages}}<li>{{.}}</li>{{/removePackages}}</ul></dd>{{/removePackages?}}
    {{#keepPackages?}}<dt>%keep_packages</dt>
    <dd><ul>{{#keepPackages}}<li>{{keep}} %required_by {{requiredBy}}</li>{{/keepPackages}}</ul></dd>{{/keepPackages?}}
</dl>", array('%install_modules' => $T('Install_modules_label'), '%remove_modules' => $T('Remove_modules_label'), '%install_optionals' => $T('Install_optionals_label'), '%remove_packages' => $T('Remove_packages_label'), '%keep_packages' => $T('Keep_packages_label'), '%required_by' => $T('required_by')));

$view->includeJavascript("
(function( $ ) {
    $(function(){
        var template = " . json_encode($template) . ";
        var templateTarget = " . json_encode($view->getClientEventTarget('messages')) . ";
        $('.' + templateTarget).on('nethguiupdateview', function (e, data) {
             $(this).html(Mustache.render(template, data));
        });
    });
}( jQuery ));
");

$eventTarget = $view->getClientEventTarget('messages');
$view->includeCss("
.${eventTarget} ul { list-style: disc }
.${eventTarget} li { margin-left: 2em }
.${eventTarget} dd { margin-bottom: 0.5em }
");

$mustache = new \Mustache_Engine();
echo sprintf('<div class="%s">%s</div>', $view->getClientEventTarget('messages'), $mustache->render($template, $view['messages']));
echo $view->hidden('removeGroup');
echo $view->buttonList()
    ->insert($view->button('Run', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;
