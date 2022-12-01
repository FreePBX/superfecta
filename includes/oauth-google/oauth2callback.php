<?php
    $code='';
    foreach($_GET as $key => $value) {
		if ($key != 'code')
			continue;
		$code=$value;
		break;
    }

    echo sprintf('
<table class="table table-striped">
  <thead>
    <tr>
      <th data-field="code">%s</th>
      <th data-field="actions">%s</th>
    </tr>
  </thead>
  <tbody>
      <tr>
        <td>
          <b>%s</b>
        </td>
        <td>
          <button class="fa fa-files-o" onclick=copyCode()> - %s</button>
        </td>
      </tr>
  </tbody>
</table>

<li>%s</li>
<li>%s"</li>
<ol type="a">
    <li>%s</li>
    <li>%s</li>
    <li>%s</li>
</ol>
<li><button onclick=closeWindow()><b>%s</b></button></li>

<script type="application/javascript">
    function copyCode() {
      var code = "%s";

      // Copy the code to clipboard
      navigator.clipboard.writeText(code);

      // Display Alert window with Code copy confirmation message
      alert("%s" + code);
    }
    function closeWindow(){
        window.close() ;
    }
</script>
        ',
        _('Google Code'),
        _('Actions'),
        $code,
        _('Copy'),
        _('Copy the Google Code.'),
        _('Go back to configure Google Contacts Caller-ID Lookup in FreePBX.'),
        _('Paste this Google Code into the <b>Google Code</b> box replacing the <b>XXX</b>.'),
        _('Hit <b>Save</b>.'),
        _('Now run debug again and it should work.'),
        _('Close'),
        $code,
        _('Google Code copied: '));
?>
