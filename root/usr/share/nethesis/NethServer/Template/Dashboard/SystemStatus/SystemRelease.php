<?php

echo "<div class='dashboard-item'>";
echo "<dl>";
echo $view->header()->setAttribute('template',$T('release_title'));
echo "<dt>".$T('release_label')."</dt><dd>"; echo $view->textLabel('release'); echo "</dd>";
echo "<dt>".$T('kernel_label')."</dt><dd>"; echo $view->textLabel('kernel'); echo "</dd>";
echo "</dl>";
echo "</div>";
