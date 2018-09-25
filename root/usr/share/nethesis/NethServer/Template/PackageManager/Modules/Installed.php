<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->buttonList()
    ->insert($view->button('Packages', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../../Packages')))
;

$cssUrl = $view->getPathUrl() . 'css';
$viewId = $view->getUniqueId();
$groupsTarget = $view->getClientEventTarget('groups');
$groupsId = $view->getUniqueId('groups');

// 'groups' contains an array of views..
echo $view->objectsCollection('groups')
        ->setAttribute('ifEmpty', function ($view) use ($T) {
            return '<div class="emptybanner">' . $T('No_installed_modules_message') . '</div>';
        })
        ->setAttribute('template', function ($view) use ($T) {
            return $view->panel()->setAttribute('class', 'row')
                    ->insert($view->panel()->setAttribute('class', 'module')
                            ->insert($view->textLabel('name'))
                            ->insert($view->textLabel('description'))
                            ->insert($view->textList('categories')
                                        ->setAttribute('tag', 'div/span/span')
                                        ->setAttribute('separator', ', '))
                            ->insert($view->textList('mpackages')
                                        ->setAttribute('tag', 'div/span/span')
                                        ->setAttribute('separator', ', ')))
                    ->insert($view->buttonList($view::BUTTONSET)->setAttribute('class', 'Buttonset')
                            ->insert($view->button('Remove', $view::BUTTON_LINK))
                            ->insert($view->button('Edit', $view::BUTTON_LINK)))

            ;
        })
        ->setAttribute('key', 'id');

echo $view->buttonList()
    ->insert($view->button('Packages', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('../../Packages')))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

$view->includeCss("
.${groupsTarget} .emptybanner { padding: 2em 0; text-align: center; font-size: 4em; font-weight: bold; color: #dedede }
.${groupsTarget} { margin: 1em 0;  }
.${groupsTarget} .row {display: flex; align-items: flex-start; border: 1px solid #d3d3d3; border-radius: 4px; margin-bottom: .5em}
.${groupsTarget} .row .ui-buttonset {margin: 0; position: relative; top: -1px; right: 0; flex-shrink: 0}
.${groupsTarget} .name {font-size: 1.2em; display: block}
.${groupsTarget} .mpackages {font-size: .8em; display: block; margin-left: 1.2em }
.${groupsTarget} .categories {font-size: .8em; display: block; margin-left: 1.2em }
.${groupsTarget} .description { display: block; margin-top: 0.5em; margin-left: 1em}
.${groupsTarget} .module { padding: .5em; flex-grow: 1; border-radius: 4px; }
");
