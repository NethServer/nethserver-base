<?php

/* @var $view \Nethgui\Renderer\Xhtml */

//echo $view->header('Id')->setAttribute('template', $T('Select_label'));
// 'groups' contains an array of views..
echo $view->objectsCollection('groups')
    ->setAttribute('template', function ($view) use ($T) {
            return $view->fieldsetSwitch('status', 'installed', $view::FIELDSETSWITCH_CHECKBOX)
                ->setAttribute('uncheckedValue', 'available')
                ->setAttribute('labelSource', 'name')
                ->setAttribute('label', '${0}')
                ->insert($view->panel()
                    ->setAttribute('class', 'labeled-control')
                    ->insert($view->textLabel('description'))
                    ->insert($view->textList('mpackages')
                        ->setAttribute('tag', 'div/span/span')
                        ->setAttribute('separator', ', ')))
            ;
        })
    ->setAttribute('key', 'id');

echo $view->buttonList()
    ->insert($view->button('Next', $view::BUTTON_SUBMIT))
    ->insert($view->button('Reset', $view::BUTTON_RESET));

$groupsTarget = $view->getClientEventTarget('groups');

$view->includeCss("
.${groupsTarget} .TextList.mpackages {font-size: .9em}
.${groupsTarget} .TextLabel.name {font-size: 1.2em}
.${groupsTarget} .FieldsetSwitchPanel {position: relative; top: -0.5em}
    ");

//$view->includeJavascript("
//(function ( $ ) {
//    $(document).ready(function() {
//        $.Nethgui.Server.ajaxMessage({
//            isMutation: false,
//            url: '/PackageManager/Groups/Index'
//        });
//    });
//    $('.$groupsTarget').ObjectsCollection('startThrobbing')
//        .one('ajaxStop', function() { $(this).ObjectsCollection('endThrobbing'); });
//}) ( jQuery );
//");
