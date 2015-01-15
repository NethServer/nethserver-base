<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('Select_header'));

echo $view->buttonList()
    ->insert($view->button('ApplySelection', $view::BUTTON_SUBMIT))
    ->insert($view->button('Packages', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../../Packages')))
    ->insert($view->button('Update', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../../Update')))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

$cssUrl = $view->getPathUrl() . 'css';
$viewId = $view->getUniqueId();
$groupsTarget = $view->getClientEventTarget('groups');
$groupsId = $view->getUniqueId('groups');
$categoriesId = $view->getUniqueId('categories');
echo sprintf('<div id="%s" class="Buttonlist %s YumCategories" data-categories="%s"></div>',
    $categoriesId,
    $view->getClientEventTarget('categories'),
    \htmlspecialchars(json_encode($view['categories'], TRUE), ENT_QUOTES)
);
$view->includeJavascript("
(function( $ ) {
    var baseId = \"${groupsId}\";

    var setGroupStates = function () {
        $('#' + baseId).children().each(function(index, group) {
            var checkbox = $(group).find(':checkbox');
            $(group).addClass(checkbox.is(':checked') > 0 ? 'installed' : 'available');
            checkbox.on('change nethguitoggle', function () {
                $(group).toggleClass('changed');
            });
        });
    };

    // update groups class status
    $('.${groupsTarget}').on('nethguicreate', function () {
        $(this).on('nethguiupdateview', setGroupStates);
    });
    $('#${viewId}').on('nethguisetselectionchanged ', function (e, selection) {
        $.each(selection, function(index, id) {
            $('#' + id).prop('checked', ! $('#' + id).prop('checked')).trigger('nethguitoggle');
        });
    });

    // update groups-categories association
    $('#${categoriesId}').on('nethguiupdateview', function (e, data) {
        $(this).empty();
        var self = this;
        if( ! $.isArray(data)) {
            return;
        }
        $.each(data, function(index, category) {

            // Create the category (radio)button:
            var catid = \"${categoriesId}_\" + category.id;
            var radiobutton = $('<input />').attr({'id': catid, 'type': 'radio', 'value': category.id, 'name': 'yumcategory', 'class': category.id, 'checked': category.selected ? true : false});
            $(self).append(radiobutton);
            $('<label/>').attr({'for': catid, 'title': category.description}).text(category.name).appendTo(self);
            radiobutton.on('click.nethgui', function (e) {
                var radio = $(this);
                $('#' + baseId).children().each(function () {
                    if($(this).hasClass(radio.val())) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Mark groups with categories:
            $.each(category.groups, function(index, groupId) {
                var nodeId = '#' + baseId + '_' + groupId + '_status';
                $(nodeId).parent().parent().addClass(category.id + ' ' + groupId);
            });
        });
        $(this).buttonset();
    });
    $(document).ready(function () {        
        var node = $('#${categoriesId}');
        node.triggerHandler('nethguiupdateview',  [$.parseJSON(node.attr('data-categories'))]);
        node.removeAttr('data-categories');
        setGroupStates();
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

$view->includeCss("
.${groupsTarget} .TextList.mpackages {font-size: .9em}
.${groupsTarget} .TextLabel.name {font-size: 1.2em}
.${groupsTarget} .FieldsetSwitchPanel {position: relative; top: -0.5em; margin-bottom: 0}
.${groupsTarget} .FieldsetSwitch { padding: 2px; border-radius: 4px; background: #eee; margin-bottom: 0.5em }
.${groupsTarget} .FieldsetSwitchPanel .labeled-control {margin-bottom: 0}
.${groupsTarget} .FieldsetSwitch { border-left: 5px solid #ddd }
.${groupsTarget} .FieldsetSwitch.installed.changed { border-left-color: red }
.${groupsTarget} .FieldsetSwitch.available.changed { border-left-color: green }
#${categoriesId} { text-align: center }
#${categoriesId} label { font-weight: bold; letter-spacing: 1px; background: #4D90FE url('${cssUrl}/img/blue-inset-normal.png') repeat-x left bottom; border: 1px solid #3079ED; color: white }
#${categoriesId} label.ui-state-active { opacity: 0.8 }
#${categoriesId} label.ui-state-hover {background-image: url('${cssUrl}/img/blue-inset-hover.png') }
#${categoriesId} label.ui-corner-left { border-top-left-radius: 9px; border-bottom-left-radius: 9px }
#${categoriesId} label.ui-corner-right { border-top-right-radius: 9px; border-bottom-right-radius: 9px }
");

