<script>
var scheme = "<?php echo $scheme_data['name']?>";
</script>
<h3 class="name"><?php echo sprintf(_('Scheme Name: %s'), $scheme_data['name'])?> <i class="fa fa-wrench " id="configure"></i></h3>
<h4 class="name" id="super-debug"><?php echo _('Debug/Test Run Scheme')?> <i class="fa fa-play"></i></h4>
<div class="instructions">
	<?php echo _('Add, Remove, Enable, Disable, Sort and Configure data sources as appropriate for your situation.')?>
	<br/>
	<?php echo _('Select which data source(s) to use for your lookups, and the order in which you want them used:')?>
</div>
<table id="sources" class="alt_table">
	<tr id="row_header">
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th><?php echo _('Data Source Name')?></th>
		<th><?php echo _('Description')?></th>
		<th class="enabled"><?php echo _('Enabled')?></th>
	</tr>
	<?php foreach($sources as $source) { ?>
		<tr id="<?php echo $source['source_name']?>" data-name="<?php echo $source['source_name']?>" class="<?php echo $source['status']?> source">
			<td><i class="fa fa-arrow-down <?php echo $source['showdown'] ? '' : 'hidden' ?>" data-type="down"></i></td>
			<td><i class="fa fa-arrow-up <?php echo $source['showup'] ? '' : 'hidden' ?>" data-type="up"></i></td>
			<td><i class="fa fa-wrench <?php echo $source['configure'] ? '' : 'hidden' ?>" data-type="configure"></i></td>
			<td><?php echo $source['pretty_source_name']?></td>
			<td class="description"><?php echo $source['description']?></td>
			<td class="enabled">
				<span class="radioset">
					<input type="radio" id="<?php echo $source['source_name']?>_enabled_yes" name="<?php echo $source['source_name']?>_enabled" value="on" <?php echo $source['enabled'] ? 'checked' : ''?>/>
					<label for="<?php echo $source['source_name']?>_enabled_yes"><?php echo _('Yes')?></label>
					<input type="radio" id="<?php echo $source['source_name']?>_enabled_no" name="<?php echo $source['source_name']?>_enabled" value="off" <?php echo $source['enabled'] ? '' : 'checked'?>/>
					<label for="<?php echo $source['source_name']?>_enabled_no"><?php echo _('No')?></label>
				</span>
			</td>
		</tr>
	<?php } ?>
</table>
<div id="scheme-dialog-form" title="<?php echo _('Edit Scheme')?>">
	<form method="POST" action="ajax.php?module=superfecta&amp;command=update_scheme" name="superfecta_options" id="superfecta_options">
		<input type="hidden" name="scheme_name_orig" value="<?php echo $scheme_data['name']?>">
		<div class="form-group">
			<label><?php echo _('Scheme Name')?></label>
			<input type="text" class="form-control" name="scheme_name" size="23" maxlength="20" value="<?php echo $scheme_data['name']?>">
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('DID Rules')?><span><?php echo _('Define the expected DID Number if your trunk passes DID on incoming calls. <br><br>Leave this blank to match calls with any or no DID info.<br><br>This rule trys both absolute and pattern matching (eg "_2[345]X", to match a range of numbers). (The "_" underscore is optional.)')?></span></a></label>
			<textarea id="DID" class="form-control" tabindex="1" cols="20" rows="5" name="DID"><?php echo $scheme_data['DID']?></textarea>
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('CID Rules')?><span><?php echo _('Incoming calls with CID matching the patterns specified here will use this CID Scheme. If this is left blank, this scheme will be used for any CID. It can be used to add or remove prefixes.<br>
				<strong>Many sources require a specific number of digits in the phone number. It is recommended that you use the patterns to remove excess country code data from incoming CID to increase the effectiveness of this module.</strong><br>
				Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched pattern will be executed and the remaining rules will not be acted on.<br /><br /><b>Rules:</b><br />
				<strong>X</strong>&nbsp;&nbsp;&nbsp; matches any digit from 0-9<br />
				<strong>Z</strong>&nbsp;&nbsp;&nbsp; matches any digit from 1-9<br />
				<strong>N</strong>&nbsp;&nbsp;&nbsp; matches any digit from 2-9<br />
				<strong>[1237-9]</strong>&nbsp;   matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br />
				<strong>.</strong>&nbsp;&nbsp;&nbsp; wildcard, matches one or more characters (not allowed before a | or +)<br />
				<strong>|</strong>&nbsp;&nbsp;&nbsp; removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some one dialed "6135551234" but would only pass "5551234" to the Superfecta look up.)<br><strong>+</strong>&nbsp;&nbsp;&nbsp; adds a dialing prefix to the number (for example, 1613+NXXXXXX would match when someone dialed "5551234" and would pass "16135551234" to the Superfecta look up.)<br /><br />
				You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match "016065551234" and dial it as "0116065551234" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing.')?></span>
				</a>
			</label>
			<textarea tabindex="2" class="form-control" id="dialrules" cols="20" rows="5" name="CID_rules"><?php echo $scheme_data['CID_rules']?></textarea>
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('Lookup Timeout')?><span><?php echo _('Specify a timeout in seconds for each source. If the source fails to return a result within the alloted time, the script will move on.')?></span></a></label>
			<input type="text" name="Curl_Timeout" class="form-control" size="4" maxlength="5" value="<?php echo $scheme_data['Curl_Timeout']?>">
		</div>
		<div class="form-group">
			<label>
				<a href="javascript:return(false);" class="info"><?php echo _('Superfecta Processor')?>
					<span><?php echo _('These are the types of Superfecta Processors')?>:<br />
						<?php foreach($scheme_data['processors_list'] as $list) { ?>
							<strong><?php echo $list['name']?>:</strong> <?php echo $list['description']?><br />
						<?php } ?>
					</span>
				</a>
			</label>
			<select class="form-control" name="processor">
				<?php foreach($scheme_data['processors_list'] as $list) { ?>
					<option value='<?php echo $list['filename']?>' <?php echo ($scheme_data['processor'] == $list['filename']) ? 'selected' : ''?>><?php echo $list['name']?></option>
				<?php } ?>
			</select>
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('Multifecta Timeout')?><span><?php echo _('Specify a timeout in seconds defining how long multifecta will obey the source priority. After this timeout, the first source to respond with a CNAM will be taken, until "Lookup Timeout" is reached')?></span></a></label>
			<input type="text" class="form-control" name="multifecta_timeout" maxlength="5" value="<?php echo $scheme_data['multifecta_timeout']?>">
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('CID Prefix URL')?><span><?php echo _('If you wish to prefix information on the caller id you can specify a url here where that prefix can be retrieved.<br>The data will not be parsed in any way, and will be truncated to the first 10 characters.<br>Example URL: http://www.example.com/GetCID.php?phone_number=[thenumber]<br>[thenumber] will be replaced with the full 10 digit phone number when the URL is called')?></span></a></label>
			<input type="text" class="form-control" name="Prefix_URL" maxlength="255" value="<?php echo $scheme_data['Prefix_URL']?>">
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('SPAM Text')?><span><?php echo _('This text will be prepended to Caller ID information to help you identify calls as SPAM calls')?></span></a></label>
			<input type="text" class="form-control" name="SPAM_Text" maxlength="20" value="<?php echo $scheme_data['SPAM_Text']?>">
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('SPAM Text Substituted')?><span><?php echo _('When enabled, the text entered in "SPAM Text" (above) will replace the CID completely rather than pre-pending the CID value')?></span></a></label>
			<br/>
			<span class="radioset">
				<input type="radio" id="SPAM_Text_Substitute_on" name="SPAM_Text_Substitute" value="on" <?php echo $scheme_data['SPAM_Text_Substitute'] == 'Y' ? 'checked' : ''?>>
				<label for="SPAM_Text_Substitute_on"><?php echo _("yes")?></label>
				<input type="radio" id="SPAM_Text_Substitute_off" name="SPAM_Text_Substitute" value="off" <?php echo $scheme_data['SPAM_Text_Substitute'] == "Y" ? '' : 'checked'?>>
				<label for="SPAM_Text_Substitute_off"><?php echo _("no")?></label>
			</span>
		</div>
		<div class="form-group">
			<label><a href="javascript:return(false);" class="info"><?php echo _('Enable SPAM Interception')?><span><?php echo _('When enabled, Spam calls can be diverted or terminated')?></span></a></label>
			<br/>
			<span class="radioset">
				<input type="radio" id="enableInterceptor_on" name="enable_interceptor" value="on" <?php echo $scheme_data['spam_interceptor'] ? 'checked' : ''?>>
				<label for="enableInterceptor_on"><?php echo _("yes")?></label>
				<input type="radio" id="enableInterceptor_off" name="enable_interceptor" value="off" <?php echo $scheme_data['spam_interceptor'] ? '' : 'checked'?>>
				<label for="enableInterceptor_off"><?php echo _("no")?></label>
			</span>
		</div>
		<div id="InterceptorVector">
			<div class="form-group">
				<label><a href="javascript:return(false);" class="info"><?php echo _('SPAM Send Threshold')?><span><?php echo _('This is the threshold to send the call to the specified destination below')?></span></a></label>
				<input type="text" class="form-control" name="SPAM_threshold" size="4" maxlength="2" value="<?php echo $scheme_data['SPAM_threshold']?>">
			</div>
			<div class="form-group">
				<label><?php echo _('Send Spam Call To')?></label>
				<?php echo $scheme_data['interceptor_select']?>
			</div>
		</div>
	</form>
</div>
<div id="debug-dialog" title="<?php echo _('Debug/Test Run Scheme')?>">
	<div class="form-group" id="didnumber">
		<label><a href="javascript:return(false);" class="info"><?php echo _('DID Number')?><span><?php echo _('The DID to test this scheme against')?></span></a></label>
		<input type="text" class="form-control" id="thedid" size="15" maxlength="20" name="thedid">
	</div>
	<div class="form-group">
		<label><a href="javascript:return(false);" class="info"><?php echo _('Phone Number')?><span><?php echo _('Phone number to test this scheme against')?></span></a></label>
		<input type="text" class="form-control" id="thenumber" size="15" maxlength="20" name="thenumber">
	</div>
	<div class="form-group">
		<label><a href="javascript:return(false);" class="info"><?php echo _('Debug Level')?><span><?php echo _('Debug Level to display')?></span></a></label>
		<select name="debug" id="debug_level" class="form-control">
			<option value="0"><?php echo _('NONE')?></option>
			<option value="1" selected=""><?php echo _('INFO')?></option>
			<option value="2"><?php echo _('WARN')?></option>
			<option value="3"><?php echo _('ALL')?></option>
		</select>
	</div>
	<div class="debug-window">
	</div>
</div>
