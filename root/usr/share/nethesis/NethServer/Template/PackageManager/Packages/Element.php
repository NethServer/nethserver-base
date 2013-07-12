<tr><td><?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->textLabel('name')
    ->setAttribute('template', '<a href=${1}>${0}</a>')
    ->setAttribute('escapeHtml', FALSE);

echo '</td><td>';

echo $view->textLabel('version');

echo '</td><td>';

echo $view->textLabel('release');

?></td></tr>
