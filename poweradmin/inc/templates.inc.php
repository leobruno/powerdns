<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <http://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2009  Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2014  Poweradmin Development Team
 *      <http://www.poweradmin.org/credits.html>
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

/**
 *  Template functions
 *
 * @package Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2014 Poweradmin Development Team
 * @license     http://opensource.org/licenses/GPL-3.0 GPL
 */

/** Get a list of all available zone templates
 *
 * @param int $userid User ID
 *
 * @return mixed[] array of zone templates [id,name,descr]
 */
function get_list_zone_templ($userid) {
    global $db_mdb2;

    $query = "SELECT * FROM zone_templ "
            . "WHERE owner = '" . $userid . "' "
            . "ORDER BY name";
    $result = $db_mdb2->query($query);
    if (isError($result)) {
        error("Not all tables available in database, please make sure all upgrade/install proceedures were followed");
        return false;
    }

    $zone_templ_list = array();
    while ($zone_templ = $result->fetchRow()) {
        $zone_templ_list[] = array(
            "id" => $zone_templ['id'],
            "name" => $zone_templ['name'],
            "descr" => $zone_templ['descr']
        );
    }
    return $zone_templ_list;
}

/** Add a zone template
 *
 * @param mixed[] $details zone template details
 * @param $userid User ID that owns template
 *
 * @return boolean true on success, false otherwise
 */
function add_zone_templ($details, $userid) {
    global $db_mdb2;

    $zone_name_exists = zone_templ_name_exists($details['templ_name']);
    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_ADD_ZONE_TEMPL);
        return false;
    } elseif ($zone_name_exists != '0') {
        error(ERR_ZONE_TEMPL_EXIST);
    } else {
        $query = "INSERT INTO zone_templ (name, descr, owner)
			VALUES ("
                . $db_mdb2->quote($details['templ_name'], 'text') . ", "
                . $db_mdb2->quote($details['templ_descr'], 'text') . ", "
                . $db_mdb2->quote($userid, 'integer') . ")";

        $result = $db_mdb2->query($query);
        if (isError($result)) {
            error($result->getMessage());
            return false;
        }

        return true;
    }
}

/** Get name and description of template based on template ID
 *
 * @param int $zone_templ_id Zone template ID
 *
 * @return mixed[] zone template details
 */
function get_zone_templ_details($zone_templ_id) {
    global $db_mdb2;

    $query = "SELECT *"
            . " FROM zone_templ"
            . " WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');

    $result = $db_mdb2->query($query);
    if (isError($result)) {
        error($result->getMessage());
        return false;
    }

    $details = $result->fetchRow();
    return $details;
}

/** Delete a zone template
 *
 * @param int $zone_templ_id Zone template ID
 *
 * @return boolean true on success, false otherwise
 */
function delete_zone_templ($zone_templ_id) {
    global $db_mdb2;

    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_DEL_ZONE_TEMPL);
        return false;
    } else {
        // Delete the zone template
        $query = "DELETE FROM zone_templ"
                . " WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');
        $result = $db_mdb2->query($query);
        if (isError($result)) {
            error($result->getMessage());
            return false;
        }

        // Delete the zone template records
        $query = "DELETE FROM zone_templ_records"
                . " WHERE zone_templ_id = " . $db_mdb2->quote($zone_templ_id, 'integer');
        $result = $db_mdb2->query($query);
        if (isError($result)) {
            error($result->getMessage());
            return false;
        }

        // Delete references to zone template
        $query = "DELETE FROM records_zone_templ"
                . " WHERE zone_templ_id = " . $db_mdb2->quote($zone_templ_id, 'integer');
        $result = $db_mdb2->query($query);
        if (isError($result)) {
            error($result->getMessage());
            return false;
        }

        return true;
    }
}

/** Delete all zone templates for specific user
 *
 * @param $userid User ID
 *
 * @return boolean true on success, false otherwise
 */
function delete_zone_templ_userid($userid) {
    global $db_mdb2;

    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_DEL_ZONE_TEMPL);
        return false;
    } else {
        $query = "DELETE FROM zone_templ"
                . " WHERE owner = " . $db_mdb2->quote($userid, 'integer');
        $result = $db_mdb2->query($query);
        if (isError($result)) {
            error($result->getMessage());
            return false;
        }

        return true;
    }
}

/** Count zone template records
 *
 * @param int $zone_templ_id Zone template ID
 *
 * @return boolean true on success, false otherwise
 */
function count_zone_templ_records($zone_templ_id) {
    global $db_mdb2;
    $query = "SELECT COUNT(id) FROM zone_templ_records WHERE zone_templ_id = " . $db_mdb2->quote($zone_templ_id, 'integer');
    $record_count = $db_mdb2->queryOne($query);
    if (isError($record_count)) {
        error($record_count->getMessage());
        return false;
    }
    return $record_count;
}

/** Check if zone template exist
 *
 * @param int $zone_templ_id Zone template ID
 *
 * @return boolean true on success, false otherwise
 */
function zone_templ_id_exists($zone_templ_id) {
    global $db_mdb2;
    $query = "SELECT COUNT(id) FROM zone_templ WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');
    $count = $db_mdb2->queryOne($query);
    if (isError($count)) {
        error($count->getMessage());
        return false;
    }
    return $count;
}

/** Get a zone template record from an id
 *
 * Retrieve all fields of the record and send it back to the function caller.
 *
 * @param int $id zone template record id
 *
 * @return mixed[] zone template record
 * [id,zone_templ_id,name,type,content,ttl,prio] or -1 if nothing is found
 */
function get_zone_templ_record_from_id($id) {
    global $db_mdb2;
    if (is_numeric($id)) {
        $result = $db_mdb2->queryRow("SELECT id, zone_templ_id, name, type, content, ttl, prio FROM zone_templ_records WHERE id=" . $db_mdb2->quote($id, 'integer'));
        if ($result) {
            $ret = array(
                "id" => $result["id"],
                "zone_templ_id" => $result["zone_templ_id"],
                "name" => $result["name"],
                "type" => $result["type"],
                "content" => $result["content"],
                "ttl" => $result["ttl"],
                "prio" => $result["prio"],
            );
            return $ret;
        } else {
            return -1;
        }
    } else {
        error(sprintf(ERR_INV_ARG, "get_zone_templ_record_from_id"));
    }
}

/** Get all zone template records from a zone template id
 *
 * Retrieve all fields of the records and send it back to the function caller.
 *
 * @param int $id zone template ID
 * @param int $rowstart Starting row (default=0)
 * @param int $rowamount Number of rows per query (default=999999)
 * @param string $sortby Column to sort by (default='name')
 *
 * @return mixed[] zone template records numerically indexed
 * [id,zone_templd_id,name,type,content,ttl,pro] or -1 if nothing is found
 */
function get_zone_templ_records($id, $rowstart = 0, $rowamount = 999999, $sortby = 'name') {
    global $db_mdb2;

    if (is_numeric($id)) {
        $db_mdb2->setLimit($rowamount, $rowstart);
        $result = $db_mdb2->query("SELECT id FROM zone_templ_records WHERE zone_templ_id=" . $db_mdb2->quote($id, 'integer') . " ORDER BY " . $sortby);
        $ret[] = array();
        $retcount = 0;
        while ($r = $result->fetchRow()) {
            // Call get_record_from_id for each row.
            $ret[$retcount] = get_zone_templ_record_from_id($r["id"]);
            $retcount++;
        }
        return ($retcount > 0 ? $ret : -1);
    } else {
        error(sprintf(ERR_INV_ARG, "get_zone_templ_records"));
    }
}

/** Add a record for a zone template
 *
 * This function validates and if correct it inserts it into the database.
 * TODO: actual validation?
 *
 * @param int $zone_templ_id zone template ID
 * @param string $name name part of record
 * @param string $type record type
 * @param string $content record content
 * @param int $ttl TTL
 * @param int $prio Priority
 *
 * @return boolean true if succesful, false otherwise
 */
function add_zone_templ_record($zone_templ_id, $name, $type, $content, $ttl, $prio) {
    global $db_mdb2;

    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_ADD_RECORD);
        return false;
    } else {
        if ($content == '') {
            error(ERR_DNS_CONTENT);
            return false;
        }

        if ($name != '') {
            if ($type == "SPF") {
                $content = $db_mdb2->quote(stripslashes('\"' . $content . '\"'), 'text');
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
            if (isError($result)) {
                error($result->getMessage());
                return false;
            }
            return true;
        } else {
            error(ERR_DNS_HOSTNAME);
            return false;
        }
    }
}

/** Modify zone template reocrd
 *
 * Edit a record for a zone template.
 * This function validates it if correct it inserts it into the database.
 *
 * @param mixed[] $record zone record array
 *
 * @return boolean true on success, false otherwise
 */
function edit_zone_templ_record($record) {
    global $db_mdb2;

    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_EDIT_RECORD);
        return false;
    } else {
        if ("" != $record['name']) {
            if ($record['type'] == "SPF") {
                $content = $db_mdb2->quote(stripslashes('\"' . $record['content'] . '\"'), 'text');
            } else {
                $content = $db_mdb2->quote($record['content'], 'text');
            }
            $query = "UPDATE zone_templ_records
                                SET name=" . $db_mdb2->quote($record['name'], 'text') . ",
                                type=" . $db_mdb2->quote($record['type'], 'text') . ",
                                content=" . $content . ",
                                ttl=" . $db_mdb2->quote($record['ttl'], 'integer') . ",
                                prio=" . $db_mdb2->quote(isset($record['prio']) ? $record['prio'] : 0, 'integer') . "
                                WHERE id=" . $db_mdb2->quote($record['rid'], 'integer');
            $result = $db_mdb2->query($query);
            if (isError($result)) {
                error($result->getMessage());
                return false;
            }
            return true;
        } else {
            error(ERR_DNS_HOSTNAME);
            return false;
        }
    }
}

/** Delete a record for a zone template by a given id
 *
 * @param int $rid template record id
 *
 * @return boolean true on success, false otherwise
 */
function delete_zone_templ_record($rid) {
    global $db_mdb2;

    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_DEL_RECORD);
        return false;
    } else {
        $query = "DELETE FROM zone_templ_records WHERE id = " . $db_mdb2->quote($rid, 'integer');
        $result = $db_mdb2->query($query);
        if (isError($result)) {
            error($result->getMessage());
            return false;
        }
        return true;
    }
}

/** Check if the session user is the owner for the zone template
 *
 * @param int $zone_templ_id zone template id
 * @param int $userid user id
 *
 * @return boolean true on success, false otherwise
 */
function get_zone_templ_is_owner($zone_templ_id, $userid) {
    global $db_mdb2;

    $query = "SELECT owner FROM zone_templ WHERE id = " . $db_mdb2->quote($zone_templ_id, 'integer');
    $result = $db_mdb2->queryOne($query);
    if (isError($result)) {
        error($result->getMessage());
        return false;
    }

    if ($result == $userid) {
        return true;
    } else {
        return false;
    }
}

/** Add a zone template from zone / another template
 *
 * @param string $template_name template name
 * @param string $description description
 * @param int $userid user id
 * @param mixed[] $records array of zone records
 * @param string $domain domain to substitute with '[ZONE]' (optional) [default=null]
 *
 * @return boolean true on success, false otherwise
 */
function add_zone_templ_save_as($template_name, $description, $userid, $records, $domain = null) {
    global $db_mdb2;
    global $db_layer;
    global $db_type;

    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_ADD_ZONE_TEMPL);
        return false;
    } else {
        $result = $db_mdb2->beginTransaction();

        $query = "INSERT INTO zone_templ (name, descr, owner)
			VALUES ("
                . $db_mdb2->quote($template_name, 'text') . ", "
                . $db_mdb2->quote($description, 'text') . ", "
                . $db_mdb2->quote($userid, 'integer') . ")";

        $result = $db_mdb2->exec($query);

        if ($db_layer == 'MDB2' && ($db_type == 'mysql' || $db_type == 'pgsql')) {
            $zone_templ_id = $db_mdb2->lastInsertId('zone_templ', 'id');
        } else if ($db_layer == 'PDO' && $db_type == 'pgsql') {
            $zone_templ_id = $db_mdb2->lastInsertId('zone_templ_id_seq');
        } else {
            $zone_templ_id = $db_mdb2->lastInsertId();
        }

        $owner = get_zone_templ_is_owner($zone_templ_id, $_SESSION['userid']);

        foreach ($records as $record) {
            if ($record['type'] == "SPF") {
                $content = $db_mdb2->quote(stripslashes('\"' . $record['content'] . '\"'), 'text');
            } else {
                $content = $db_mdb2->quote($record['content'], 'text');
            }

            $name = $domain ? preg_replace('/' . $domain . '/', '[ZONE]', $record['name']) : $record['name'];
            $content = $domain ? preg_replace('/' . $domain . '/', '[ZONE]', $content) : $content;

            $query2 = "INSERT INTO zone_templ_records (zone_templ_id, name, type, content, ttl, prio) VALUES ("
                    . $db_mdb2->quote($zone_templ_id, 'integer') . ","
                    . $db_mdb2->quote($name, 'text') . ","
                    . $db_mdb2->quote($record['type'], 'text') . ","
                    . $content . ","
                    . $db_mdb2->quote($record['ttl'], 'integer') . ","
                    . $db_mdb2->quote(isset($record['prio']) ? $record['prio'] : 0, 'integer') . ")";
            $result = $db_mdb2->exec($query2);
        }

        if (isError($result)) {
            $result = $db_mdb2->rollback();
        } else {
            $result = $db_mdb2->commit();
        }
    }
    return true;
}

/** Get list of all zones using template
 *
 * @param int $zone_templ_id zone template id
 * @param int $userid user id
 *
 * @return mixed[] array of zones [id,name,type,count_records]
 */
function get_list_zone_use_templ($zone_templ_id, $userid) {
    global $db_mdb2;

    if (do_hook('verify_permission' , 'zone_content_edit_others' )) {
        $perm_edit = "all";
    } elseif (do_hook('verify_permission' , 'zone_content_edit_own' )) {
        $perm_edit = "own";
    } else {
        $perm_edit = "none";
    }

    $sql_add = '';
    if ($perm_edit != "all") {
        $sql_add = " AND zones.domain_id = domains.id
				AND zones.owner = " . $db_mdb2->quote($userid, 'integer');
    }

    $query = "SELECT domains.id,
			domains.name,
			domains.type,
			Record_Count.count_records
			FROM domains
			LEFT JOIN zones ON domains.id=zones.domain_id
			LEFT JOIN (
				SELECT COUNT(domain_id) AS count_records, domain_id FROM records GROUP BY domain_id
			) Record_Count ON Record_Count.domain_id=domains.id
			WHERE 1=1" . $sql_add . "
                        AND zone_templ_id = " . $db_mdb2->quote($zone_templ_id, 'integer') . "
			GROUP BY domains.name, domains.id, domains.type, Record_Count.count_records";

    $result = $db_mdb2->query($query);
    if (isError($result)) {
        error("Not all tables available in database, please make sure all upgrade/install proceedures were followed");
        return false;
    }

    $zone_list = array();
    while ($zone = $result->fetchRow()) {
        $zone_list[] = array(
            "id" => $zone['id'],
            "name" => $zone['name'],
            "type" => $zone['type'],
            "count_records" => $zone['count_records']
        );
    }
    return $zone_list;
}

/** Modify zone template
 *
 * @param mixed[] $details array of new zone template details
 * @param int $zone_templ_id zone template id
 *
 * @return boolean true on success, false otherwise
 */
function edit_zone_templ($details, $zone_templ_id) {
    global $db_mdb2;
    $zone_name_exists = zone_templ_name_exists($details['templ_name'], $zone_templ_id);
    if (!(do_hook('verify_permission' , 'zone_master_add' ))) {
        error(ERR_PERM_ADD_ZONE_TEMPL);
        return false;
    } elseif ($zone_name_exists != '0') {
        error(ERR_ZONE_TEMPL_EXIST);
        return false;
    } else {
        $query = "UPDATE zone_templ
			SET name=" . $db_mdb2->quote($details['templ_name'], 'text') . ",
			descr=" . $db_mdb2->quote($details['templ_descr'], 'text') . "
			WHERE id=" . $db_mdb2->quote($zone_templ_id, 'integer');

        $result = $db_mdb2->query($query);
        if (isError($result)) {
            error($result->getMessage());
            return false;
        }

        return true;
    }
}

/** Check if zone template name exists
 *
 * @param string $zone_templ_name zone template name
 * @param int $zone_templ_id zone template id (optional) [default=null]
 *
 * @return int number of matching templates
 */
function zone_templ_name_exists($zone_templ_name, $zone_templ_id = null) {
    global $db_mdb2;

    $sql_add = '';
    if ($zone_templ_id) {
        $sql_add = " AND id != " . $db_mdb2->quote($zone_templ_id, 'integer');
    }

    $query = "SELECT COUNT(id) FROM zone_templ WHERE name = " . $db_mdb2->quote($zone_templ_name, 'text') . "" . $sql_add;
    $count = $db_mdb2->queryOne($query);
    if (isError($count)) {
        error($count->getMessage());
        return false;
    }

    return $count;
}

/** Parse string and substitute domain and serial
 *
 * @param string $val string to parse containing tokens '[ZONE]' and '[SERIAL]'
 * @param string $domain domain to subsitute for '[ZONE]'
 *
 * @return string interpolated/parsed string
 */
function parse_template_value($val, $domain) {
    $serial = date("Ymd");
    $serial .= "00";

    $val = str_replace('[ZONE]', $domain, $val);
    $val = str_replace('[SERIAL]', $serial, $val);
    return $val;
}

/** Add relation between zone record and template
 *
 * @param type $db_mdb2 DB link
 * @param type $domain_id Domain id
 * @param type $record_id Record id
 * @param type $zone_templ_id Zone template id
 */
function add_record_relation_to_templ($db, $domain_id, $record_id, $zone_templ_id) {
    $query = "INSERT INTO records_zone_templ (domain_id, record_id, zone_templ_id) VALUES ("
            . $db_mdb2->quote($domain_id, 'integer') . ","
            . $db_mdb2->quote($record_id, 'integer') . ","
            . $db_mdb2->quote($zone_templ_id, 'integer') . ")";
    $db_mdb2->query($query);
}

/** Check if given relation exists
 *
 * @param type $db
 * @param type $domain_id
 * @param type $record_id
 * @param type $zone_templ_id
 * @return boolean true on success, false on failure
 */
function record_relation_to_templ_exists($db, $domain_id, $record_id, $zone_templ_id) {
    $query = "SELECT COUNT(*) FROM records_zone_templ WHERE domain_id = " . $db_mdb2->quote($domain_id, 'integer') .
            " AND record_id = " . $db_mdb2->quote($record_id, 'integer') . " AND zone_templ_id = " . $db_mdb2->quote($zone_templ_id, 'integer');
    $count = $db_mdb2->queryOne($query);
    if ($count == 0) {
        return false;
    }

    return true;
}
