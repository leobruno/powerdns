<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://rejo.zenger.nl/poweradmin> for more details.
 *
 *  Copyright 2007-2009  Rejo Zenger <rejo@zenger.nl>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once("inc/toolkit.inc.php");


// Get a list of all available zone templates.

function get_list_zone_templ($userid) {
	global $db_mdb2;

	$query = "SELECT * FROM zone_templ "
					. "WHERE owner = '" . $userid . "' "
					. "ORDER BY name";
	$result = $db_mdb2->query($query);
	if (isError($result)) { error("Not all tables available in database, please make sure all upgrade/install proceedures were followed"); return false; }

	$zone_templ_list = array();
	while ($zone_templ = $result->fetchRow()) {
		$zone_templ_list[] = array(
		"id"    =>      $zone_templ['id'],
		"name"  =>      $zone_templ['name'],
		"descr" =>      $zone_templ['descr']
		);
	}
	return $zone_templ_list;
}

// Add a zone template.

function add_zone_templ($details, $userid) {
	global $db_mdb2;

	if (!(verify_permission('zone_master_add'))) {
		error(ERR_PERM_ADD_ZONE_TEMPL);
		return false;
	} else {
		$query = "INSERT INTO zone_templ (name, descr, owner)
			VALUES ("
				. $db_mdb2->quote($details['templ_name'], 'text') . ", "
				. $db_mdb2->quote($details['templ_descr'], 'text') . ", "
				. $db_mdb2->quote($userid, 'integer') . ")";

		$result = $db_mdb2->query($query);
		if (isError($result)) { error($result->getMessage()); return false; }

		return true;
	}
}

// Get name and description of template based on template ID.

function get_zone_templ_details($zone_templ_id) {
	global $db_mdb2;

	$query = "SELECT *"
		. " FROM zone_templ"
		. " WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');

	$result = $db_mdb2->query($query);
	if (isError($result)) { error($result->getMessage()); return false; }

	$details = $result->fetchRow();
	return $details;
}

// Delete a zone template

function delete_zone_templ($zone_templ_id) {
	global $db_mdb2;

	if (!(verify_permission('zone_master_add'))) {
		error(ERR_PERM_DEL_ZONE_TEMPL);
		return false;
	} else {
		$query = "DELETE FROM zone_templ"
			. " WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');
		$result = $db_mdb2->query($query);
		if (isError($result)) { error($result->getMessage()); return false; }

		return true;
	}
}

// Delete all zone templates for specific user

function delete_zone_templ_userid($userid) {
	global $db_mdb2;

	if (!(verify_permission('zone_master_add'))) {
		error(ERR_PERM_DEL_ZONE_TEMPL);
		return false;
	} else {
		$query = "DELETE FROM zone_templ"
			. " WHERE owner = " . $db_mdb2->quote($userid, 'integer');
		$result = $db_mdb2->query($query);
		if (isError($result)) { error($result->getMessage()); return false; }

		return true;
	}
}

// Count zone template records

function count_zone_templ_records($zone_templ_id) {
        global $db_mdb2;
        $query = "SELECT COUNT(id) FROM zone_templ_records WHERE zone_templ_id = ".$db_mdb2->quote($zone_templ_id, 'integer');
        $record_count = $db_mdb2->queryOne($query);
	if (isError($record_count)) { error($record_count->getMessage()); return false; }
        return $record_count;
}

// Check if zone template exist

function zone_templ_id_exists($zone_templ_id) {
        global $db_mdb2;
        $query = "SELECT COUNT(id) FROM zone_templ WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');
        $count = $db_mdb2->queryOne($query);
        if (isError($count)) { error($result->getMessage()); return false; }
        return $count;
}

/*
 * Get a zone template record from an id.
 * Retrieve all fields of the record and send it back to the function caller.
 * return values: the array with information, or -1 is nothing is found.
 */
function get_zone_templ_record_from_id($id)
{
	global $db_mdb2;
	if (is_numeric($id)) {
		$result = $db_mdb2->query("SELECT id, zone_templ_id, name, type, content, ttl, prio FROM zone_templ_records WHERE id=".$db_mdb2->quote($id, 'integer'));
		if($result->numRows() == 0) {
			return -1;
		} elseif ($result->numRows() == 1) {
			$r = $result->fetchRow();
			$ret = array(
				"id"            =>      $r["id"],
				"zone_templ_id" =>      $r["zone_templ_id"],
				"name"          =>      $r["name"],
				"type"          =>      $r["type"],
				"content"       =>      $r["content"],
				"ttl"           =>      $r["ttl"],
				"prio"          =>      $r["prio"],
				);
			return $ret;
		} else {
			error(sprintf(ERR_INV_ARGC, "get_zone_templ_record_from_id", "More than one row returned! This is bad!"));
		}
	} else {
		error(sprintf(ERR_INV_ARG, "get_zone_templ_record_from_id"));
	}
}

/*
 * Get all zone template records from a zone template id.
 * Retrieve all fields of the records and send it back to the function caller.
 * return values: the array with information, or -1 is nothing is found.
 */
function get_zone_templ_records($id,$rowstart=0,$rowamount=999999,$sortby='name') {
	global $db_mdb2;
	if (is_numeric($id)) {
		$db_mdb2->setLimit($rowamount, $rowstart);
		$result = $db_mdb2->query("SELECT id FROM zone_templ_records WHERE zone_templ_id=".$db_mdb2->quote($id, 'integer')." ORDER BY ".$sortby);
		$ret = array();
		if($result->numRows() == 0) {
			return -1;
		} else {
			$ret[] = array();
			$retcount = 0;
			while($r = $result->fetchRow()) {
				// Call get_record_from_id for each row.
				$ret[$retcount] = get_zone_templ_record_from_id($r["id"]);
				$retcount++;
			}
			return $ret;
		}
	} else {
		error(sprintf(ERR_INV_ARG, "get_zone_templ_records"));
	}
}

/*
 * Adds a record for a zone template.
 * This function validates it if correct it inserts it into the database.
 * return values: true if succesful.
 */
function add_zone_templ_record($zone_templ_id, $name, $type, $content, $ttl, $prio) {
	global $db_mdb2;

	if (!(verify_permission('zone_master_add'))) {
		error(ERR_PERM_ADD_RECORD);
		return false;
	} else {
		if($type == "SPF"){
			$content = $db_mdb2->quote(stripslashes('\"'.$content.'\"'), 'text');
		} else {
			$content = $db_mdb2->quote($content, 'text');
		}
		$query = "INSERT INTO zone_templ_records (zone_templ_id, name, type, content, ttl, prio) VALUES ("
					. $db_mdb2->quote($zone_templ_id, 'integer') . ","
					. $db_mdb2->quote($name, 'text') . "," 
					. $db_mdb2->quote($type, 'text') . ","
					. $content . ","
					. $db_mdb2->quote($ttl, 'integer') . ","
					. $db_mdb2->quote($prio, 'integer') . ")";
		$result = $db_mdb2->query($query);
		if (isError($result)) { error($result->getMessage()); return false; }
		return true;
	}
}

/*
 * Edit a record for a zone template.
 * This function validates it if correct it inserts it into the database.
 * return values: true if succesful.
 */
function edit_zone_templ_record($record) {
	global $db_mdb2;

	if (!(verify_permission('zone_master_add'))) {
		error(ERR_PERM_EDIT_RECORD);
		return false;
	} else {
		if($record['type'] == "SPF"){
			$content = $db_mdb2->quote(stripslashes('\"'.$record['content'].'\"'), 'text');
		}else{
			$content = $db_mdb2->quote($record['content'], 'text');
		}
		$query = "UPDATE zone_templ_records 
			SET name=".$db_mdb2->quote($record['name'], 'text').", 
			type=".$db_mdb2->quote($record['type'], 'text').", 
			content=".$content.",
			ttl=".$db_mdb2->quote($record['ttl'], 'integer').",
			prio=".$db_mdb2->quote($record['prio'], 'integer')."
			WHERE id=".$db_mdb2->quote($record['rid'], 'integer');
		$result = $db_mdb2->query($query);
		if (isError($result)) { error($result->getMessage()); return false; }
		return true;
	}
}

/*
 * Delete a record for a zone template by a given id.
 * return values: true if succesful.
 */
function delete_zone_templ_record($rid)
{
	global $db_mdb2;

	if (!(verify_permission('zone_master_add'))) {
		error(ERR_PERM_DEL_RECORD);
		return false;
	} else {
		$query = "DELETE FROM zone_templ_records WHERE id = " . $db_mdb2->quote($rid, 'integer');
		$result = $db_mdb2->query($query);
		if (isError($result)) { error($result->getMessage()); return false; }
		return true;
	}
}

/*
 * Check if the session user is the owner for the zone template.
 * return values: true if succesful.
 */
function get_zone_templ_is_owner($zone_templ_id, $userid) {
	global $db_mdb2;

	$query = "SELECT owner FROM zone_templ WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');
	$result = $db_mdb2->queryOne($query);
	if (isError($result)) { error($result->getMessage()); return false; }

	if ($result == $userid) {
		return true;
	} else {
		return false;
	}
}

?>
