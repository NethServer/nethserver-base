<?php

if ($view->getModule()->getIdentifier() == 'update') {
    $view->requireFlag($view::INSET_FORM);
    $headerText = 'Update user `${0}`';
} else {
    $headerText = 'Create a new user';
}

echo $view->header('username')->setAttribute('template', $headerText);

$basicInfo = $view->panel()
    ->setAttribute('title', $T('BasicInfo_Title'))
    ->insert($view->textInput('username', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0)))
    ->insert($view->textInput('FirstName'))
    ->insert($view->textInput('LastName'))
    ->insert($view->objectPicker('Groups')
        ->setAttribute('objects', 'GroupsDatasource')
        ->setAttribute('template', 'Groups')
        ->setAttribute('objectLabel', 1));


$infoTab = $view->panel()
    ->setAttribute('title', $T('ExtraInfo_Title'))
    ->insert($view->textInput('Company'))
    ->insert($view->textInput('Dept'))
    ->insert($view->textInput('Street'))
    ->insert($view->textInput('City'))
    ->insert($view->textInput('Phone'));

$tabs = $view->tabs()
    ->insert($basicInfo)
    ->insert($infoTab)
;


if ($view->getModule()->getIdentifier() == 'update') {
    $pluginTabs = array();
    foreach ($view['Plugin'] as $pluginView) {
        if ($pluginView->getModule() instanceof \NethServer\Module\User\PluginInterface)
        {
            $parentId = $pluginView->getModule()->getParentIdentifier();
            $index = sprintf("%4d-%s", $parentId[1],  $parentId[0]);
            if ( ! isset($pluginTabs[$index])) {
                // Create new tab plugin if needed:
                $pluginTabs[$index] = $view->panel()->setAttribute('name', $parentId[0]);
            }
            $pluginTabs[$index]->insert($view->literal($pluginView)); #add plugin view to the tab
        } else {
            $tabs->insert($view->literal($pluginView)); #add a new tab
        }
    }

    ksort($pluginTabs);
    foreach ($pluginTabs as $tab) {
        $tabs->insert($tab);
    }
}

echo $tabs;

$buttons = $view->buttonList($view::BUTTON_SUBMIT  | $view::BUTTON_HELP);

if ($view->getModule()->getIdentifier() == 'update') {
    $buttons->insert($view->button('change-password', $view::BUTTON_LINK));
}
$buttons->insert($view->button('Cancel', $view::BUTTON_CANCEL));

echo $buttons;

