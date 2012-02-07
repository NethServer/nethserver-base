
<!-- general -->
<div class="statusbox">
  <div class="caption"><?php echo $view->translate('Generale') ?></div>
  <div class="content">
    <div class="item">
      <?php echo $view->translate('Domain') ?>:
      <div class="status"><?php echo $view['smbdb']['Workgroup']; ?></div>
    </div>
    <div class="item">
      <?php echo $view->translate('Role') ?>:
      <div class="status"><?php echo $view['smbdb']['ServerRole']; ?></div>
    </div>
    <div class="item">
      <?php echo $view->translate('Hostname') ?>:
      <div class="status"><?php echo $view['smbdb']['ServerName']; ?></div>
    </div>
  </div>
</div>

<!-- shared folders -->
<div class="tab contiguous">
  <div class="caption"><?php echo $view->translate('Shared folders') ?></div>
  <div class="content">
    <table>
      <thead>
        <tr>
          <th><?php echo $view->translate('ID') ?></th>
          <th><?php echo $view->translate('Name') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($view['shfolders'] as $folder => $val): ?>
        <tr>
          <td><?php echo $folder ?></td>
          <td><?php echo $val['Name'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- users -->
<div class="tab">
  <div class="caption"><?php echo $view->translate('Connected Users') ?></div>
  <div class="content">
    <table>
      <thead>
        <tr>
          <th><?php echo $view->translate('Username') ?></th>
          <th><?php echo $view->translate('Group') ?></th>
          <th><?php echo $view->translate('Machine') ?></th>
          <th><?php echo $view->translate('Services') ?></th>
          <th><?php echo $view->translate('Connection DateTime') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($view['smb_users'] as $user => $val): ?>
        <tr>
          <td><?php echo $user ?></td>
          <td><?php echo $val['group'] ?></td>
          <td><?php echo $val['machine'] ?></td>
          <?php if (array_key_exists($val['pid'], $view['smb_services'])): ?>
          <?php foreach ($view['smb_services'][$val['pid']] as $srvpid => $srv): ?>
        <tr>
          <td></td>
          <td></td>
          <td></td>
          <td><?php echo $srv['service'] ?></td>
          <td><?php echo $srv['connected_time'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="height: 682px;"></div>
