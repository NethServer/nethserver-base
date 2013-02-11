<?php

/* @var $view \Nethgui\Renderer\Xhtml */

// Configure the client side validation tests

$lprefix = 'valid_platform,password-strength,password-strength,';

$tests = array(
    array(// missing lowercase
        'label' => $T($lprefix . 7),
        'test' => '[a-z]'
    ),
    array(// missing digit
        'label' => $T($lprefix . 5),
        'test' => '[0-9]'
    ),
    array(// missing uppercase
        'label' => $T($lprefix . 6),
        'test' => '[A-Z]'
    ),
    array(// missing symbol
        'label' => $T($lprefix . 8),
        'test' => '(\W|_)'
    ),
    array(// too short
        'label' => $T($lprefix . 3),
        'test' => '.{7}.*'
    )
);

$view->includeFile('NethServer/Js/jquery.nethserver.passwordstrength.js');
$view->includeJavascript("
jQuery(document).ready(function () {
    $('#" . $view->getUniqueId('newPassword') . "').PasswordStrength({
        position: {my: 'right top', at: 'right bottom'},
        leds: " . json_encode($tests) . ",
        id: " . json_encode($view->getUniqueId('passwordStrength')) . ",
    }).on('keyup', function () { $('#" . $view->getUniqueId('confirmNewPassword') . "').PasswordStrength('refresh'); });

    $('#" . $view->getUniqueId('confirmNewPassword') . "').PasswordStrength({
        position: {my: 'right top', at: 'right bottom'},
        leds: [{ label: '" . $T('ConfirmNoMatch_label') . "', test: function(value) { return value === $('#" . $view->getUniqueId('newPassword') . "').val() }}],
        id: " . json_encode($view->getUniqueId('confirmNewPassword')) . ",
    });
});
");
$view->includeCss("
.PasswordStrength {overflow: hidden; padding-top: 4px}
.PasswordStrength .led {float: left; cursor: help}
.PasswordStrength .led.off.ui-icon { background-image: url(/css/ui/images/ui-icons_cd0a0a_256x240.png) }
.PasswordStrength .led.on.ui-icon { background-image: url(/css/ui/images/ui-icons_888888_256x240.png) }
");

echo $view->textInput('newPassword', $view::TEXTINPUT_PASSWORD);
echo $view->textInput('confirmNewPassword', $view::TEXTINPUT_PASSWORD);
echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL);

