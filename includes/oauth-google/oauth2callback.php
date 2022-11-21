<?php
    $code='';
    foreach($_GET as $key => $value) {
		if ($key != 'code')
			continue;
		$code=$value;
		break;
    }
    echo "<ol>";
    echo "<li>".  sprintf(_("Copy the code: <b>%s</b>"), $code)  ."</li>";
    echo "<li>".  _("Go back to configure Google in FreePBX.") ."</li>";
    echo '<ol type="a">';
    echo "<li>".  _("Paste this code into the <b>Google Code</b> box replacing the <b>XXX</b>.") . "</li>";
    echo "<li>".  _("Hit <b>Save</b>.")  . "</li>";
    echo "<li>" . _("Now run debug again and it should work.") . "</li>";
    echo "</ol>";
    echo "<li>". _("You can close this window once done.") . "</li>";
    echo "</ol>";
?>
