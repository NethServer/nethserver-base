<?php

echo "<div class='dashboard-item'>";
echo $view->header()->setAttribute('template',$T('raidstatus_title'));
if (count($view['status']['results']) == 0) {
    echo $T('no_raid');
} else {
    foreach ($view['status']['results'] as $raid) {
        echo "<dt class='raid'>".$raid['md']."</dt> <dd class='raid-".$raid['result']."'>".$T($raid['result'])."</dd>";
        echo "<dl class='raid'>";
        echo "<dt>".$T('level')."</dt><dd>RAID"; echo $raid['level']; echo "</dd>";
        echo "<dt>".$T('devs')."</dt><dd>"; echo $raid['ok_devs'] . "/" . $raid['tot_devs'] ; 
        echo " (".($raid['active_devs']=='none'?$T("none"):$raid['active_devs']).")";
        echo "</dd>";
        if ($raid['failed_devs'] != 'none') {
            echo "<dt>".$T('failed_devs')."</dt><dd>"; echo $raid['failed_dev']; echo "</dd>";
        }
        if ($raid['spare_devs'] != 'none') {
            echo "<dt>".$T('spare_devs')."</dt><dd>"; echo $raid['spare_devs']; echo "</dd>";
        }
        echo "</dl>";
    }
}
echo "</div>";

$view->includeCSS("
  dl.raid {
     margin-bottom: 8px;
  }
  dl.raid:last-of-type {
     margin-bottom: 0px;
  }

  dt.raid {
      font-size: 1.2em;
      font-weight: bold;
  }
  dd.raid-ok {
      padding: 3px;
      color: green;
      font-weight: bold;
  }
  dd.raid-critical {
      padding: 3px;
      color: red;
      font-weight: bold;
  }

");

