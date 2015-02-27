<?php

/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header()->setAttribute('template', $T('RenameInterface_header'));

echo $view->objectsCollection('cards')
        ->setAttribute('placeholders', array('link', 'configuration', 'currentRole'))
        ->setAttribute('key', 'name')
        ->setAttribute('ifEmpty', function(\Nethgui\Renderer\Xhtml $w) use ($T) {
            return $w->panel()->setAttribute('class', 'ifEmpty')->insert($w->literal($T('All roles are correctly assigned to network cards.')));
        })
        ->setAttribute('template', function(\Nethgui\Renderer\Xhtml $w) use ($T) {
    return $w->panel()->setAttribute('class', 'row ${configuration}')
            ->insert($w->panel()->setAttribute('class', 'title')
                ->insert($w->textLabel('name')->setAttribute('class', 'name'))
                ->insert($w->literal(sprintf(' <span class="actionLabel">%s</span> ', $T('roleis_label'))))
                ->insert($w->textLabel('currentRole')->setAttribute('class', 'role ${currentRole}')->setAttribute('tag', 'b'))
                ->insert($w->selector('interface', $w::SELECTOR_DROPDOWN | $w::LABEL_NONE))
            )
        ->insert($w->textLabel('linkText')->setAttribute('class', 'link ${link}'))
        ->insert($w->textLabel('hwaddr')->setAttribute('template', $T('MAC: ${0}')))        
        ->insert($w->textLabel('model')->setAttribute('template', $T('Model: ${0}')))        
        ->insert($w->hidden('hwaddr'))
    ;
});

$cardsId = $view->getUniqueId('cards');

$view->includeCss("

#${cardsId} {margin-bottom: 2em}
#${cardsId} .ifEmpty {text-align: center; padding: 5em 1em; font-size: 150%; color: #ccc}
#${cardsId} .row { margin: 2em 0; align-items: top }
#${cardsId} .name { font-size: 160%; font-weight: bold;display: bl }
#${cardsId} .title { margin-bottom: 2px }
#${cardsId} .configured .name { color: gray }
#${cardsId} select { padding: 1px; font-size: 100% }
#${cardsId} .link {display: inline-block; padding: 2px; border-radius: 2px; border: 1px solid gray; margin-right: 1ex; background: #ccc}
#${cardsId} .linkup {background: lime; color: black; border-color: lime}
#${cardsId} .model {display: block}
#${cardsId} .configured .Selector {display:none}
#${cardsId} .unconfigured .TextLabel.role {display:none}
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

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP)
        ->insert($view->button('Refresh', $view::BUTTON_LINK)->setAttribute('value', $view->getModuleUrl('?refresh')));
