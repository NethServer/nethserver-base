<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('Select_header'));

echo $view->buttonList()
    ->insert($view->button('ApplySelection', $view::BUTTON_SUBMIT))
    ->insert($view->button('Packages', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../../Packages')))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

$baseId = $view->getUniqueId('groups');
$categoriesId = $view->getUniqueId('categories');
echo sprintf('<div id="%s" class="Buttonlist %s YumCategories" data-categories="%s"></div>',
    $categoriesId,
    $view->getClientEventTarget('categories'),
    \htmlspecialchars(json_encode($view['categories'], TRUE), ENT_QUOTES)
);
$view->includeJavascript("
(function( $ ) {
    var baseId = \"${baseId}\";

    $('#${categoriesId}').on('nethguiupdateview', function (e, data) {
        $(this).empty();
        var self = this;
        $.each(data, function(index, category) {

            // Create the category (radio)button:
            var catid = \"${categoriesId}_\" + category.id;
            var radiobutton = $('<input />').attr({'id': catid, 'type': 'radio', 'value': category.id, 'name': 'yumcategory'});
            $(self).append(radiobutton);
            $('<label/>').attr({'for': catid}).text(category.name).appendTo(self);
            radiobutton.on('click.nethgui', function (e) {
                var radio = $(this);
                $('#' + baseId).children().each(function () {
                    if($(this).hasClass(radio.val())) {
                        $(this).slideDown();
                    } else {
                        $(this).slideUp();
                    }
                });
            });

            // Mark groups with categories:
            $.each(category.groups, function(index, groupId) {
                var nodeId = '#' + baseId + '_' + groupId + '_status';
                $(nodeId).parent().parent().addClass(category.id);
            });
        });
        $(this).buttonset();
    });
    $(document).ready(function () {
        var node = $('#${categoriesId}')
        node.triggerHandler('nethguiupdateview',  [$.parseJSON(node.attr('data-categories'))]);
        node.removeAttr('data-categories');
    });
}( jQuery ));
");

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
    ->insert($view->button('ApplySelection', $view::BUTTON_SUBMIT))
    ->insert($view->button('Packages', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../../Packages')))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

$groupsTarget = $view->getClientEventTarget('groups');

$view->includeCss("
.${groupsTarget} .TextList.mpackages {font-size: .9em}
.${groupsTarget} .TextLabel.name {font-size: 1.2em}
.${groupsTarget} .FieldsetSwitchPanel {position: relative; top: -0.5em}
.${groupsTarget} { background: #eee; border: 1px inset #ddd; padding: 1em }
    ");

