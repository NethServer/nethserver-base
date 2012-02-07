
<div class="left_panel">
  <div class="statusbox">
    <div class="caption"><?php echo $view->translate("General") ?></div>
    <div class="content">
      <div class="item">
        <?php echo $view->translate("Hostname") ?>:
        <div class="status"><?php echo $view['hostname']; ?></div>
      </div>
      <div class="item">
        <?php echo $view->translate("Domain") ?>:
        <div class="status"><?php echo $view['domain']; ?></div>
      </div>
      <div class="item">
        <?php echo $view->translate("IP") ?>:
        <div class="status"><?php echo $view['ip']; ?></div>
      </div>
    </div>
  </div>

  <!-- NICs -->
  <div class="statusbox">
    <div class="caption"><?php echo $view->translate("Interface") ?></div>
    <div class="content">
      <?php foreach ($view['nics'] as $nic => $props): ?>
      <div class="item">
        <?php echo $view->translate($props['title']) ?>
        <div class="status"><?php echo $props['ip'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  
  <!-- services -->
  <div class="statusbox">
    <div class="caption"><?php echo $view->translate("Services Status") ?></div>
    <div class="content">
      <?php foreach ($view['services'] as $srv => $props): ?>
      <?php if ($props['enabled']): ?>
      <div class="item">
        <?php echo $srv ?>
        <div class="status">
          <div class="<?php echo $props['running'] ? 'running' : 'stopped' ?>"></div>
        </div>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  
  <!-- users -->
  <div class="statusbox">
    <div class="caption"><?php echo $view->translate("Users") ?></div>
    <div class="content">
      <div class="item">
        <?php echo $view->translate("Actived") ?>
        <div class="status">
          <?php echo $view['users_stats']['active'] ?>
        </div>
      </div>
      <div class="item">
        <?php echo $view->translate("Blocked") ?>
        <div class="status">
          <?php echo $view['users_stats']['blocked'] ?>
        </div>
      </div>
    </div>
  </div>
  
  <div id="active_alerts" class="tab">
    <div class="caption"><?php echo $view->translate("Active Alerts") ?></div>
    <div class="content">
      <table>
        <thead>
          <tr>
            <th><?php echo $view->translate("Parameter") ?></th>
            <th><?php echo $view->translate("Code") ?></th>
            <th><?php echo $view->translate("Priority") ?></th>
            <th><?php echo $view->translate("DateTime") ?></th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="right_panel">
  
  <!-- Network traffic -->
  <div class="chart">
    <div class="title"><?php echo $view->translate("Network traffic") ?></div>
    <div class="content" id="network_chart"></div>
  </div>
  
</div>

<div style="height: 600px;"></div>
