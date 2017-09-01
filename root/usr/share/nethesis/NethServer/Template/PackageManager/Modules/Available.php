<?php

/* @var $view \Nethgui\Renderer\Xhtml */


echo $view->buttonList()
    ->insert($view->button('Add', $view::BUTTON_SUBMIT))
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
                var nodeId = '#' + baseId + '_' + groupId + '_action';
                $(nodeId).parent().parent().addClass(category.id + ' ' + groupId);
            });
        });
        $(this).buttonset();
    });
    $(document).ready(function () {        
        var node = $('#${categoriesId}');
        node.triggerHandler('nethguiupdateview',  [$.parseJSON(node.attr('data-categories'))]);
        node.removeAttr('data-categories');
    });
}( jQuery ));
");

// 'groups' contains an array of views..
echo $view->objectsCollection('groups')
    ->setAttribute('ifEmpty', function ($view) use ($T) {
            return '<div class="emptybanner">' . $T('No_modules_available_message') . '</div>';
        })
    ->setAttribute('template', function ($view) use ($T) {
        return $view->fieldsetSwitch('action', 'install', $view::FIELDSETSWITCH_CHECKBOX)            
            ->setAttribute('labelSource', 'name')
            ->setAttribute('label', '${0}')            
            ->insert($view->panel()
                ->setAttribute('class', 'labeled-control')
                ->insert($view->textLabel('description'))
                ->insert($view->panel()->setAttribute('class', 'details')
                ->insert($view->textList('mpackages')
                    ->setAttribute('tag', 'div/span/span')
                    ->setAttribute('separator', ', '))
                ->insert($view->selector('opackages_selected', $view::SELECTOR_MULTIPLE | $view::LABEL_NONE)
                    ->setAttribute('choices', 'opackages_datasource')
                    )))
        ;
    })
    ->setAttribute('key', 'id');

echo $view->buttonList()
    ->insert($view->button('Add', $view::BUTTON_SUBMIT))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

$view->includeCss("
.${groupsTarget} .emptybanner { padding: 2em 0; text-align: center; font-size: 4em; font-weight: bold; color: #dedede }
.${groupsTarget} .TextList.mpackages {font-size: .9em}
.${groupsTarget} .TextLabel.name {font-size: 1.2em}
.${groupsTarget} .FieldsetSwitchPanel {position: relative; top: -0.5em; margin-bottom: 0}
.${groupsTarget} .FieldsetSwitch { padding: 2px; border-radius: 4px; background: #eee; margin-bottom: 0.5em }
.${groupsTarget} .FieldsetSwitchPanel .labeled-control {margin-bottom: 0}
.${groupsTarget} .FieldsetSwitch { border-left: 5px solid #ddd }
.${groupsTarget} .FieldsetSwitch.installed.changed { border-left-color: red }
.${groupsTarget} .FieldsetSwitch.available.changed { border-left-color: green }
.${groupsTarget} .FieldsetSwitch .details, .${groupsTarget} .FieldsetSwitch .opackages_selected.multiple { margin-top: 0.5em }
#${categoriesId} { text-align: center }
#${categoriesId} label { font-weight: bold; letter-spacing: 1px; background: #00719a; border: 1px solid #00425b; color: white }
#${categoriesId} label.ui-state-active { opacity: 0.7 }
#${categoriesId} label.ui-state-hover { opacity: 0.7 }
#${categoriesId} label.ui-corner-left { border-top-left-radius: 9px; border-bottom-left-radius: 9px }
#${categoriesId} label.ui-corner-right { border-top-right-radius: 9px; border-bottom-right-radius: 9px }

.yumError .Controller, .yumSuccess .Controller {margin-top: 4px}
");

$yumError = '<i class="fa fa-li fa-exclamation-triangle"></i><tt>{{message}}</tt><p>{{description}}</p><div class="Controller"><div class="Action"><form action="{{action}}" method="post"><button class="Button submit" type="submit">{{buttonLabel}}</button><input name="csrfToken" type="hidden" value="' . htmlspecialchars($view->getModule()->csrfToken) . '"></form></div></div>';
$view->getModule()->getParent()->notifications->defineTemplate('yumError', $yumError, 'yumError bg-red pre-fa');
$yumSuccess = '<i class="fa fa-li fa-info-circle"></i><p>{{message}}</p><p>{{description}}</p><div class="Controller"><div class="Action"><button class="Button default" onclick=\'location.href="{{action}}";\'>{{buttonLabel}}</button></div></div>';
$view->getModule()->getParent()->notifications->defineTemplate('yumSuccess', $yumSuccess, 'yumSuccess bg-green pre-fa');
