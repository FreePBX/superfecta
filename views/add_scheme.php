<h3><?php echo _('Define Settings for a new Scheme')?></h3>
<form method="POST" action="config.php?display=superfecta" class="fpbx-submit" name="new_scheme" id="superfecta_options">
	<input type="hidden" name="scheme_name_orig" value="<?php echo $scheme_data['name']?>">
	<input type="hidden" name="type" value="add">
	<div class="fpbx-container">
		<div class="display full-border">
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element1"><?php echo _('New Scheme Name')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element1"></i>
								</div>
								<div class="col-md-9"><input type="text" id="element1" class="form-control" name="scheme_name" size="23" maxlength="20" value="<?php echo $scheme_data['name']?>"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element1-help" class="help-block fpbx-help-block"><?php _('Define a new name for this scheme')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="DID"><?php echo _('DID Rules')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="DID"></i>
								</div>
								<div class="col-md-9"><textarea id="DID" class="form-control" tabindex="1" cols="20" rows="5" name="DID"><?php echo $scheme_data['DID']?></textarea></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="DID-help" class="help-block fpbx-help-block"><?php echo _('Define the expected DID Number if your trunk passes DID on incoming calls. <br><br>Leave this blank to match calls with any or no DID info.<br><br>This rule trys both absolute and pattern matching (eg "_2[345]X", to match a range of numbers). (The "_" underscore is optional.)')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="dialrules"><?php echo _('CID Rules')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="dialrules"></i>
								</div>
								<div class="col-md-9"><textarea tabindex="2" class="form-control" id="dialrules" cols="20" rows="5" name="CID_rules"><?php echo $scheme_data['CID_rules']?></textarea></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="dialrules-help" class="help-block fpbx-help-block"><?php echo _('Incoming calls with CID matching the patterns specified here will use this CID Scheme. If this is left blank, this scheme will be used for any CID. It can be used to add or remove prefixes.<br>
							<strong>Many sources require a specific number of digits in the phone number. It is recommended that you use the patterns to remove excess country code data from incoming CID to increase the effectiveness of this module.</strong><br>
							Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched pattern will be executed and the remaining rules will not be acted on.<br /><br /><b>Rules:</b><br />
							<strong>X</strong>&nbsp;&nbsp;&nbsp; matches any digit from 0-9<br />
							<strong>Z</strong>&nbsp;&nbsp;&nbsp; matches any digit from 1-9<br />
							<strong>N</strong>&nbsp;&nbsp;&nbsp; matches any digit from 2-9<br />
							<strong>[1237-9]</strong>&nbsp;   matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br />
							<strong>.</strong>&nbsp;&nbsp;&nbsp; wildcard, matches one or more characters (not allowed before a | or +)<br />
							<strong>|</strong>&nbsp;&nbsp;&nbsp; removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some one dialed "6135551234" but would only pass "5551234" to the Superfecta look up.)<br><strong>+</strong>&nbsp;&nbsp;&nbsp; adds a dialing prefix to the number (for example, 1613+NXXXXXX would match when someone dialed "5551234" and would pass "16135551234" to the Superfecta look up.)<br /><br />
							You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match "016065551234" and dial it as "0116065551234" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing.')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element4"><?php echo _('Lookup Timeout')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element4"></i>
								</div>
								<div class="col-md-9"><input id="element4" type="text" name="Curl_Timeout" class="form-control" size="4" maxlength="5" value="<?php echo $scheme_data['Curl_Timeout']?>"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element4-help" class="help-block fpbx-help-block"><?php echo _('Specify a timeout in seconds for each source. If the source fails to return a result within the alloted time, the script will move on.')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element5"><?php echo _('Superfecta Processor')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element5"></i>
								</div>
								<div class="col-md-9">				<select id="element5" class="form-control" name="processor">
													<?php foreach($scheme_data['processors_list'] as $list) { ?>
														<option value='<?php echo $list['filename']?>' <?php echo $scheme_data['processor'] == $list['filename'] ? 'selected' : ''?>><?php echo $list['name']?></option>
													<?php } ?>
												</select></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element5-help" class="help-block fpbx-help-block"><?php echo _('These are the types of Superfecta Processors')?>:<br />
							<?php foreach($scheme_data['processors_list'] as $list) { ?>
								<strong><?php echo $list['name']?>:</strong> <?php echo $list['description']?><br />
							<?php } ?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element6"><?php echo _('Multifecta Timeout')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element6"></i>
								</div>
								<div class="col-md-9"><input id="element6" type="text" class="form-control" name="multifecta_timeout" maxlength="5" value="<?php echo $scheme_data['multifecta_timeout']?>"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element6-help" class="help-block fpbx-help-block"><?php echo _('Specify a timeout in seconds defining how long multifecta will obey the source priority. After this timeout, the first source to respond with a CNAM will be taken, until "Lookup Timeout" is reached')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element6"><?php echo _('CID Prefix URL')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element7"></i>
								</div>
								<div class="col-md-9"><input type="text" id="element7" class="form-control" name="Prefix_URL" maxlength="255" value="<?php echo $scheme_data['Prefix_URL']?>"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element7-help" class="help-block fpbx-help-block"><?php echo _('If you wish to prefix information on the caller id you can specify a url here where that prefix can be retrieved.<br>The data will not be parsed in any way, and will be truncated to the first 10 characters.<br>Example URL: http://www.example.com/GetCID.php?phone_number=[thenumber]<br>[thenumber] will be replaced with the full 10 digit phone number when the URL is called')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element8"><?php echo _('SPAM Text')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element8"></i>
								</div>
								<div class="col-md-9"><input type="text" class="form-control" name="SPAM_Text" maxlength="20" value="<?php echo $scheme_data['SPAM_Text']?>"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element8-help" class="help-block fpbx-help-block"><?php echo _('This text will be prepended to Caller ID information to help you identify calls as SPAM calls')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element9"><?php echo _('SPAM Text Substituted')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element9"></i>
								</div>
								<div class="col-md-9">
									<span class="radioset">
										<input type="radio" id="SPAM_Text_Substitute_yes" name="SPAM_Text_Substitute" value="Y" <?php echo $scheme_data['SPAM_Text_Substitute'] == "Y" ? 'checked' : ''?>>
										<label for="SPAM_Text_Substitute_yes"><?php echo _('Yes')?></label>
										<input type="radio" id="SPAM_Text_Substitute_no" name="SPAM_Text_Substitute" value="N" <?php echo $scheme_data['SPAM_Text_Substitute'] != "Y" ? 'checked' : ''?>>
										<label for="SPAM_Text_Substitute_no"><?php echo _('No')?></label>
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element9-help" class="help-block fpbx-help-block"><?php echo _('When enabled, the text entered in "SPAM Text" (above) will replace the CID completely rather than pre-pending the CID value')?></span>
					</div>
				</div>
			</div>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="element10"><?php echo _('Enable SPAM Interception')?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="element10"></i>
								</div>
								<div class="col-md-9">
									<span class="radioset">
										<input type="radio" id="enableInterceptor_on" name="enable_interceptor" value="Y" <?php echo $scheme_data['spam_interceptor'] == "Y" ? 'checked' : ''?>>
										<label for="enableInterceptor_on"><?php echo _('Yes')?></label>
										<input type="radio" id="enableInterceptor_off" name="enable_interceptor" value="N" <?php echo $scheme_data['spam_interceptor'] != "Y" ? 'checked' : ''?>>
										<label for="enableInterceptor_off"><?php echo _('No')?></label>
									</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="element10-help" class="help-block fpbx-help-block"><?php echo _('When enabled, Spam calls can be diverted or terminated')?></span>
					</div>
				</div>
			</div>
			<div id="InterceptorVector">
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="element11"><?php echo _('SPAM Send Threshold')?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="element11"></i>
									</div>
									<div class="col-md-9">
										<input type="text" class="form-control" name="SPAM_threshold" size="4" maxlength="2" value="<?php echo $scheme_data['SPAM_threshold']?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="element11-help" class="help-block fpbx-help-block"><?php echo _('This is the threshold to send the call to the specified destination below')?></span>
						</div>
					</div>
				</div>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="element12"><?php echo _('Send Spam Call To')?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="element11"></i>
									</div>
									<div class="col-md-9"><?php echo drawselects('', 0, FALSE, FALSE)?></div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="element12-help" class="help-block fpbx-help-block"><?php echo _('Where to send the call when it is detected as spam')?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
