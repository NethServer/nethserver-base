<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('Review_header'));

echo $view->fieldset()->setAttribute('template', $T('Transaction_summary_label'))
    ->insert($view->textList('messages'));

echo $view->fieldset()->setAttribute('template', $T('Optional_packages_label'))
    ->insert($view->objectsCollection('optionals')
        ->setAttribute('key', 'id')
        ->setAttribute('ifEmpty', function(\Nethgui\Renderer\Xhtml $iView) use ($T) { 
            return $iView->literal($T('Empty_optionals_label'))->setAttribute('hsc', TRUE);
        })
        ->setAttribute('template', function(\Nethgui\Renderer\Xhtml $iView) use ($T) {
                return $iView->panel()
                    ->insert($iView->fieldsetSwitch('status', 'installed', $iView::FIELDSETSWITCH_CHECKBOX)
                        ->setAttribute('unchekedValue', 'available')
                        ->setAttribute('labelSource', 'id')
                        ->setAttribute('label', '${0}')
                        ->insert($iView->panel()
                            ->insert($iView->textLabel('requiredBy')->setAttribute('template', $T("RequiredBy_label") . ' '))
                            ->insert($iView->textList('requiredBy')
                                ->setAttribute('tag', 'span/span/span')
                                ->setAttribute('separator', ', ')))
                );
            }))
;

$optionalsTarget = $view->getClientEventTarget('optionals');

$view->includeCss("
.${optionalsTarget} .FieldsetSwitchPanel {position: relative; top: -0.5em}
    ");

echo $view->hidden('addGroups');
echo $view->hidden('removeGroups');

echo $view->buttonList()
    ->insert($view->button('Apply', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;