<div class="rnav">
  <ul id="schemeorder_list" >
    <li id="add_new"><a href="config.php?display=superfecta&amp;scheme=new"><?php echo _('Add Caller ID Scheme')?></a></li>
    <?php foreach($schemes as $scheme) {?>
    <li id="scheme_<?php echo $scheme['scheme']?>" class="scheme" data-name="<?php echo $scheme['scheme']?>">
      <i class="fa fa-toggle-<?php echo $scheme['powered'] ? 'on' : 'off'?>" data-type="power"></i>
      <i class="fa fa-arrow-down <?php echo ($scheme['showdown']) ? '' : 'hidden'?>" data-type="down"></i>
      <i class="fa fa-arrow-up <?php echo ($scheme['showup']) ? '' : 'hidden'?>" data-type="up"></i>
      <i class="fa fa-files-o" data-type="duplicate"></i>
      <i class="fa fa-trash-o" data-type="delete"></i>
      <span onclick="window.location.href='config.php?display=superfecta&amp;scheme=<?php echo $scheme['scheme']?>';"><?php echo $scheme['name']?></span>
    </li>
    <?php } ?>
  </ul>
</div>
<h1><?php echo _('Caller ID Superfecta')?></h1>
<hr>
<div class="intro-message">
  <?php echo _('CallerID Superfecta for FreePBX is a utility program which adds incoming CallerID name lookups to your Asterisk system using multiple data sources')?>
  <br/>
  This Project is hosted/maintained at <a href="https://github.com/POSSA/Caller-ID-Superfecta">https://github.com/POSSA/Caller-ID-Superfecta</a> Feel free to fork/help/complain.
  <br />
  <a target="_blank" href="https://github.com/POSSA/Caller-ID-Superfecta/wiki">This Module's wiki pages can be found here.</a>
</div>
