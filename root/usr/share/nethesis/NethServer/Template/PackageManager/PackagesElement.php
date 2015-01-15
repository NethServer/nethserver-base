<tr><td><?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->textLabel('name')
    ->setAttribute('template', '${0}')
    ->setAttribute('escapeHtml', FALSE);

echo '</td><td>';

echo $view->textLabel('version');

echo '</td><td>';

echo $view->textLabel('release');

?></td></tr>
