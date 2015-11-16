<?php

function superfecta_hook_core($viewing_itemid, $target_menuid) {
	global $db;
	$sql = "SELECT * FROM superfectaconfig WHERE field ='order' ORDER BY value ASC";
	$schemes = $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
	$html = '';
	if ($target_menuid == 'did') {
		if (superfecta_did_get($viewing_itemid)) {
			$enabled = true;
		} else {
			$enabled = false;
		}
		$html.= '
		<!--Enable CID Lookup-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="enable_superfecta">'. _("Enable Superfecta Lookup") .'</label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_superfecta"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="enable_superfecta" id="enable_superfecta_yes" value="yes" '. ($enabled?"CHECKED":"").'>
								<label for="enable_superfecta_yes">'. _("Yes").'</label>
								<input type="radio" name="enable_superfecta" id="enable_superfecta_no" value="no" '.($enabled?"":"CHECKED").'>
								<label for="enable_superfecta_no">'. _("No").'</label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="enable_superfecta-help" class="help-block fpbx-help-block">'. _("Sources can be added/removed in CID Superfecta section").'</span>
				</div>
			</div>
		</div>
		<!--END Enable CID Lookup-->
		';
		//$html.='<tr><td colspan="2"><h5>' . _("Superfecta CID Lookup") . '<hr></h5></td></tr>';
		$info = explode("/", $viewing_itemid);
		$sql = "SELECT scheme FROM superfecta_to_incoming WHERE extension = ?";
		$q = $db->prepare($sql);
		$q->execute(array($info[0]));
		$scheme = $q->fetchColumn();

		$first = '<option value="ALL|ALL" {$selected}>'._('ALL').'</option>';
		$has_selected = FALSE;
		$last = '';
		foreach ($schemes as $data) {
			if ($scheme == $data['source']) {
				$selected = 'selected';
				$has_selected = TRUE;
			} else {
				$selected = '';
			}
			$name = explode("_", $data['source']);
			$last .= '<option value="' . $data['source'] . '" ' . $selected . '>' . $name[1] . '</option>';
		}
		$selected = ($has_selected) ? 'selected' : '';
		$first = str_replace('{$selected}', $selected, $first);
		$opts = $first . $last;
		$html .= '
		<!--Scheme-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="superfecta_scheme">'. _("Superfecta Scheme") .'</label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="superfecta_scheme"></i>
							</div>
							<div class="col-md-9">
								<select class="form-control" id="superfecta_scheme" name="superfecta_scheme">
									'.$opts.'
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="superfecta_scheme-help" class="help-block fpbx-help-block">'. _("Setup Schemes in CID Superfecta section").'</span>
				</div>
			</div>
		</div>
		<!--END Scheme-->
		';
	}
	return $html;
}

function superfecta_hookProcess_core($viewing_itemid, $request) {
	$db = \FreePBX::Database();
	// TODO: move sql to functions superfecta_did_(add, del, edit)
	if (!isset($request['action'])) {
		return;
	}
	$result = '';
	switch ($request['action']) {
		case 'addIncoming':
		if ($request['enable_superfecta'] == 'yes') {
			$sql = "REPLACE INTO superfecta_to_incoming (extension, cidnum, scheme) values (:extension, :cidnum,:superfecta_scheme)";
			$q = $db->prepare($sql);
			$q->bindParam(':extension', $request['extension'], \PDO::PARAM_STR);
			$q->bindParam(':cidnum', $request['cidnum'], \PDO::PARAM_STR);
			$q->bindParam(':superfecta_scheme', $request['superfecta_scheme'], \PDO::PARAM_STR);
			$q->execute();
			$result = $db->lastInsertId();
		}
		break;
		case 'delIncoming':
		$extarray = explode('/', $request['extdisplay'], 2);
		if (count($extarray) == 2) {
			$sql = "DELETE FROM superfecta_to_incoming WHERE extension = :extension AND cidnum = :cidnum";
			$q = $db->prepare($sql);
			$q->bindParam(':extension', $extarray[0], \PDO::PARAM_STR);
			$q->bindParam(':cidnum', $extarray[1], \PDO::PARAM_STR);
			$q->execute();
			$result = $db->lastInsertId();
		}
		break;
		case 'edtIncoming': // deleting and adding as in core module
		$extarray = explode('/', $request['extdisplay'], 2);
		if (count($extarray) == 2) {
			$sql = "DELETE FROM superfecta_to_incoming WHERE extension = :extension AND cidnum = :cidnum";
			$q = $db->prepare($sql);
			$q->bindParam(':extension', $extarray[0], \PDO::PARAM_STR);
			$q->bindParam(':cidnum', $extarray[1], \PDO::PARAM_STR);
			$q->execute();
		}
		if ($request['enable_superfecta'] == 'yes') {
			$sql = "REPLACE INTO superfecta_to_incoming (extension, cidnum, scheme) values (:extension, :cidnum,:superfecta_scheme)";
			$q = $db->prepare($sql);
			$q->bindParam(':extension', $request['extension'], \PDO::PARAM_STR);
			$q->bindParam(':cidnum', $request['cidnum'], \PDO::PARAM_STR);
			$q->bindParam(':superfecta_scheme', $request['superfecta_scheme'], \PDO::PARAM_STR);
			$q->execute();
			$result = $db->lastInsertId();
		}
		break;
	}
	return $result;
}

function superfecta_hookGet_config($engine) {
	// TODO: integrating with direct extension <-> DID association
	// TODO: add option to avoid callerid lookup if the telco already supply a callerid name (GosubIf)
	global $ext;  // is this the best way to pass this?

	switch ($engine) {
		case "asterisk":
		$pairing = superfecta_did_list();
		if (is_array($pairing)) {
			foreach ($pairing as $item) {
				if ($item['superfecta_to_incoming_id'] != 0) {
					// Code from modules/core/functions.inc.php core_get_config inbound routes
					$exten = trim($item['extension']);
					$cidnum = trim($item['cidnum']);
					$scheme = trim($item['scheme']);
					if ($scheme == '') {
						$scheme = 'base_Default';
					}

					if ($cidnum != '' && $exten == '') {
						$exten = 's';
						$pricid = ($item['pricid']) ? true : false;
					} else if (($cidnum != '' && $exten != '') || ($cidnum == '' && $exten == '')) {
						$pricid = true;
					} else {
						$pricid = false;
					}
					$context = ($pricid) ? "ext-did-0001" : "ext-did-0002";

					$exten = (empty($exten) ? "s" : $exten);
					$exten = $exten . (empty($cidnum) ? "" : "/" . $cidnum); //if a CID num is defined, add it

					//https://github.com/POSSA/Caller-ID-Superfecta/issues/144
					$ext->splice($context, $exten, 'did-cid-hook', new ext_setvar('CIDSFSCHEME', base64_encode($scheme)));
					$ext->splice($context, $exten, 'did-cid-hook', new ext_agi(dirname(__FILE__) . '/agi/superfecta.agi'));
					$ext->splice($context, $exten, 'did-cid-hook', new ext_setvar('CALLERID(name)', '${lookupcid}'));
				}
			}
		}
		break;
	}
}

function superfecta_did_get($did) {
	$extarray = explode('/', $did, 2);
	if (count($extarray) == 2) {
		$sql = "SELECT * FROM superfecta_to_incoming WHERE extension = " . q($extarray[0]) . " AND cidnum = " . q($extarray[1]);
		$result = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
		if (is_array($result) && count($result)) {
			return true;
		}
	}
	return false;
}

function superfecta_did_list($id=false) {
	$sql = "
	SELECT superfecta_to_incoming_id, a.extension extension, a.cidnum cidnum, pricid, scheme FROM superfecta_to_incoming a
	INNER JOIN incoming b
	ON a.extension = b.extension AND a.cidnum = b.cidnum
	";
	if ($id !== false && ctype_digit($id)) {
		$sql .= " WHERE superfecta_to_incoming_id = '" . q($id) . "'";
	}

	$results = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
	return is_array($results) ? $results : array();
}
