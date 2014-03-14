<?php 
/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);

echo $view->header()->setAttribute('template', $T('Packages_header'));

?><table class="SmallTable"><thead>
        <tr style="border-bottom: 1px solid gray"><th><?php echo $T('rpm_name') ?></th>
            <th><?php echo $T('rpm_version')?></th>
            <th><?php echo $T('rpm_release') ?></th>
        </tr></thead><?php

echo $view->objectsCollection('packages')
    ->setAttribute('tag', 'tbody')    
    ->setAttribute('template', 'NethServer\Template\PackageManager\Packages\Element')
    ->setAttribute('key', 'name');

$packagesTarget = $view->getClientEventTarget('packages');

$view->includeCss("
table.SmallTable {width: auto;  font-size: 12px}
.SmallTable td {padding: 0 1ex 4px 0}
.SmallTable th {text-align: left; padding: 0 1ex 4px 0}
");
?></table><?php


echo $view->buttonList($view::BUTTON_HELP | $view::BUTTON_CANCEL);

    