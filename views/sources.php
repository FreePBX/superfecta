<h3>Scheme Name: <?php echo $scheme?> <i class="fa fa-wrench " id="configure"></i></h3>
<div style="font-size: 90%;">
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
    <tr id="<?php echo $source['source_name']?>" data-name="<?php echo $source['source_name']?>" data-scheme="<?php echo $scheme?>" class="<?php echo $source['status']?> source">
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
<script>
  var scheme = "<?php echo $_REQUEST['scheme']?>";
</script>
<div id="scheme-dialog-form" title="Create new user">
  <form method="POST" action="ajax.php?module=superfecta&amp;command=update_scheme" name="superfecta_options" id="superfecta_options">
    <input type="hidden" name="scheme_name_orig" value="<?php echo $scheme?>">
    <div class="form-group">
      <label>Scheme Name:</label>
      <input type="text" class="form-control" name="scheme_name" size="23" maxlength="20" value="<?php echo $scheme?>">
    </div>
    <div class="form-group">
      <label><a href="javascript:return(false);" class="info">DID Rules<span>Define the expected DID Number if your trunk passes DID on incoming calls. <br><br>Leave this blank to match calls with any or no DID info.<br><br>This rule trys both absolute and pattern matching (eg "_2[345]X", to match a range of numbers). (The "_" underscore is optional.)</span></a></label>
      <textarea id="DID" class="form-control" tabindex="1" cols="20" rows="5" name="DID"><?php echo $scheme_data['did']?></textarea>
    </div>
    <div class="form-group">
      <label><a href="javascript:return(false);" class="info">CID Rules<span>Incoming calls with CID matching the patterns specified here will use this CID Scheme. If this is left blank, this scheme will be used for any CID. It can be used to add or remove prefixes.<br>
        <strong>Many sources require a specific number of digits in the phone number. It is recommended that you use the patterns to remove excess country code data from incoming CID to increase the effectiveness of this module.</strong><br>
        Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched pattern will be executed and the remaining rules will not be acted on.<br /><br /><b>Rules:</b><br />
        <strong>X</strong>&nbsp;&nbsp;&nbsp; matches any digit from 0-9<br />
        <strong>Z</strong>&nbsp;&nbsp;&nbsp; matches any digit from 1-9<br />
        <strong>N</strong>&nbsp;&nbsp;&nbsp; matches any digit from 2-9<br />
        <strong>[1237-9]</strong>&nbsp;   matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br />
        <strong>.</strong>&nbsp;&nbsp;&nbsp; wildcard, matches one or more characters (not allowed before a | or +)<br />
        <strong>|</strong>&nbsp;&nbsp;&nbsp; removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some one dialed "6135551234" but would only pass "5551234" to the Superfecta look up.)<br><strong>+</strong>&nbsp;&nbsp;&nbsp; adds a dialing prefix to the number (for example, 1613+NXXXXXX would match when someone dialed "5551234" and would pass "16135551234" to the Superfecta look up.)<br /><br />
        You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match "016065551234" and dial it as "0116065551234" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing.</span>
        </a>
      </label>
      <textarea tabindex="2" class="form-control" id="dialrules" cols="20" rows="5" name="CID_rules"><?php echo $scheme_data['cid_rules']?></textarea>
    </div>
    <div class="form-group">
      <label><a href="javascript:return(false);" class="info">Lookup Timeout<span>Specify a timeout in seconds for each source. If the source fails to return a result within the alloted time, the script will move on.</span></a></label>
      <input type="text" name="Curl_Timeout" class="form-control" size="4" maxlength="5" value="<?php echo $scheme_data['curl_timeout']?>">
    </div>
    <div class="form-group">
      <label>
        <a href="javascript:return(false);" class="info">Superfecta Processor
          <span>These are the types of Superfecta Processors:<br />
            <?php foreach($processors_list as $list) { ?>
              <strong><?php echo $list['name']?>:</strong> <?php echo $list['description']?><br />
            <?php } ?>
          </span>
        </a>
      </label>
      <select class="form-control" name="processor">
        <?php foreach($processors_list as $list) { ?>
          <option value='<?php echo $list['filename']?>' <?php echo $list['selected'] ? 'selected' : ''?>><?php echo $list['name']?></option>
        <?php } ?>
      </select>
    </div>
    <div class="form-group">
      <label><a href="javascript:return(false);" class="info">Multifecta Timeout<span>Specify a timeout in seconds defining how long multifecta will obey the source priority. After this timeout, the first source to respond with a CNAM will be taken, until "Lookup Timeout" is reached.</span></a></label>
      <input type="text" class="form-control" name="multifecta_timeout" size="4" maxlength="5" value="<?php echo $scheme_data['multifecta_timeout']?>">
    </div>
    <table border="0" id="table1" cellspacing="1">
      <tr>
        <td><a href="javascript:return(false);" class="info">CID Prefix URL<span>If you wish to prefix information on the caller id you can specify a url here where that prefix can be retrieved.<br>The data will not be parsed in any way, and will be truncated to the first 10 characters.<br>Example URL: http://www.example.com/GetCID.php?phone_number=[thenumber]<br>[thenumber] will be replaced with the full 10 digit phone number when the URL is called.</span></a></td>
        <td><input type="text" name="Prefix_URL" size="23" maxlength="255" value="{$prefix_url}"></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td><a href="javascript:return(false);" class="info">SPAM Text<span>This text will be prepended to Caller ID information to help you identify calls as SPAM calls.</span></a></td>
        <td><input type="text" name="SPAM_Text" size="23" maxlength="20" value="{$spam_text}"></td>
      </tr>
      <tr>
        <td><a href="javascript:return(false);" class="info">SPAM Text Substituted<span>When enabled, the text entered in "SPAM Text" (above) will replace the CID completely rather than pre-pending the CID value.</span></a></td>
        <td>
          <input type="checkbox" name="SPAM_Text_Substitute" value="Y" {if condition="$spam_text_substitute === TRUE"}checked{/if}>
        </td>
      </tr>
      <tr>
        <td><a href="javascript:return(false);" class="info">Enable SPAM Interception<span>When enabled, Spam calls can be diverted or terminated.</span></a></td>
        <td>
          <input type="checkbox" onclick="toggleInterceptor()" name="enable_interceptor" value="Y" {if condition="$spam_int === TRUE"}checked{/if}>
        </td>
      </tr>
    </table>
    <table id="InterceptorVector" border="0">
      <tr>
        <td><a href="javascript:return(false);" class="info">SPAM Send Threshold<span>This is the threshold to send the call to the specified destination below</span></a></td>
        <td><input type="text" name="SPAM_threshold" size="4" maxlength="2" value="{$spam_threshold}"></td>
      </tr>
      <tr class="incerceptorCell">
        <td colspan="2">Send Spam Call To:</td>
      </tr>
      <tr class="incerceptorCell">
        <td colspan="2">{$interceptor_select}</td>
      </tr>
    </table>
    <p><a target="_blank" href="modules/superfecta/disclaimer.html">(License Terms)&nbsp; </a><input type="submit" value="Agree and Save" name="Save"></p>
    <p style="font-size:12px;">(* By clicking on either the &quot;Agree and Save&quot;<br>button, or the &quot;Debug&quot; button on this form<br>you are agreeing to the Caller ID Superfecta<br>Licensing Terms.)</p>
  </form>
</div>
