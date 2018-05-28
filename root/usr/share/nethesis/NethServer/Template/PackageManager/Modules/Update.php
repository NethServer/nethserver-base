<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->buttonList()
    ->insert($view->button('DoUpdate', $view::BUTTON_SUBMIT))
;
?><table class="SmallTable"><thead>
        <tr style="border-bottom: 1px solid gray"><th><?php echo $T('rpm_name') ?></th>
            <th><?php echo $T('rpm_version')?></th>
            <th><?php echo $T('rpm_release') ?></th>
        </tr></thead><?php

echo $view->objectsCollection('updates')
   ->setAttribute('ifEmpty', function (\Nethgui\Renderer\Xhtml $renderer) {
        return sprintf('<tr><td colspan="3">%s</td></tr>', $renderer->translate('noupdates_message'));
    })
    ->setAttribute('tag', 'tbody')
    ->setAttribute('template', 'NethServer\Template\PackageManager\PackagesElement')
    ->setAttribute('key', 'name');

?></table><?php

$changelogTarget = $view->getClientEventTarget('changelog');

$view->includeCss("
table.SmallTable {width: auto;  font-size: 12px}
.SmallTable td {padding: 0 1ex 4px 0}
.SmallTable th {text-align: left; padding: 0 1ex 4px 0}
.${changelogTarget} { font-size: smaller; border: 1px solid #d2d2d2; background: #eee; padding: 1em }

.nextRelease .Controller {margin-top: 4px}
");

echo $view->fieldset(NULL, $view::FIELDSET_EXPANDABLE)->setAttribute('template', $T('Changelog_label'))
        ->insert($view->textLabel('changelog')->setAttribute('tag', 'pre'));

$upstreamReleaseTemplate =
    '<i class="fa fa-li fa-eye" aria-hidden="true"></i>' .
    '<p>{{message}}</p>' .
    '<div class="Controller"><div class="Action">' .
        $view->button('ChangePolicy', $view::BUTTON_LINK)->setAttribute('value', '{{link}}') .
    '</div></div>';

$view->getModule()->notifications->defineTemplate('nextRelease', $upstreamReleaseTemplate, 'nextRelease bg-yellow');
