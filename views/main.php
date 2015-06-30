<div class="panel panel-info">
  <div class="panel-heading">
    <div class="panel-title">
      <a href="#" data-toggle="collapse" data-target="#moreinfo"><i class="glyphicon glyphicon-info-sign"></i></a>&nbsp;&nbsp;&nbsp;<?php echo _('What is CallerID Superfecta?')?></div>
  </div>
  <!--At some point we can probably kill this... Maybe make is a 1 time panel that may be dismissed-->
  <div class="panel-body collapse" id="moreinfo">
    <?php echo _('CallerID Superfecta for FreePBX is a utility program which adds incoming CallerID name lookups to your Asterisk system using multiple data sources')?>
  </div>
</div>
  <table id="scheme-list"
         data-unique-id="_id"
         data-cache="false"
         data-cookie="true"
         data-cookie-id-table="superfectaschemes"
         data-maintain-selected="true"
         data-toggle="table"
         data-pagination="true"
         data-search="true"
         class="table table-striped">
  <thead>
    <tr>
      <th data-field="order" data-sortable="true"><?php echo _('Order')?></th>
      <th data-field="name" data-sortable="true"><?php echo _('Name')?></th>
      <th data-field="actions"><?php echo _('Actions')?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($schemes as $order => $scheme) {?>
      <tr class="scheme" id="<?php echo $scheme['name']?>" data-name="<?php echo $scheme['name']?>">
        <td><?php echo $order?></td>
        <td>
          <a href="?display=superfecta&amp;action=edit&amp;scheme=<?php echo $scheme['name']?>"><?php echo $scheme['name']?></a>
        </td>
        <td class="scheme-actions">
          <i class="fa fa-toggle-<?php echo $scheme['powered'] ? 'on' : 'off'?>" data-type="power"></i>
    			<i class="fa fa-arrow-down <?php echo ($scheme['showdown']) ? '' : 'hidden'?>" data-type="down"></i>
    			<i class="fa fa-arrow-up <?php echo ($scheme['showup']) ? '' : 'hidden'?>" data-type="up"></i>
    			<i class="fa fa-files-o" data-type="duplicate"></i>
    			<i class="fa fa-trash-o" data-type="delete"></i>
        </td>
      </tr>
    <?php } ?>
  </tbody>
</table>
