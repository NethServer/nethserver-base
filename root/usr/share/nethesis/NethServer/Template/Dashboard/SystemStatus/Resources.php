<?php
$view->includeFile('NethServer/Js/jquery.nethserver.dashboard.resources.js');

echo $view->panel()
    ->setAttribute('title', $T('Resources_Title'))
    ->insert($view->textLabel('load1')->setAttribute('tag','p')->setAttribute('template',$T('load1_label: ${0}')))
    ->insert($view->textLabel('load5')->setAttribute('tag','p')->setAttribute('template',$T('load5_label: ${0}')))
    ->insert($view->textLabel('load15')->setAttribute('tag','p')->setAttribute('template',$T('load15_label: ${0}')));


echo $view->panel()
    ->insert($view->textLabel('days')->setAttribute('template',$T('uptime_days: ${0}')))
    ->insert($view->textLabel('hours')->setAttribute('template',$T('uptime_hours: ${0}')))
    ->insert($view->textLabel('minutes')->setAttribute('template',$T('uptime_minutes: ${0}')))
    ->insert($view->textLabel('seconds')->setAttribute('template',$T('uptime_seconds: ${0}')));


echo $view->panel()
    ->insert($view->textLabel('MemTotal')->setAttribute('template',$T('memtotal_label: ${0}')))
    ->insert($view->textLabel('MemFree')->setAttribute('template',$T('memfree_label: ${0}')));

echo $view->panel()
    ->insert($view->textLabel('SwapTotal')->setAttribute('template',$T('swaptotal_label: ${0}')))
    ->insert($view->textLabel('SwapFree')->setAttribute('template',$T('swapfree_label: ${0}')));


echo $view->panel()
    ->insert($view->textLabel('root_total')->setAttribute('template',$T('root_total_label: ${0}')))
    ->insert($view->textLabel('root_used')->setAttribute('template',$T('root_used_label: ${0}')))
    ->insert($view->textLabel('root_avail')->setAttribute('template',$T('root_avail_label: ${0}')));
   
