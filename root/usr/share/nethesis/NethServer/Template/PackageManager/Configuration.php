<?php

/* @var $view Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('Configuration_header'));

if( ! $view['PolicyDisabled']) {
    $enabledDescription = sprintf('<div class="policy-description">%s</div>', htmlspecialchars($T('NsReleaseLock_enabled_description', array($view['Version']))));
    $disabledDescription = sprintf('<div class="policy-description">%s</div>', htmlspecialchars($T('NsReleaseLock_disabled_description', array($view['Version']))));

    echo $view->hidden('NsReleaseLock');
    echo $view->fieldset('')->setAttribute('template', $T('NsReleaseLock_label'))
        ->insert($view->fieldsetSwitch('NsReleaseLock', 'disabled', $policyFlag)
            ->insert($view->literal($disabledDescription)))
        ->insert($view->fieldsetSwitch('NsReleaseLock', 'enabled', $policyFlag)->setAttribute('label', $T('NsReleaseLock_enabled_label', array($view['Version'])))
            ->insert($view->literal($enabledDescription)))
    ;
}

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('BackToModules', $view::BUTTON_LINK))
    ->insert($view->button('Save', $view::BUTTON_SUBMIT))
;


$view->includeCss('
.policy-description {
    max-width: 40em;
}
');