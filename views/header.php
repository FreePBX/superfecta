<div class="rnav">
	<ul id="schemeorder_list" >
		<li id="add_new"><a href="config.php?display=superfecta&amp;action=add"><?php echo _('Add Caller ID Scheme')?></a></li>
		<?php foreach($schemes as $scheme) {?>
		<li id="scheme_<?php echo $scheme['scheme']?>" class="scheme" data-name="<?php echo $scheme['name']?>">
			<i class="fa fa-toggle-<?php echo $scheme['powered'] ? 'on' : 'off'?>" data-type="power"></i>
			<i class="fa fa-arrow-down <?php echo ($scheme['showdown']) ? '' : 'hidden'?>" data-type="down"></i>
			<i class="fa fa-arrow-up <?php echo ($scheme['showup']) ? '' : 'hidden'?>" data-type="up"></i>
			<i class="fa fa-files-o" data-type="duplicate"></i>
			<i class="fa fa-trash-o" data-type="delete"></i>
			<span onclick="window.location.href='config.php?display=superfecta&amp;action=edit&amp;scheme=<?php echo urlencode($scheme['name'])?>';"><?php echo $scheme['name']?></span>
		</li>
		<?php } ?>
	</ul>
</div>
<h1><?php echo _('Caller ID Superfecta')?></h1>
<hr class="breaker">
