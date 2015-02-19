<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('RenameInterface_header'));

echo $view->objectsCollection('cards')
        ->setAttribute('placeholders', array('link', 'configuration'))
        ->setAttribute('key', 'name')
        ->setAttribute('template', function(\Nethgui\Renderer\Xhtml $w) {
    return $w->panel()->setAttribute('class', 'row')
                    ->insert($w->panel()->setAttribute('class', 'info ${configuration}')
                            ->insert($w->literal('<i class="fa ${link}"></i> '))
                            ->insert($w->textLabel('name'))
                            ->insert($w->literal(' '))
                            ->insert($w->textLabel('hwaddr'))
                            ->insert($w->textLabel('model'))
                    )
                    ->insert($w->hidden('hwaddr'))
                    ->insert($w->panel()->setAttribute('class', 'interface')
                            ->insert($w->selector('interface', $w::SELECTOR_DROPDOWN | $w::LABEL_NONE))
    );
});

$cardsId = $view->getUniqueId('cards');

$view->includeCss("
#${cardsId} {margin-bottom: 2em}
#${cardsId} .row { margin: 2em 0; align-items: top }
#${cardsId} .info { word-spacing: .6em }
#${cardsId} .model { display: block; word-spacing: 1px; }
#${cardsId} .linkon { color: green; position: relative; top: -.24em; left: 2px }
#${cardsId} .linkoff { color: gray; position: relative; top: -.24em; left: 2px }
#${cardsId} .name { font-size: 140%; font-weight: bold }
#${cardsId} .configured .name { color: gray }
");

$adminTodoString = json_encode($view->getModuleUrl('/AdminTodo?notifications'), TRUE);
$renameInterfaceString = json_encode($view->getModuleUrl('RenameInterface'), TRUE);

$view->includeJavascript("
(function ( $ ) {

    function loadPage() {

        var rename = /renameInterface$/.test(window.location.href);

        $.Nethgui.Server.ajaxMessage({
            isMutation: false,
            url: rename ? $renameInterfaceString : $adminTodoString
        });
    }

    $(document).ready(function() {
        loadPage();
    });

})( jQuery);
");

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_RESET | $view::BUTTON_HELP);
