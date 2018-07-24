<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('SetPppoeParameters_header'));

echo $view->selector('PppoeInterface', $view::SELECTOR_DROPDOWN )->setAttribute('choices', 'PppoeInterfaceDatasource');
echo $view->textInput('PppoeProvider');
echo $view->textInput('PppoeUser');
echo $view->textInput('PppoePassword', $view::TEXTINPUT_PASSWORD);
echo $view->selector('PppoeAuthType', $view::SELECTOR_DROPDOWN)->setAttribute('choices', \Nethgui\Widget\XhtmlWidget::hashToDatasource(array('auto' => $T('AuthType_auto'), 'pap' => $T('AuthType_pap'), 'chap' => $T('AuthType_chap'))));

echo $view->fieldset()->setAttribute('template', $T('multiwan_label'))
    ->insert($view->textInput('ProviderName'))
    ->insert($view->textInput('Weight'))
;

echo $view->fieldset()->setAttribute('template', $T('trafficshaping_label'))
    ->insert($view->textInput('FwInBandwidth'))
    ->insert($view->textInput('FwOutBandwidth'));

echo $view->buttonList($view::BUTTON_HELP)
    ->insert($view->button('Submit', $view::BUTTON_SUBMIT))
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
;
