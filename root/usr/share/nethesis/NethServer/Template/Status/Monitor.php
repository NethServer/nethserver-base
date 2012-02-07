
<!-- Network traffic -->
<div class="chart">
  <div class="title"><?php echo $view->translate('Network traffic') ?></div>
  <div class="content" id="monitor_network_chart"></div>
</div>


<!-- CPU Load -->
<div class="chart">
  <div class="title"><?php echo $view->translate('CPU load') ?></div>
  <div class="content" id="cpuload_chart">
    <img src="/load.gif" />
  </div>
</div>

<!-- Memory Usage -->
<div class="chart">
  <div class="title"><?php echo $view->translate('Memory usage') ?></div>
  <div class="content" id="mem_chart">
    <img src="/mem.gif" />
  </div>
</div>

<!-- Disk Usage -->
<div class="chart">
  <div class="title"><?php echo $view->translate('Disk usage') ?></div>
  <div class="content" id="disk_chart"></div>
</div>

<div style="min-height: 682px;"></div>
