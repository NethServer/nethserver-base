<?php
echo $view->panel()
        ->insert($view->header('address')->setAttribute('template', $T('Authorize network')))
        ->insert($view->textInput('address'))
        ->insert($view->textInput('mask'));
        
echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL);
