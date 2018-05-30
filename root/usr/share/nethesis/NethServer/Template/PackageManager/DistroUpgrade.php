<?php

/* @var $view Nethgui\Renderer\Xhtml */
echo $view->header('')->setAttribute('template', $T('DistroUpgrade_header'));

echo sprintf('<div class="information"><p>%s</p></div>', htmlspecialchars($T('DistroUpgradeAvailable_message1', $view['DistroUpgradeParams'])));
echo sprintf('<div class="information"><p>%s</p></div>', htmlspecialchars($T('DistroUpgradeAvailable_message2', $view['DistroUpgradeParams'])));

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('UpgradeLater', $view::BUTTON_LINK))
    ->insert($view->button('DistroUpgrade', $view::BUTTON_SUBMIT))
;

$view->includeCss('
#PackageManager_DistroUpgrade .information {
    font-size: 1.2em;
    max-width: 505px;
    margin-bottom: 1em;
}
');