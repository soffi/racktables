<?php
/*
*
*  This file is a library of database access functions for RackTables.
*
*/

function isInnoDBSupported ($dbh = FALSE) {
	global $dbxlink;

	// sometimes db handle isn't available globally, must be passed
	if (!$dbxlink)
		$dbxlink = $dbh;

	// create a temp table
	$dbxlink->query("CREATE TABLE `innodb_test` (`id` int) ENGINE=InnoDB");
	$row = $dbxlink->query("SHOW TABLE STATUS LIKE 'innodb_test'")->fetch(PDO::FETCH_ASSOC);
	$dbxlink->query("DROP TABLE `innodb_test`");
	if ($row['Engine'] == 'InnoDB')
		return TRUE;

	return FALSE;
}

function escapeString ($value, $do_db_escape = TRUE)
{
	$ret = htmlspecialchars ($value, ENT_QUOTES, 'UTF-8');
	if ($do_db_escape)
	{
		global $dbxlink;
		$ret = substr ($dbxlink->quote ($ret), 1, -1);
	}
	return $ret;
}

function getRackspace ($tagfilter = array(), $tfmode = 'any')
{
	$whereclause = getWhereClause ($tagfilter);
	$query =
		"select RackRow.id as row_id, RackRow.name as row_name, count(Rack.id) as count " .
		"from RackRow left join Rack on Rack.row_id = RackRow.id " .
		"left join TagStorage on Rack.id = TagStorage.entity_id and entity_realm = 'rack' " .
		"where 1=1 " .
		$whereclause .
		" group by RackRow.id order by RackRow.name";
	$result = Database::query ($query);
	$ret = array();
	$clist = array ('row_id', 'row_name', 'count');
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach ($clist as $cname)
			$ret[$row['row_id']][$cname] = $row[$cname];
	Database::closeCursor($result);
	return $ret;
}

// Return detailed information about one rack row.
function getRackRowInfo ($rackrow_id)
{
	Database::inLifetime('RackRow', $rackrow_id);
	$query =
		"select RackRow.id as id, RackRow.name as name, count(Rack.id) as count, " .
		"if(isnull(sum(Rack.height)),0,sum(Rack.height)) as sum " .
		"from RackRow left join Rack on Rack.row_id = RackRow.id " .
		"where RackRow.id = ? " .
		"group by RackRow.id";
	$result = Database::query ($query, array(1=>$rackrow_id));
	$row = $result->fetch (PDO::FETCH_ASSOC);
	return $row;
}


function getRackRows ()
{
	$query = "select id, name from RackRow ";
	$result = Database::query($query);
	$rows = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$rows[$row['id']] = parseWikiLink ($row['name'], 'o');
	Database::closeCursor($result);
	asort ($rows);
	return $rows;
}



function commitAddRow($rackrow_name)
{
	Database::insert(array('name'=>$rackrow_name), 'RackRow');
	return TRUE;
}

function commitUpdateRow($rackrow_id, $rackrow_name)
{
	Database::update(array('name'=>$rackrow_name), 'RackRow', $rackrow_id);
	return TRUE;
}

function commitDeleteRow($rackrow_id)
{
	$query = "select count(*) from Rack where row_id = ?";
	$result = Database::query($query, array(1=>$rackrow_id));
	if (($result!=NULL) && ($row = $result->fetch(PDO::FETCH_NUM)) )
		if ($row[0] == 0)
			Database::delete('RackRow', $rackrow_id);
	Database::closeCursor($result);
	return TRUE;
}




// This function returns id->name map for all object types. The map is used
// to build <select> input for objects.
function getObjectTypeList ()
{
	return readChapter ('RackObjectType');
}

// Return a part of SQL query suitable for embeding into a bigger text.
// The returned result should list all tag IDs shown in the tag filter.
function getWhereClause ($tagfilter = array())
{
	$whereclause = '';
	if (count ($tagfilter))
	{
		$whereclause .= ' and (';
		$conj = '';
		foreach ($tagfilter as $tag_id)
		{
			$whereclause .= $conj . 'tag_id = ' . $tag_id;
			$conj = ' or ';
		}
		$whereclause .= ') ';
	}
	return $whereclause;
}

function getTagFilterCondition($tagfilter = array(), $wherepos = 1)
{
	$whereclause = '';
	$wherevalues = array();
	if (count ($tagfilter))
	{
		$whereclause = '( ';
		$first = true;
		foreach ($tagfilter as $tag_id)
		{
			if (!$first)
				$whereclause .= ' or ';
			$whereclause .= 'tag_id = ?';
			$wherevalues[$wherepos] = $tag_id;
			$wherepos++;
			$first = false;
		}
		$whereclause .= ' ) ';
	}
	return array($whereclause, $wherevalues, $wherepos);
}

// Return a simple object list w/o related information, so that the returned value
// can be directly used by printSelect(). An optional argument is the name of config
// option with constraint in RackCode.
function getNarrowObjectList ($varname = '')
{
	$ret = array();
	$query =
		"select RackObject.id as id, RackObject.name as name, dict_value as objtype_name, " .
		"objtype_id from " .
		"RackObject inner join Dictionary on objtype_id=dict_key join Chapter on Chapter.id = Dictionary.chapter_id " .
		"where RackObject.deleted = 'no' and Chapter.name = 'RackObjectType' " .
		"order by objtype_id, name";
	$result = useSelectBlade ($query, __FUNCTION__);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[$row['id']] = displayedName ($row);
	if (strlen ($varname) and strlen (getConfigVar ($varname)))
	{
		global $parseCache;
		if (!isset ($parseCache[$varname]))
			$parseCache[$varname] = spotPayload (getConfigVar ($varname), 'SYNT_EXPR');
		if ($parseCache[$varname]['result'] != 'ACK')
			return array();
		$ret = filterEntityList ($ret, 'object', $parseCache[$varname]['load']);
	}
	return $ret;
}

// Return a filtered, detailed object list.
function getObjectList ($type_id = 0, $tagfilter = array(), $tfmode = 'any')
{
	$wherevalues = array();
	$wherenum = 1;
	$whereclause = '';
	list($whereclause, $wherevalues, $wherenum) = getTagFilterCondition ($tagfilter, $wherenum);
	if ($type_id != 0)
	{
		if ($whereclause != '')
			$whereclause .= ' and ';
		$whereclause .= 'objtype_id = ? ';
		$wherevalues[$wherenum] = $type_id;
		$wherenum++;
	}
	$query =
		"select distinct RackObject.id as id , RackObject.name as name, dict_value as objtype_name, " .
		"RackObject.label as label, RackObject.barcode as barcode, " .
		"Dictionary.id as objtype_id, asset_no, rack_id, Rack.name as Rack_name, Rack.row_id, " .
		"RackRow.name as Row_name " .
		"from ((RackObject inner join Dictionary on objtype_id=Dictionary.id join Chapter on Chapter.id = Dictionary.chapter_id) " .
		"left join RackSpace on RackObject.id = object_id) " .
		"left join Rack on rack_id = Rack.id " .
		"left join RackRow on Rack.row_id = RackRow.id " .
		"left join TagStorage on RackObject.id = TagStorage.entity_id and entity_realm = 'object' " .
		"where Chapter.name = 'RackObjectType' " .
		($whereclause != ''?'and ':'') .
		$whereclause .
		"order by name";
	$result = Database::query ($query, $wherevalues);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		foreach (array (
			'id',
			'name',
			'label',
			'barcode',
			'objtype_name',
			'objtype_id',
			'asset_no',
			'rack_id',
			'Rack_name',
			'row_id',
			'Row_name'
			) as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
		$ret[$row['id']]['dname'] = displayedName ($ret[$row['id']]);
	}
	Database::closeCursor($result);
	return $ret;
}

function getRacksForRow ($row_id = 0, $tagfilter = array(), $tfmode = 'any')
{
	$query =
		"select Rack.id, Rack.name, height, Rack.comment, row_id, RackRow.name as row_name " .
		"from Rack left join RackRow on Rack.row_id = RackRow.id " .
		"left join TagStorage on Rack.id = TagStorage.entity_id and entity_realm = 'rack' " .
		"where 1=1 " .
		(($row_id == 0) ? "" : " and row_id = ${row_id} ") .
		getWhereClause ($tagfilter) .
		" order by row_name, Rack.name";
	$result = Database::query ($query);
	$ret = array();
	$clist = array
	(
		'id',
		'name',
		'height',
		'comment',
		'row_id',
		'row_name'
	);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach ($clist as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
	Database::closeCursor($result);
	return $ret;
}

// This is a popular helper for getting information about
// a particular rack and its rackspace at once.
function getRackData ($rack_id = 0, $silent = FALSE)
{
	if ($rack_id == 0)
		throw new Exception ('Invalid rack_id $rack_id');
	Database::inLifetime('Rack', $rack_id);
	$query =
		"select Rack.id, Rack.name, row_id, height, Rack.comment, RackRow.name as row_name from " .
		"Rack left join RackRow on Rack.row_id = RackRow.id  " .
		"where  Rack.id='${rack_id}'";
	$result = Database::query ($query);
	$row = $result->fetch (PDO::FETCH_ASSOC);
	// load metadata
	$clist = array
	(
		'id',
		'name',
		'height',
		'comment',
		'row_id',
		'row_name'
	);
	foreach ($clist as $cname)
		$rack[$cname] = $row[$cname];
	Database::closeCursor($result);

	// start with default rackspace
	for ($i = $rack['height']; $i > 0; $i--)
		for ($locidx = 0; $locidx < 3; $locidx++)
			$rack[$i][$locidx]['state'] = 'F';

	// load difference
	$query =
		"select unit_no, atom, state, object_id " .
		"from RackSpace where rack_id = ${rack_id} and " .
		"unit_no between 1 and " . $rack['height'] . " order by unit_no";
	$result = Database::query ($query);
	global $loclist;
	$mounted_objects = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$rack[$row['unit_no']][$loclist[$row['atom']]]['state'] = $row['state'];
		$rack[$row['unit_no']][$loclist[$row['atom']]]['object_id'] = $row['object_id'];
		if ($row['state'] == 'T' and $row['object_id']!=NULL)
			$mounted_objects[$row['object_id']] = TRUE;
		//We need to mark atoms as free, if their state is T, but object id is null
		if ($rack[$row['unit_no']][$loclist[$row['atom']]]['state'] == 'T' and $rack[$row['unit_no']][$loclist[$row['atom']]]['object_id'] === NULL)
			$rack[$row['unit_no']][$loclist[$row['atom']]]['state'] = 'F';
	}
	$rack['mountedObjects'] = array_keys($mounted_objects);
	Database::closeCursor($result);
	return $rack;
}

// This is a popular helper.
function getObjectInfo ($object_id = 0, $set_dname = TRUE)
{
	if ($object_id == 0)
		throw new Exception ('Invalid object_id');
	Database::inLifetime('RackObject', $object_id);
	$query =
		"select RackObject.id as id, RackObject.name as name, label, barcode, dict_value as objtype_name, asset_no, Dictionary.id as objtype_id, has_problems, comment from " .
		"RackObject inner join Dictionary on objtype_id = Dictionary.id " .
		"where RackObject.id = ? ";
	$result = Database::query($query, array(1=>$object_id));
	$row = $result->fetch (PDO::FETCH_ASSOC);
	$ret['id'] = $row['id'];
	$ret['name'] = $row['name'];
	$ret['label'] = $row['label'];
	$ret['barcode'] = $row['barcode'];
	$ret['objtype_name'] = $row['objtype_name'];
	$ret['objtype_id'] = $row['objtype_id'];
	$ret['has_problems'] = $row['has_problems'];
	$ret['asset_no'] = $row['asset_no'];
	$ret['dname'] = displayedName ($ret);
	$ret['comment'] = $row['comment'];
	Database::closeCursor($result);
	return $ret;
}

function getArrayObjectInfo ($objects = array())
{
	$ret = array();
	if (count($objects) == 0)
		return $ret;
	$query =
		'select RackObject.id as id, RackObject.name as name, label, barcode, dict_value as objtype_name, asset_no, Dictionary.id as objtype_id, has_problems, comment from ' .
		'RackObject inner join Dictionary on objtype_id = Dictionary.id ' .
		'where RackObject.id in ( ';

	$first = true;
	$bindPos = 1;
	$bindValues = array();
	foreach($objects as $object_id)
	{
		Database::inLifetime('RackObject', $object_id);
		if (!$first)
			$query .= ', ';
		$first = false;
		$query .= ' ? ';
		$bindValues[$bindPos++] = $object_id;
	};
	$query .= ')';
	$result = Database::query($query, $bindValues);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$id = $row['id'];
		$ret[$id]['id'] = $row['id'];
		$ret[$id]['name'] = $row['name'];
		$ret[$id]['label'] = $row['label'];
		$ret[$id]['barcode'] = $row['barcode'];
		$ret[$id]['objtype_name'] = $row['objtype_name'];
		$ret[$id]['objtype_id'] = $row['objtype_id'];
		$ret[$id]['has_problems'] = $row['has_problems'];
		$ret[$id]['asset_no'] = $row['asset_no'];
		$ret[$id]['dname'] = displayedName ($row);
		$ret[$id]['comment'] = $row['comment'];
	}
	Database::closeCursor($result);
	return $ret;
}



function getPortTypes ()
{
	return readChapter ('PortType');
}

function getObjectPortsAndLinks ($object_id = 0)
{
	if ($object_id == 0)
	{
		showError ('Invalid object_id', __FUNCTION__);
		return;
	}
	// prepare decoder
	$ptd = readChapter ('PortType');
	$query = "select id, name, label, l2address, type as type_id, reservation_comment from Port where object_id = ${object_id}";
	// list and decode all ports of the current object
	$result = Database::query ($query);
	$ret=array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$row['type'] = $ptd[$row['type_id']];
		$row['l2address'] = l2addressFromDatabase ($row['l2address']);
		$row['remote_id'] = NULL;
		$row['remote_name'] = NULL;
		$row['remote_object_id'] = NULL;
		$row['remote_object_name'] = NULL;
		$ret[] = $row;
	}
	unset ($result);
	// now find and decode remote ends for all locally terminated connections
	foreach (array_keys ($ret) as $tmpkey)
	{
		$portid = $ret[$tmpkey]['id'];
		$remote_id = NULL;
		$query = "select porta, portb from Link where porta = {$portid} or portb = ${portid}";
		$result = Database::query ($query);
		if ($row = $result->fetch (PDO::FETCH_ASSOC))
		{
			if ($portid != $row['porta'])
				$remote_id = $row['porta'];
			elseif ($portid != $row['portb'])
				$remote_id = $row['portb'];
		}
		unset ($result);
		if ($remote_id) // there's a remote end here
		{
			$query = "select Port.name as port_name, Port.type as port_type, object_id, RackObject.name as object_name " .
				"from Port left join RackObject on Port.object_id = RackObject.id " .
				"where Port.id = ${remote_id}";
			$result = Database::query ($query);
			if ($row = $result->fetch (PDO::FETCH_ASSOC))
			{
				$ret[$tmpkey]['remote_name'] = $row['port_name'];
				$ret[$tmpkey]['remote_object_id'] = $row['object_id'];
				$ret[$tmpkey]['remote_object_name'] = $row['object_name'];
			}
			$ret[$tmpkey]['remote_id'] = $remote_id;
			unset ($result);
			// only call displayedName() when necessary
			if (empty ($ret[$tmpkey]['remote_object_name']) and !empty ($ret[$tmpkey]['remote_object_id']))
			{
				$oi = getObjectInfo ($ret[$tmpkey]['remote_object_id']);
				$ret[$tmpkey]['remote_object_name'] = $oi['dname'];
			}
		}
	}
	return $ret;
}

function commitAddRack ($name, $height = 0, $row_id = 0, $comment, $taglist)
{
	if ($row_id <= 0 or $height <= 0 or empty ($name))
		return FALSE;
	$last_insert_id = Database::insert
	(
		array
		(
			'row_id' => $row_id,
			'name' => $name,
			'height' =>  $height,
			'comment' => $comment
		),
		'Rack'
	);
	return (produceTagsForLastRecord ('rack', $taglist, $last_insert_id) == '');
}

function commitAddObject ($new_name, $new_label, $new_barcode, $new_type_id, $new_asset_no, $taglist = array())
{
	// Maintain UNIQUE INDEX for common names and asset tags by
	// filtering out empty strings (not NULLs).
	try {
		$last_insert_id = Database::insert
		(
			array
			(
				'name' => empty ($new_name) ? NULL : $new_name,
				'label' => $new_label,
				'barcode' => empty ($new_barcode) ? NULL : $new_barcode,
				'objtype_id' => $new_type_id,
				'asset_no' => empty ($new_asset_no) ? NULL : $new_asset_no,
				'has_problems' => 'no'
			),
			'RackObject'
		);
	} catch (UniqueConstraintException $e) {
		return FALSE;
	}
	// Do AutoPorts magic
	executeAutoPorts ($last_insert_id, $new_type_id);
	// Now tags...
	$error = produceTagsForLastRecord ('object', $taglist, $last_insert_id);
	if ($error != '')
	{
		showError ("Error adding tags for the object: ${error}");
		return FALSE;
	}
	return TRUE;
}

function commitUpdateObject ($object_id = 0, $new_name = '', $new_label = '', $new_barcode = '', $new_type_id = 0, $new_has_problems = 'no', $new_asset_no = '', $new_comment = '')
{
	if ($object_id == 0 || $new_type_id == 0)
	{
		showError ('Not all required args are present.', __FUNCTION__);
		return FALSE;
	}
	$new_asset_no = empty ($new_asset_no) ? NULL : $new_asset_no;
	$new_barcode = empty ($new_barcode) ? NULL : $new_barcode;
	$new_name = empty ($new_name) ? NULL : $new_name;
	try {
		Database::update(
			array(
				'name'=>$new_name,
				'label'=>$new_label,
				'barcode'=>$new_barcode,
				'objtype_id'=>$new_type_id,
				'has_problems'=>$new_has_problems,
				'asset_no'=>$new_asset_no,
				'comment'=>$new_comment
			), 'RackObject', $object_id);
	} catch (UniqueConstraintException $e) {
		return FALSE;
	}
	return TRUE;
}

// There are times when you want to delete all traces of an object
function commitDeleteObject ($object_id = 0)
{
	if ($object_id <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::deleteWhere('AttributeValue', array('object_id'=>$object_id));
	$result = Database::query('SELECT file_id FROM FileLink WHERE entity_id = \'object\' AND entity_id = ?', array(1=>$object_id));
	while ($row = $result->fetch(PDO::FETCH_NUM))
		Database::delete('File', $row[0]);
	Database::closeCursor($result);
	Database::deleteWhere('IPv4LB', array('object_id'=>$object_id));
	Database::deleteWhere('IPv4Allocation', array('object_id'=>$object_id));
	Database::deleteWhere('Port', array('object_id'=>$object_id));
	Database::deleteWhere('IPv4NAT', array('object_id'=>$object_id));
	Database::deleteWhere('RackSpace', array('object_id'=>$object_id));
	Database::deleteWhere('TagStorage', array('entity_realm'=>'object', 'entity_id'=>$object_id));
	Database::delete('RackObject', $object_id);
	return '';
}

function commitDeleteRack($rack_id)
{
	Database::deleteWhere('RackSpace', array('rack_id'=>$rack_id));
	Database::deleteWhere('TagStorage', array('entity_realm'=>'rack', 'entity_id'=>$rack_id));
	Database::delete('Rack', $rack_id);
	return TRUE;
}

function commitUpdateRack ($rack_id, $new_name, $new_height, $new_row_id, $new_comment)
{
	if (empty ($rack_id) || empty ($new_name) || empty ($new_height))
	{
		showError ('Not all required args are present.', __FUNCTION__);
		return FALSE;
	}
	Database::update(
		array(
			'name'=>$new_name,
			'height'=>$new_height,
			'comment'=>$new_comment,
			'row_id'=>$new_row_id
		), 'Rack', $rack_id);
	return TRUE;
}

// This function accepts rack data returned by getRackData(), validates and applies changes
// supplied in $_REQUEST and returns resulting array. Only those changes are examined, which
// correspond to current rack ID.
// 1st arg is rackdata, 2nd arg is unchecked state, 3rd arg is checked state.
// If 4th arg is present, object_id fields will be updated accordingly to the new state.
// The function returns the modified rack upon success.
function processGridForm (&$rackData, $unchecked_state, $checked_state, $object_id = 0)
{
	global $loclist;
	$rack_id = $rackData['id'];
	$rack_name = $rackData['name'];
	$rackchanged = FALSE;
	for ($unit_no = $rackData['height']; $unit_no > 0; $unit_no--)
	{
		for ($locidx = 0; $locidx < 3; $locidx++)
		{
			if ($rackData[$unit_no][$locidx]['enabled'] != TRUE)
				continue;
			// detect a change
			$state = $rackData[$unit_no][$locidx]['state'];
			if (isset ($_REQUEST["atom_${rack_id}_${unit_no}_${locidx}"]) and $_REQUEST["atom_${rack_id}_${unit_no}_${locidx}"] == 'on')
				$newstate = $checked_state;
			else
				$newstate = $unchecked_state;
			if ($state == $newstate)
				continue;
			$rackchanged = TRUE;
			// and validate
			$atom = $loclist[$locidx];
			// The only changes allowed are those introduced by checkbox grid.
			if
			(
				!($state == $checked_state && $newstate == $unchecked_state) &&
				!($state == $unchecked_state && $newstate == $checked_state)
			)
				return array ('code' => 500, 'message' => "${rack_name}: Rack ID ${rack_id}, unit ${unit_no}, 'atom ${atom}', cannot change state from '${state}' to '${newstate}'");
			// Here we avoid using ON DUPLICATE KEY UPDATE by first performing DELETE
			// anyway and then looking for probable need of INSERT.
			Database::startTransaction();
			$result = Database::query("select id from RackSpace where rack_id = ? and unit_no = ? and atom = ?", array(1=>$rack_id, 2=>$unit_no, 3=>$atom));
			if ($row = $result->fetch())
			{
				Database::closeCursor($result);
				if ($newstate == 'T' and $object_id != 0)
				{
					Database::update(array('object_id'=>$object_id, 'state'=>$newstate), 'RackSpace', $row[0]);
				}
				else
				{
					Database::update(array('object_id'=>null, 'state'=>$newstate), 'RackSpace', $row[0]);
				}
			}
			else
			{
				if ($newstate == 'T' and $object_id != 0)
				{
					Database::insert(array(
						'rack_id'=>$rack_id,
						'unit_no'=>$unit_no,
						'atom'=>$atom,
						'object_id'=>$object_id,
						'state'=>$newstate), 'RackSpace');

				}
				else
				{
					Database::insert(array(
						'rack_id'=>$rack_id,
						'unit_no'=>$unit_no,
						'atom'=>$atom,
						'object_id'=>null,
						'state'=>$newstate), 'RackSpace');
				}
			}
			Database::commit();
		}
	}
	if ($rackchanged)
	{
		resetThumbCache ($rack_id);
		return array ('code' => 200, 'message' => "${rack_name}: All changes were successfully saved.");
	}
	else
		return array ('code' => 300, 'message' => "${rack_name}: No changes.");
}

function getRackSpaceChangedBetween($rev1, $rev2)
{
	$racks = array();
	$result = Database::getObjectsChangedBetween($rev1, $rev2, 'RackSpace');
	while($row = $result->fetch())
	{
		$result1 = Database::getStatic('RackSpace', $row[0]);
		$row1 = $result1->fetch();
		if (!in_array($row1['rack_id'], $racks))
			$racks[] = $row1['rack_id'];
		Database::closeCursor($result1);
	}
	Database::closeCursor($result);
	return $racks;
}

// returns exactly what is's named after
function lastInsertID ()
{
	return Database::getLastInsertId();
}

function getHistoryForObject($object_type, $id=NULL)
{
	if ($object_type == 'row')
	{
		$result = Database::getHistory('RackRow', $id);
		$history = $result->fetchAll();
		Database::closeCursor($result);
	}
	elseif ($object_type == 'rack')
	{
		$result = Database::getHistory('Rack', $id);
		while($row = $result->fetch())
		{
			$history[$row['rev']] = $row;
		}
		Database::closeCursor($result);
		$history = Operation::getOperationsForHistory($history);
		foreach($history as &$row)
		{
			$rev = Database::getRevision();
			Database::setRevision($row['rev']);
			$result1 = Database::query("select name from RackRow where id = ?", array(1=>$row['row_id']));
			$row1 = $result1->fetch();
			$row['row_name'] = $row1['name'];
			Database::closeCursor($result1);
			Database::setRevision($rev);
		}
	}
	elseif ($object_type == 'object')
	{
		$result = Database::getHistory('RackObject', $id);
		while($row = $result->fetch())
		{
			$history[$row['rev']] = $row;
		}
		Database::closeCursor($result);
		$history = Operation::getOperationsForHistory($history);
		foreach($history as &$row)
		{
			$rev = Database::getRevision();
			Database::setRevision($row['rev']);
			$result1 = Database::query("select dict_value from Dictionary join Chapter on Dictionary.chapter_id = Chapter.id where Chapter.name = 'RackObjectType' and Dictionary.id = ?", array(1=>$row['objtype_id']));
			$row1 = $result1->fetch();
			$row['objtype'] = $row1['dict_value'];
			Database::closeCursor($result1);
			Database::setRevision($rev);
		}
	}
	elseif ($object_type == 'rackspace')
	{
		$history = array();
		$result = Database::getHistory('RackSpace', $id);
		while($row = $result->fetch())
		{
			$history[$row['rev']] = $row;
		}
		Database::closeCursor($result);
		$history = Operation::getOperationsForHistory($history);
		foreach($history as &$row)
		{
			$rev = Database::getRevision();
			Database::setRevision($row['rev']);
			$result1 = Database::query("select RackObject.name as name, Dictionary.dict_value as object_type from RackObject left join Dictionary on RackObject.objtype_id = Dictionary.id join Chapter on Dictionary.chapter_id = Chapter.id where Chapter.name = 'RackObjectType' and RackObject.id = ?", array(1=>$row['object_id']));
			$row1 = $result1->fetch();
			$row['object_name'] = $row1['name'];
			$row['objtype'] = $row1['object_type'];
			Database::closeCursor($result1);
			Database::setRevision($rev);
		}
	}
	elseif ($object_type == 'ipv4net')
	{
		$result = Database::getHistory('IPv4Network', $id);
		$history = $result->fetchAll();
		Database::closeCursor($result);
	}
	elseif ($object_type == 'ipaddress')
	{
		$result = Database::getHistory('IPv4Address', $id);
		$history = $result->fetchAll();
		Database::closeCursor($result);
	}
	elseif ($object_type == 'file')
	{
		$result = Database::getHistory('File', $id);
		$history = $result->fetchAll();
		Database::closeCursor($result);
	}
	else
	{
		throw new Exception ("Unknown object type '${object_type}'");
	}
	foreach($history as &$row)
		$row['hr_timestamp'] = date('d/m/Y H:i:s', $row['timestamp']);
	return $history;
}

function getResidentRacksData ($object_id = 0, $fetch_rackdata = TRUE)
{
	if ($object_id <= 0)
	{
		showError ('Invalid object_id', __FUNCTION__);
		return;
	}
	$query = "select distinct rack_id from RackSpace where object_id = ${object_id} order by rack_id";
	$result = Database::query ($query);
	$rows = $result->fetchAll (PDO::FETCH_NUM);
	Database::closeCursor($result);
	$ret = array();
	foreach ($rows as $row)
	{
		if (!$fetch_rackdata)
		{
			$ret[$row[0]] = $row[0];
			continue;
		}
		$rackData = getRackData ($row[0]);
		$ret[$row[0]] = $rackData;
	}
	Database::closeCursor($result);
	return $ret;
}

function getObjectGroupInfo ()
{
	$query =
		'select Dictionary.id as id, dict_value as name, count(Dictionary.id) as count from ' .
		'Dictionary join Chapter on Chapter.id = Dictionary.chapter_id join RackObject on Dictionary.id = objtype_id ' .
		'where Chapter.name = "RackObjectType" ' .
		'group by Dictionary.id order by dict_value';
	$result = Database::query ($query);
	$ret = array();
	$ret[0] = array ('id' => 0, 'name' => 'ALL types');
	$clist = array ('id', 'name', 'count');
	$total = 0;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		if ($row['count'] > 0)
		{
			$total += $row['count'];
			foreach ($clist as $cname)
				$ret[$row['id']][$cname] = $row[$cname];
		}
	Database::closeCursor($result);
	$ret[0]['count'] = $total;
	return $ret;
}

// This function returns objects, which have no rackspace assigned to them.
// Additionally it keeps rack_id parameter, so we can silently pre-select
// the rack required.
function getUnmountedObjects ()
{
	$query =
		'select dict_value as objtype_name, Dictionary.id as objtype_id, name, label, barcode, id, asset_no from ' .
		'RackObject inner join Dictionary on objtype_id = Dictionary.id join Chapter on Chapter.id = Dictionary.chapter_id ' .
		'left join RackSpace on id = object_id '.
		'where rack_id is null and Chapter.name = "RackObjectType" order by dict_value, name, label, asset_no, barcode';
	$result = Database::query ($query);
	$ret = array();
	$clist = array ('id', 'name', 'label', 'barcode', 'objtype_name', 'objtype_id', 'asset_no');
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		foreach ($clist as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
		$ret[$row['id']]['dname'] = displayedName ($ret[$row['id']]);
	}
	Database::closeCursor($result);
	return $ret;
}

function getProblematicObjects ()
{
	$query =
		'select dict_value as objtype_name, Dictionary.id as objtype_id, name, id, asset_no from ' .
		'RackObject inner join Dictionary on objtype_id = Dictionary.id join Chapter on Chapter.id = Dictionary.chapter_id '.
		'where has_problems = "yes" and Chapter.name = "RackObjectType" order by objtype_name, name';
	$result = Database::query ($query);
	$ret = array();
	$clist = array ('id', 'name', 'objtype_name', 'objtype_id', 'asset_no');
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		foreach ($clist as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
		$ret[$row['id']]['dname'] = displayedName ($ret[$row['id']]);
	}
	Database::closeCursor($result);
	return $ret;
}

function commitAddPort ($object_id = 0, $port_name, $port_type_id, $port_label, $port_l2address)
{
	if ($object_id <= 0)
	{
		showError ('Invalid object_id', __FUNCTION__);
		return;
	}
	if (NULL === ($db_l2address = l2addressForDatabase ($port_l2address)))
		return "Invalid L2 address ${port_l2address}";
	Database::insert
	(
		array
		(
			'name' => $port_name,
			'object_id' => $object_id,
			'label' => $port_label,
			'type' => $port_type_id,
			'l2address' => ($db_l2address === '') ? NULL : $db_l2address
		),
		'Port'
	);
	return '';
}

// The fifth argument may be either explicit 'NULL' or some (already quoted by the upper layer)
// string value. In case it is omitted, we just assign it its current value.
// It would be nice to simplify this semantics later.
function commitUpdatePort ($port_id, $port_name, $port_type_id, $port_label, $port_l2address, $port_reservation_comment = NULL)
{
	if (NULL === ($db_l2address = l2addressForDatabase ($port_l2address)))
		return "Invalid L2 address ${port_l2address}";
	if (isset($port_reservation_comment))
	{
		Database::update(array(
			'name'=>$port_name,
			'type'=>$port_type_id,
			'label'=>$port_label,
			'reservation_comment'=>(($port_reservation_comment === '') ? NULL : $port_reservation_comment),
			'l2address'=>(($db_l2address === '') ? NULL : $db_l2address)
			), 'Port', $port_id);
	}
	else
	{
		Database::update(array(
			'name'=>$port_name,
			'type'=>$port_type_id,
			'label'=>$port_label,
			'l2address'=>(($db_l2address === '') ? NULL : $db_l2address)
			), 'Port', $port_id);
	}
	return '';
}

function delObjectPort ($port_id)
{
	if (unlinkPort ($port_id) != '')
		return __FUNCTION__ . ': unlinkPort() failed';
	Database::delete('Port', $port_id);
	return '';
}

function getAllIPv4Allocations ()
{
	$query =
		"select object_id as object_id, ".
		"RackObject.name as object_name, ".
		"IPv4Allocation.name as name, ".
		"INET_NTOA(ip) as ip ".
		"from IPv4Allocation join RackObject on RackObject.id=IPv4Allocation.object_id ";
	$result = Database::query ($query);
	$ret = array();
	$count=0;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$ret[$count]['object_id']=$row['object_id'];
		$ret[$count]['object_name']=$row['object_name'];
		$ret[$count]['name']=$row['name'];
		$ret[$count]['ip']=$row['ip'];
		$count++;
	}
	Database::closeCursor($result);
	return $ret;
}

function getObjectsEmptyPortsOfType ($type_id)
{
	$query =
		"select distinct ".
		"RackObject.id as id, ".
		"RackObject.name as name, ".
		"RackObject.objtype_id as objtype_id, ".
		"Dictionary.dict_value as objtype_name ".
		"from ( ".
		"	Port ".
		" 	join RackObject on Port.object_id = RackObject.id ".
		" 	join Dictionary on RackObject.objtype_id = Dictionary.id ".
		") ".
		"left join Link on Port.id=Link.porta or Port.id=Link.portb ".
		"inner join PortCompat on Port.type = PortCompat.type2 ".
		"where PortCompat.type1 = '$type_id' and Link.porta is NULL ".
		"and ( Port.reservation_comment is null or Port.reservation_comment = '' ) order by name";
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[]=$row;
	Database::closeCursor($result);
	return $ret;
}

function getEmptyPortsOfTypeForObject ($object_id, $type_id)
{
	$query =
		"select distinct ".
		"Port.id as id, ".
		"Port.name as name ".
		"from ( ".
		"	Port ".
		") ".
		"left join Link on Port.id=Link.porta or Port.id=Link.portb ".
		"inner join PortCompat on Port.type = PortCompat.type2 ".
		"where PortCompat.type1 = '$type_id' and Link.porta is NULL ".
		"and Port.object_id = '$object_id' ".
		"and ( Port.reservation_comment is null or Port.reservation_comment = '' ) order by name";
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[]=$row;
	Database::closeCursor($result);
	return $ret;
}



function linkPorts ($porta, $portb)
{
	if ($porta == $portb)
		return "Ports can't be the same";
	if ($porta > $portb)
	{
		$tmp = $porta;
		$porta = $portb;
		$portb = $tmp;
	}
	Database::insert(array('porta'=>$porta, 'portb'=>$portb), 'Link');
	Database::update(array('reservation_comment'=>NULL), 'Port', $porta);
	Database::update(array('reservation_comment'=>NULL), 'Port', $portb);
	return '';
}

function unlinkPort ($port)
{
	Database::deleteWhere('Link', array('porta'=>$port));
	Database::deleteWhere('Link', array('portb'=>$port));
	return '';
}

// Return all IPv4 addresses allocated to the objects. Attach detailed
// info about address to each alocation records. Index result by dotted-quad
// address.
function getObjectIPv4Allocations ($object_id = 0)
{
	$ret = array();
	$query = 'select name as osif, type, inet_ntoa(ip) as dottedquad from IPv4Allocation ' .
		"where object_id = ${object_id} " .
		'order by ip';
	$result = Database::query ($query);
	// don't spawn a sub-query with unfetched buffer, it may fail
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[$row['dottedquad']] = array ('osif' => $row['osif'], 'type' => $row['type']);
	unset ($result);
	foreach (array_keys ($ret) as $dottedquad)
		$ret[$dottedquad]['addrinfo'] = getIPv4Address ($dottedquad);
	return $ret;
}

// Return minimal IPv4 address, optionally with "ip" key set, if requested.
function constructIPv4Address ($dottedquad = NULL)
{
	$ret = array
	(
		'name' => '',
		'reserved' => 'no',
		'outpf' => array(),
		'inpf' => array(),
		'rslist' => array(),
		'allocs' => array(),
		'lblist' => array()
	);
	if ($dottedquad != NULL)
		$ret['ip'] = $dottedquad;
	return $ret;
}

// Check the range requested for meaningful IPv4 records, build them
// into a list and return. Return an empty list if nothing matched.
// Both arguments are expected in signed int32 form. The resulting list
// is keyed by uint32 form of each IP address, items aren't sorted.
// LATER: accept a list of pairs and build WHERE sub-expression accordingly
function scanIPv4Space ($pairlist)
{
	$ret = array();
	if (!count ($pairlist)) // this is normal for a network completely divided into smaller parts
		return $ret;;
	$dnamechache = array();
	// FIXME: this is a copy-and-paste prototype
	$or = '';
	$whereexpr1 = '(';
	$whereexpr2 = '(';
	$whereexpr3 = '(';
	$whereexpr4 = '(';
	$whereexpr5a = '(';
	$whereexpr5b = '(';
	foreach ($pairlist as $tmp)
	{
		$db_first = sprintf ('%u', 0x00000000 + $tmp['i32_first']);
		$db_last = sprintf ('%u', 0x00000000 + $tmp['i32_last']);
		$whereexpr1 .= $or . "ip between ${db_first} and ${db_last}";
		$whereexpr2 .= $or . "ip between ${db_first} and ${db_last}";
		$whereexpr3 .= $or . "vip between ${db_first} and ${db_last}";
		$whereexpr4 .= $or . "rsip between ${db_first} and ${db_last}";
		$whereexpr5a .= $or . "remoteip between ${db_first} and ${db_last}";
		$whereexpr5b .= $or . "localip between ${db_first} and ${db_last}";
		$or = ' or ';
	}
	$whereexpr1 .= ')';
	$whereexpr2 .= ')';
	$whereexpr3 .= ')';
	$whereexpr4 .= ')';
	$whereexpr5a .= ')';
	$whereexpr5b .= ')';

	// 1. collect labels and reservations
	$query = "select INET_NTOA(ip) as ip, name, reserved from IPv4Address ".
		"where ${whereexpr1} and (reserved = 'yes' or name != '')";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$ip_bin = ip2long ($row['ip']);
		if (!isset ($ret[$ip_bin]))
			$ret[$ip_bin] = constructIPv4Address ($row['ip']);
		$ret[$ip_bin]['name'] = $row['name'];
		$ret[$ip_bin]['reserved'] = $row['reserved'];
	}
	unset ($result);

	// 2. check for allocations
	$query =
		"select INET_NTOA(ipb.ip) as ip, ro.id as object_id, " .
		"ro.name as object_name, ipb.name, ipb.type, objtype_id, " .
		"dict_value as objtype_name from " .
		"IPv4Allocation as ipb inner join RackObject as ro on ipb.object_id = ro.id " .
		"left join Dictionary on objtype_id=Dictionary.id join Chapter on Chapter.id = Dictionary.chapter_id " .
		"where ${whereexpr2} " .
		"and Chapter.name = 'RackObjectType'" .
		"order by ipb.type, object_name";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$ip_bin = ip2long ($row['ip']);
		if (!isset ($ret[$ip_bin]))
			$ret[$ip_bin] = constructIPv4Address ($row['ip']);
		if (!isset ($dnamecache[$row['object_id']]))
		{
			$quasiobject['id'] = $row['object_id'];
			$quasiobject['name'] = $row['object_name'];
			$quasiobject['objtype_id'] = $row['objtype_id'];
			$quasiobject['objtype_name'] = $row['objtype_name'];
			$dnamecache[$row['object_id']] = displayedName ($quasiobject);
		}
		$tmp = array();
		foreach (array ('object_id', 'type', 'name') as $cname)
			$tmp[$cname] = $row[$cname];
		$tmp['object_name'] = $dnamecache[$row['object_id']];
		$ret[$ip_bin]['allocs'][] = $tmp;
	}
	unset ($result);

	// 3. look for virtual services and related LB 
	$query = "select vs_id, inet_ntoa(vip) as ip, vport, proto, vs.name, " .
		"object_id, objtype_id, ro.name as object_name, dict_value as objtype_name from " .
		"IPv4VS as vs inner join IPv4LB as lb on vs.id = lb.vs_id " .
		"inner join RackObject as ro on lb.object_id = ro.id " .
		"left join Dictionary on objtype_id=Dictionary.id " .
		"join Chapter on Chapter.id = Dictionary.chapter_id " .
		"where ${whereexpr3} " .
		"and Chapter.name = 'RackObjectType'" .
		"order by vport, proto, ro.name, object_id";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$ip_bin = ip2long ($row['ip']);
		if (!isset ($ret[$ip_bin]))
			$ret[$ip_bin] = constructIPv4Address ($row['ip']);
		if (!isset ($dnamecache[$row['object_id']]))
		{
			$quasiobject['name'] = $row['object_name'];
			$quasiobject['objtype_id'] = $row['objtype_id'];
			$quasiobject['objtype_name'] = $row['objtype_name'];
			$dnamecache[$row['object_id']] = displayedName ($quasiobject);
		}
		$tmp = array();
		foreach (array ('object_id', 'vport', 'proto', 'vs_id', 'name') as $cname)
			$tmp[$cname] = $row[$cname];
		$tmp['object_name'] = $dnamecache[$row['object_id']];
		$tmp['vip'] = $row['ip'];
		$ret[$ip_bin]['lblist'][] = $tmp;
	}
	unset ($result);

	// 4. don't forget about real servers along with pools
	$query = "select inet_ntoa(rsip) as ip, inservice, rsport, rspool_id, rsp.name as rspool_name from " .
		"IPv4RS as rs inner join IPv4RSPool as rsp on rs.rspool_id = rsp.id " .
		"where ${whereexpr4} " .
		"order by ip, rsport, rspool_id";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$ip_bin = ip2long ($row['ip']);
		if (!isset ($ret[$ip_bin]))
			$ret[$ip_bin] = constructIPv4Address ($row['ip']);
		$tmp = array();
		foreach (array ('rspool_id', 'rsport', 'rspool_name', 'inservice') as $cname)
			$tmp[$cname] = $row[$cname];
		$ret[$ip_bin]['rslist'][] = $tmp;
	}
	unset ($result);

	// 5. add NAT rules, part 1
	$query =
		"select " .
		"proto, " .
		"INET_NTOA(localip) as localip, " .
		"localport, " .
		"INET_NTOA(remoteip) as remoteip, " .
		"remoteport, " .
		"description " .
		"from IPv4NAT " .
		"where ${whereexpr5a} " .
		"order by localip, localport, remoteip, remoteport, proto";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$remoteip_bin = ip2long ($row['remoteip']);
		if (!isset ($ret[$remoteip_bin]))
			$ret[$remoteip_bin] = constructIPv4Address ($row['remoteip']);
		$ret[$remoteip_bin]['inpf'][] = $row;
	}
	unset ($result);
	// 5. add NAT rules, part 2
	$query =
		"select " .
		"proto, " .
		"INET_NTOA(localip) as localip, " .
		"localport, " .
		"INET_NTOA(remoteip) as remoteip, " .
		"remoteport, " .
		"description " .
		"from IPv4NAT " .
		"where ${whereexpr5b} " .
		"order by localip, localport, remoteip, remoteport, proto";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$localip_bin = ip2long ($row['localip']);
		if (!isset ($ret[$localip_bin]))
			$ret[$localip_bin] = constructIPv4Address ($row['localip']);
		$ret[$localip_bin]['outpf'][] = $row;
	}
	unset ($result);
	return $ret;
}

// Return summary data about an IPv4 prefix, if it exists, or NULL otherwise.
function getIPv4NetworkInfo ($id = 0)
{
	if ($id <= 0)
		throw new Exception ('Invalid arg');
	Database::inLifetime('IPv4Network', $id);
	$query = "select INET_NTOA(ip) as ip, mask, name ".
		"from IPv4Network where id = $id";
	$result = Database::query ($query);
	$ret = $result->fetch (PDO::FETCH_ASSOC);
	unset ($result);
	$ret['id'] = $id;
	$ret['ip_bin'] = ip2long ($ret['ip']);
	$ret['mask_bin'] = binMaskFromDec ($ret['mask']);
	$ret['mask_bin_inv'] = binInvMaskFromDec ($ret['mask']);
	$ret['db_first'] = sprintf ('%u', 0x00000000 + $ret['ip_bin'] & $ret['mask_bin']);
	$ret['db_last'] = sprintf ('%u', 0x00000000 + $ret['ip_bin'] | ($ret['mask_bin_inv']));
	return $ret;
}

function getIPv4Address ($dottedquad = '')
{
	if ($dottedquad == '')
		throw ('Invalid arg');
	$i32 = ip2long ($dottedquad); // signed 32 bit
	$scanres = scanIPv4Space (array (array ('i32_first' => $i32, 'i32_last' => $i32)));
	if (!isset ($scanres[$i32]))
		//$scanres[$i32] = constructIPv4Address ($dottedquad); // XXX: this should be verified to not break things
		return constructIPv4Address ($dottedquad);
	markupIPv4AddrList ($scanres);
	return $scanres[$i32];
}

function getIPv4AddressInfo($dottedquad = '')
{
	$result = Database::query("select id, INET_NTOA(ip) as ip, name, reserved from IPv4Address where ip = INET_ATON( ? )", array(1=>$dottedquad));
	$row = $result->fetch();
	Database::closeCursor($result);
	return $row;
}

function bindIpToObject ($ip = '', $object_id = 0, $name = '', $type = '')
{
	Database::insert
	(
		array
		(
			'ip' => array('left'=>"INET_ATON(", 'value'=>$ip, 'right'=>")"),
			'object_id' => $object_id,
			'name' => $name,
			'type' => $type
		),
		'IPv4Allocation'
	);
	return '';
}

// Collect data necessary to build a tree. Calling functions should care about
// setting the rest of data.
function getIPv4NetworkList ($tagfilter = array(), $tfmode = 'any')
{
	$whereclause = getWhereClause ($tagfilter);
	$query =
		"select distinct IPv4Network.id as id, INET_NTOA(ip) as ip, mask, name " .
		"from IPv4Network left join TagStorage on IPv4Network.id = entity_id and entity_realm = 'ipv4net' " .
		"where true ${whereclause} order by IPv4Network.ip, IPv4Network.mask";
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		// ip_bin and mask are used by iptree_fill()
		$row['ip_bin'] = ip2long ($row['ip']);
		$ret[$row['id']] = $row;
	}
	// After all the keys are known we can update parent_id appropriately. Also we don't
	// run two queries in parallel this way.
	$keys = array_keys ($ret);
	foreach ($keys as $netid)
	{
		// parent_id is for treeFromList()
		$ret[$netid]['parent_id'] = getIPv4AddressNetworkId ($ret[$netid]['ip'], $ret[$netid]['mask']);
		if ($ret[$netid]['parent_id'] and !in_array ($ret[$netid]['parent_id'], $keys))
		{
			$ret[$netid]['real_parent_id'] = $ret[$netid]['parent_id'];
			$ret[$netid]['parent_id'] = NULL;
		}
	}
	return $ret;
}

// Return the id of the smallest IPv4 network containing the given IPv4 address
// or NULL, if nothing was found. When finding the covering network for
// another network, it is important to filter out matched records with longer
// masks (they aren't going to be the right pick).
function getIPv4AddressNetworkId ($dottedquad, $masklen = 32)
{
// N.B. To perform the same for IPv6 address and networks, some pre-requisites
// are necessary and a different query. IPv6 addresses are 128 bit long, which
// is too much for both PHP and MySQL data types. These values must be split
// into 4 32-byte long parts (b_u32_0, b_u32_1, b_u32_2, b_u32_3).
// Then each network must have its 128-bit netmask split same way and either
// stored right in its record or JOINed from decoder and accessible as m_u32_0,
// m_u32_1, m_u32_2, m_u32_3. After that the query to pick the smallest network
// covering the given address would look as follows:
// $query = 'select id from IPv6Network as n where ' .
// "(${b_u32_0} & n.m_u32_0 = n.b_u32_0) and " .
// "(${b_u32_1} & n.m_u32_1 = n.b_u32_1) and " .
// "(${b_u32_2} & n.m_u32_2 = n.b_u32_2) and " .
// "(${b_u32_3} & n.m_u32_3 = n.b_u32_3) and " .
// "mask < ${masklen} " .
// 'order by mask desc limit 1';

	$query = 'select id from IPv4Network where ' .
		"inet_aton('${dottedquad}') & (4294967295 >> (32 - mask)) << (32 - mask) = ip " .
		"and mask < ${masklen} " .
		'order by mask desc limit 1';
	$result = Database::query ($query);
	if ($row = $result->fetch (PDO::FETCH_ASSOC))
		return $row['id'];
	return NULL;
}

function updateRange ($id=0, $name='')
{
	Database::update(array('name'=>$name), 'IPv4Network', $id);
	return '';
}

// This function is actually used not only to update, but also to create records,
// that's why ON DUPLICATE KEY UPDATE was replaced by DELETE-INSERT pair
// (MySQL 4.0 workaround).
function updateAddress ($ip = 0, $name = '', $reserved = 'no')
{
	$result = Database::query ('select count(*) from IPv4Address where ip = INET_ATON( ? )', array(1 => $ip));
	$numRows = $result->fetchColumn();
	Database::closeCursor($result);
	if ($numRows > 0)
	{
		Database::updateWhere( 
			array (
				'name' => $name, 
				'reserved' => $reserved
			),
			'IPv4Address',
			array ( 
				'ip' => array('left'=>'INET_ATON(', 'value'=>$ip, 'right'=>')')
			)
		);
	}
	else
	{
		Database::insert(
			array (
				'name' => $name, 
				'reserved' => $reserved,
				'ip' => array('left'=>'INET_ATON(', 'value'=>$ip, 'right'=>')')
			),
			'IPv4Address'
		);
	}
	return '';
}

function updateBond ($ip='', $object_id=0, $name='', $type='')
{
	Database::updateWhere(array(
		'name'=>$name,
		'type'=>$type ), 'IPv4Allocation', array(
		'ip'=>array('left'=>'INET_ATON(', 'value'=>$ip, 'right'=>')'),
		'object_id'=>$object_id));
	return '';
}

function unbindIpFromObject ($ip='', $object_id=0)
{
	Database::deleteWhere('IPv4Allocation', array(
		'ip'=>array('left'=>'INET_ATON(', 'value'=>$ip, 'right'=>')'),
		'object_id'=>$object_id));
	return '';
}

// This function returns either all or one user account. Array key is user name.
function getUserAccounts ($tagfilter = array(), $tfmode = 'any')
{
	$whereclause = getWhereClause ($tagfilter);
	$query =
		'select user_id, user_name, user_password_hash, user_realname ' .
		'from UserAccount left join TagStorage ' .
		"on UserAccount.user_id = TagStorage.entity_id and entity_realm = 'user' " .
		"where true ${whereclause} " .
		'order by user_name';
	$result = Database::query ($query);
	$ret = array();
	$clist = array ('user_id', 'user_name', 'user_realname', 'user_password_hash');
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach ($clist as $cname)
			$ret[$row['user_name']][$cname] = $row[$cname];
	Database::closeCursor($result);
	return $ret;
}

function searchByl2address ($port_l2address)
{
	if (NULL === ($db_l2address = l2addressForDatabase ($port_l2address)))
		return NULL; // Don't complain, other searches may return own data.
	$query = "select object_id, Port.id as port_id from RackObject as ro inner join Port on ro.id = Port.object_id " .
		"where l2address = '${db_l2address}'";
	$result = Database::query ($query);
	$rows = $result->fetchAll (PDO::FETCH_ASSOC);
	Database::closeCursor($result);
	if (count ($rows) == 0) // No results.
		return NULL;
	if (count ($rows) == 1) // Target found.
		return $rows[0];
	throw new Exception ('More than one results was found. This is probably a broken unique key.');
}

function getIPv4PrefixSearchResult ($terms)
{
	$query = "select id, inet_ntoa(ip) as ip, mask, name from IPv4Network where ";
	$or = '';
	foreach (explode (' ', $terms) as $term)
	{
		$query .= $or . "name like '%${term}%'";
		$or = ' or ';
	}
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[] = $row;
	return $ret;
}

function getIPv4AddressSearchResult ($terms)
{
	$query = "select inet_ntoa(ip) as ip, name from IPv4Address where ";
	$or = '';
	foreach (explode (' ', $terms) as $term)
	{
		$query .= $or . "name like '%${term}%'";
		$or = ' or ';
	}
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[] = $row;
	return $ret;
}

function getIPv4RSPoolSearchResult ($terms)
{
	$query = "select id as pool_id, name from IPv4RSPool where ";
	$or = '';
	foreach (explode (' ', $terms) as $term)
	{
		$query .= $or . "name like '%${term}%'";
		$or = ' or ';
	}
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[] = $row;
	return $ret;
}

function getIPv4VServiceSearchResult ($terms)
{
	$query = "select id, inet_ntoa(vip) as vip, vport, proto, name from IPv4VS where ";
	$or = '';
	foreach (explode (' ', $terms) as $term)
	{
		$query .= $or . "name like '%${term}%'";
		$or = ' or ';
	}
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[] = $row;
	return $ret;
}

function getAccountSearchResult ($terms)
{
	$byUsername = getSearchResultByField
	(
		'UserAccount',
		array ('user_id', 'user_name', 'user_realname'),
		'user_name',
		$terms,
		'user_name'
	);
	$byRealname = getSearchResultByField
	(
		'UserAccount',
		array ('user_id', 'user_name', 'user_realname'),
		'user_realname',
		$terms,
		'user_name'
	);
	// Filter out dupes.
	foreach ($byUsername as $res1)
		foreach (array_keys ($byRealname) as $key2)
			if ($res1['user_id'] == $byRealname[$key2]['user_id'])
			{
				unset ($byRealname[$key2]);
				continue 2;
			}
	return array_merge ($byUsername, $byRealname);
}

function getFileSearchResult ($terms)
{
	$byFilename = getSearchResultByField
	(
		'File',
		array ('id', 'name', 'comment', 'type', 'size'),
		'name',
		$terms,
		'name'
	);
	$byComment = getSearchResultByField
	(
		'File',
		array ('id', 'name', 'comment', 'type', 'size'),
		'comment',
		$terms,
		'name'
	);
	// Filter out dupes.
	foreach ($byFilename as $res1)
		foreach (array_keys ($byComment) as $key2)
			if ($res1['id'] == $byComment[$key2]['id'])
			{
				unset ($byComment[$key2]);
				continue 2;
			}
	return array_merge ($byFilename, $byComment);
}

function getSearchResultByField ($tname, $rcolumns, $scolumn, $terms, $ocolumn = '')
{
	$pfx = '';
	$query = 'select ';
	foreach ($rcolumns as $col)
	{
		$query .= $pfx . $col;
		$pfx = ', ';
	}
	$pfx = '';
	$query .= " from ${tname} where ";
	foreach (explode (' ', $terms) as $term)
	{
		$query .= $pfx . "${scolumn} like '%${term}%'";
		$pfx = ' or ';
	}
	if ($ocolumn != '')
		$query .= " order by ${ocolumn}";
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[] = $row;
	return $ret;
}

// This function returns either port ID or NULL for specified arguments.
function getPortID ($object_id, $port_name)
{
	$query = "select id from Port where object_id=${object_id} and name='${port_name}' limit 2";
	$result = Database::query ($query);
	$rows = $result->fetchAll (PDO::FETCH_NUM);
	if (count ($rows) != 1)
		return NULL;
	$ret = $rows[0][0];
	Database::closeCursor($result);
	return $ret;
}

function getPortByID ($id)
{
	$query = "select Port.id as id, Port.name as name, RackObject.id as object_id, RackObject.name as object_name from Port join RackObject on Port.object_id = RackObject.id where Port.id=${id}";
	$result = Database::query ($query);
	$row = $result->fetch();
	Database::closeCursor($result);
	return $row;
}

function commitCreateUserAccount ($username, $realname, $password)
{
	return Database::insert
	(
		array
		(
			'user_name' => $username,
			'user_realname' => $realname,
			'user_password_hash' => $password
		),
		'UserAccount'
	);
}

function commitUpdateUserAccount ($id, $new_username, $new_realname, $new_password)
{
	//Direct DB work is left here, as UserAccount doesn't have id and it won't work with our Database wrapper
	$query =
		"update UserAccount set user_name = '${new_username}', user_realname = '${new_realname}', " .
		"user_password_hash = '${new_password}' where user_id = ${id} limit 1";
	$result = Database::getDBLink()->exec ($query);
	return TRUE;
}

// This function returns an array of all port type pairs from PortCompat table.
function getPortCompat ()
{
	$query =
		"select type1, type2, d1.dict_value as type1name, d2.dict_value as type2name from " .
		"PortCompat as pc inner join Dictionary as d1 on pc.type1 = d1.id " .
		"inner join Dictionary as d2 on pc.type2 = d2.id " .
		"inner join Chapter as c1 on d1.chapter_id = c1.id " .
		"inner join Chapter as c2 on d2.chapter_id = c2.id " .
		"where c1.name = 'PortType' and c2.name = 'PortType'";
	$result = Database::query ($query);
	$ret = $result->fetchAll (PDO::FETCH_ASSOC);
	Database::closeCursor($result);
	return $ret;
}

function removePortCompat ($type1 = 0, $type2 = 0)
{
	//Direct DB work is left here, as PortCompat doesn't have id and it won't work with our Database wrapper

	if ($type1 == 0 or $type2 == 0)
	{
		showError ('Invalid arguments', __FUNCTION__);
		die;
	}
	Database::delete('PortCompat', array('type1'=>$type1, 'type2'=>$type2));
	return TRUE;
}

function addPortCompat ($type1 = 0, $type2 = 0)
{
	if ($type1 <= 0 or $type2 <= 0)
	{
		showError ('Invalid arguments', __FUNCTION__);
		die;
	}
	return Database::insert
	(
		array ('type1' => $type1, 'type2' => $type2),
		'PortCompat'
	);
}

// This function returns the dictionary as an array of trees, each tree
// representing a single chapter. Each element has 'id', 'name', 'sticky'
// and 'word' keys with the latter holding all the words within the chapter.
function getDict ($parse_links = FALSE)
{
	$query1 =
		"select Chapter.name as chapter_name, Chapter.id as chapter_no, Dictionary.id as dict_key, dict_value, sticky from " .
		"Chapter left join Dictionary on Chapter.id = Dictionary.chapter_id order by Chapter.name, dict_value";
	$result = Database::query ($query1);
	$dict = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$chapter_no = $row['chapter_no'];
		if (!isset ($dict[$chapter_no]))
		{
			$dict[$chapter_no]['no'] = $chapter_no;
			$dict[$chapter_no]['name'] = $row['chapter_name'];
			$dict[$chapter_no]['sticky'] = $row['sticky'] == 'yes' ? TRUE : FALSE;
			$dict[$chapter_no]['word'] = array();
		}
		if ($row['dict_key'] != NULL)
		{
			$dict[$chapter_no]['word'][$row['dict_key']] = ($parse_links or $row['dict_key'] <= MAX_DICT_KEY) ?
				parseWikiLink ($row['dict_value'], 'a') : $row['dict_value'];
			$dict[$chapter_no]['refcnt'][$row['dict_key']] = 0;
		}
	}
	Database::closeCursor($result);
	unset ($result);
// Find the list of all assigned values of dictionary-addressed attributes, each with
// chapter/word keyed reference counters. Use the structure to adjust reference counters
// of the returned disctionary words.
	$query2 = "select a.id as attr_id, am.chapter_id as chapter_no, uint_value, count(object_id) as refcnt " .
		"from Attribute as a inner join AttributeMap as am on a.id = am.attr_id " .
		"inner join AttributeValue as av on a.id = av.attr_id " .
		"inner join Dictionary as d on am.chapter_id = d.chapter_id and av.uint_value = d.id " .
		"where a.type = 'dict' group by a.id, am.chapter_id, uint_value " .
		"order by a.id, am.chapter_id, uint_value";
	$result = Database::query ($query2);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$dict[$row['chapter_no']]['refcnt'][$row['uint_value']] = $row['refcnt'];
	Database::closeCursor($result);
	return $dict;
}

function getDictStats ()
{
	$stock_chapters = array (1, 2, 11, 12, 13, 14, 16, 17, 18, 19, 20, 21, 22, 23);
	$query =
		"select Chapter.id as chapter_no, Chapter.name as chapter_name, count(Dictionary.id) as wc from " .
		"Chapter left join Dictionary on Chapter.id = Dictionary.chapter_id group by Chapter.id";
	$result = Database::query ($query);
	$tc = $tw = $uc = $uw = 0;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$tc++;
		$tw += $row['wc'];;
		if (in_array ($row['chapter_no'], $stock_chapters))
			continue;
		$uc++;
		$uw += $row['wc'];;
	}
	Database::closeCursor($result);
	unset ($result);
	$query = "select count(ro.id) as attrc from RackObject as ro left join " .
		"AttributeValue as av on ro.id = av.object_id group by ro.id";
	$result = Database::query ($query);
	$to = $ta = $so = 0;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$to++;
		if ($row['attrc'] != 0)
		{
			$so++;
			$ta += $row['attrc'];
		}
	}
	Database::closeCursor($result);
	$ret = array();
	$ret['Total chapters in dictionary'] = $tc;
	$ret['Total words in dictionary'] = $tw;
	$ret['User chapters'] = $uc;
	$ret['Words in user chapters'] = $uw;
	$ret['Total objects'] = $to;
	$ret['Objects with stickers'] = $so;
	$ret['Total stickers attached']  = $ta;
	return $ret;
}

function getIPv4Stats ()
{
	$ret = array();
	$subject = array();
	$subject[] = array ('q' => 'select count(id) from IPv4Network', 'txt' => 'Networks');
	$subject[] = array ('q' => 'select count(ip) from IPv4Address', 'txt' => 'Addresses commented/reserved');
	$subject[] = array ('q' => 'select count(ip) from IPv4Allocation', 'txt' => 'Addresses allocated');
	$subject[] = array ('q' => 'select count(*) from IPv4NAT', 'txt' => 'NAT rules');
	$subject[] = array ('q' => 'select count(id) from IPv4VS', 'txt' => 'Virtual services');
	$subject[] = array ('q' => 'select count(id) from IPv4RSPool', 'txt' => 'Real server pools');
	$subject[] = array ('q' => 'select count(id) from IPv4RS', 'txt' => 'Real servers');
	$subject[] = array ('q' => 'select count(distinct object_id) from IPv4LB', 'txt' => 'Load balancers');

	foreach ($subject as $item)
	{
		$result = Database::query ($item['q']);
		$row = $result->fetch (PDO::FETCH_NUM);
		$ret[$item['txt']] = $row[0];
		Database::closeCursor($result);
		unset ($result);
	}
	return $ret;
}

function getRackspaceStats ()
{
	$ret = array();
	$subject = array();
	$subject[] = array ('q' => 'select count(*) from RackRow', 'txt' => 'Rack rows');
	$subject[] = array ('q' => 'select count(*) from Rack', 'txt' => 'Racks');
	$subject[] = array ('q' => 'select avg(height) from Rack', 'txt' => 'Average rack height');
	$subject[] = array ('q' => 'select sum(height) from Rack', 'txt' => 'Total rack units in field');

	foreach ($subject as $item)
	{
		$result = Database::query ($item['q']);
		$row = $result->fetch (PDO::FETCH_NUM);
		$ret[$item['txt']] = empty ($row[0]) ? 0 : $row[0];
		Database::closeCursor($result);
		unset ($result);
	}
	return $ret;
}

function renderTagStats ()
{
	global $taglist, $root;
	$query = "select TagTree.id as id, tag, count(tag_id) as refcnt from " .
		"TagTree inner join TagStorage on TagTree.id = TagStorage.tag_id " .
		"group by tag_id order by refcnt desc limit 50";
	// The same data is already present in pre-loaded tag list, but not in
	// the form we need. So let's ask the DB server for cooked top list and
	// use the cooked tag list to break it down.
	$result = Database::query ($query);
	$refc = $result->fetchAll (PDO::FETCH_ASSOC);
	echo '<table border=1><tr><th>tag</th><th>total</th><th>objects</th><th>IPv4 nets</th><th>racks</th>';
	echo '<th>IPv4 VS</th><th>IPv4 RS pools</th><th>users</th><th>files</th></tr>';
	$pagebyrealm = array
	(
		'file' => 'filesbylink&entity_type=all',
		'ipv4net' => 'ipv4space&tab=default',
		'ipv4vs' => 'ipv4vslist&tab=default',
		'ipv4rspool' => 'ipv4rsplist&tab=default',
		'object' => 'objgroup&group_id=0',
		'rack' => 'rackspace&tab=default',
		'user' => 'userlist&tab=default'
	);
	foreach ($refc as $ref)
	{
		echo "<tr><td>${ref['tag']}</td><td>${ref['refcnt']}</td>";
		foreach (array ('object', 'ipv4net', 'rack', 'ipv4vs', 'ipv4rspool', 'user', 'file') as $realm)
		{
			echo '<td>';
			if (!isset ($taglist[$ref['id']]['refcnt'][$realm]))
				echo '&nbsp;';
			else
			{
				echo "<a href='${root}?page=" . $pagebyrealm[$realm] . "&tagfilter[]=${ref['id']}'>";
				echo $taglist[$ref['id']]['refcnt'][$realm] . '</a>';
			}
			echo '</td>';
		}
		echo '</tr>';
	}
	echo '</table>';
}

/*

The following allows figuring out records in TagStorage, which refer to non-existing entities:

mysql> select entity_id from TagStorage left join Files on entity_id = id where entity_realm = 'file' and id is null;
mysql> select entity_id from TagStorage left join IPv4Network on entity_id = id where entity_realm = 'ipv4net' and id is null;
mysql> select entity_id from TagStorage left join RackObject on entity_id = id where entity_realm = 'object' and id is null;
mysql> select entity_id from TagStorage left join Rack on entity_id = id where entity_realm = 'rack' and id is null;
mysql> select entity_id from TagStorage left join IPv4VS on entity_id = id where entity_realm = 'ipv4vs' and id is null;
mysql> select entity_id from TagStorage left join IPv4RSPool on entity_id = id where entity_realm = 'ipv4rspool' and id is null;
mysql> select entity_id from TagStorage left join UserAccount on entity_id = user_id where entity_realm = 'user' and user_id is null;

Accordingly, these are the records, which refer to non-existent tags:

mysql> select tag_id from TagStorage left join TagTree on tag_id = id where id is null;

*/

function commitUpdateDictionary ($chapter_no = 0, $dict_key = 0, $dict_value = '')
{
	if ($chapter_no <= 0 or $dict_key <= 0 or empty ($dict_value))
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::update(array('dict_value'=>$dict_value), 'Dictionary', $dict_key);
	return TRUE;
}

function commitSupplementDictionary ($chapter_no = 0, $dict_value = '')
{
	if ($chapter_no <= 0 or empty ($dict_value))
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::insert
	(
		array ('chapter_id' => $chapter_no, 'dict_value' => $dict_value),
		'Dictionary'
	);
	return TRUE;
}

function commitReduceDictionary ($chapter_no = 0, $dict_key = 0)
{
	if ($chapter_no <= 0 or $dict_key <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::delete('Dictionary', $dict_key);
	return TRUE;
}

function commitAddChapter ($chapter_name = '')
{
	if (empty ($chapter_name))
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	return Database::insert
	(
		array ('name' => $chapter_name),
		'Chapter'
	);
}

function commitUpdateChapter ($chapter_no = 0, $chapter_name = '')
{
	if ($chapter_no <= 0 or empty ($chapter_name))
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::update(array('name'=>$chapter_name), 'Chapter', $chapter_no);
	return TRUE;
}

function commitDeleteChapter ($chapter_no = 0)
{
	if ($chapter_no <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::delete('Chapter', $chapter_no);
	return TRUE;
}

// This is a dictionary accessor. We perform link rendering, so the user sees
// nice <select> drop-downs.
function readChapter ($chapter_name = '')
{
	if (empty ($chapter_name))
		throw ('invalid argument');
	$query =
		"select Dictionary.id as dict_key, dict_value from Dictionary join Chapter on Chapter.id = Dictionary.chapter_id " .
		"where Chapter.name = '${chapter_name}'";
	$result = Database::query ($query);
	$chapter = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$chapter[$row['dict_key']] = parseWikiLink ($row['dict_value'], 'o');
	Database::closeCursor($result);
	// SQL ORDER BY had no sense, because we need to sort after link rendering, not before.
	asort ($chapter);
	return $chapter;
}

function getAttrMap ()
{
	$query =
		"select a.id as attr_id, a.type as attr_type, a.name as attr_name, am.objtype_id, " .
		"d.dict_value as objtype_name, am.chapter_id, c2.name as chapter_name from " .
		"Attribute as a left join AttributeMap as am on a.id = am.attr_id " .
		"left join Dictionary as d on am.objtype_id = d.id " .
		"left join Chapter as c1 on d.chapter_id = c1.id " .
		"left join Chapter as c2 on am.chapter_id = c2.id " .
		"where c1.name = 'RackObjectType' or c1.name is null " .
		"order by a.name";
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$attr_id = $row['attr_id'];
		if (!isset ($ret[$attr_id]))
		{
			$ret[$attr_id]['id'] = $attr_id;
			$ret[$attr_id]['type'] = $row['attr_type'];
			$ret[$attr_id]['name'] = $row['attr_name'];
			$ret[$attr_id]['application'] = array();
		}
		if ($row['objtype_id'] == '')
			continue;
		$application['objtype_id'] = $row['objtype_id'];
		$application['objtype_name'] = $row['objtype_name'];
		if ($row['attr_type'] == 'dict')
		{
			$application['chapter_no'] = $row['chapter_id'];
			$application['chapter_name'] = $row['chapter_name'];
		}
		$ret[$attr_id]['application'][] = $application;
	}
	Database::closeCursor($result);
	return $ret;
}

function commitUpdateAttribute ($attr_id = 0, $attr_name = '')
{
	if ($attr_id <= 0 or empty ($attr_name))
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::update(array('name'=>$attr_name), 'Attribute', $attr_id);
	return TRUE;
}

function commitAddAttribute ($attr_name = '', $attr_type = '')
{
	if (empty ($attr_name))
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	switch ($attr_type)
	{
		case 'uint':
		case 'float':
		case 'string':
		case 'dict':
			break;
		default:
			showError ('Invalid args', __FUNCTION__);
			die;
	}
	Database::insert
	(
		array ('name' => $attr_name, 'type' => $attr_type),
		'Attribute'
	);
	return '';
}

function commitDeleteAttribute ($attr_id = 0)
{
	if ($attr_id <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::delete('Attribute', $attr_id);
	return '';
}

// FIXME: don't store garbage in chapter_no for non-dictionary types.
function commitSupplementAttrMap ($attr_id = 0, $objtype_id = 0, $chapter_no = 0)
{
	if ($attr_id <= 0 or $objtype_id <= 0 or $chapter_no <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::insert
	(
		array
		(
			'attr_id' => $attr_id,
			'objtype_id' => $objtype_id,
			'chapter_id' => $chapter_no
		),
		'AttributeMap'
	);
	return '';
}

function commitReduceAttrMap ($attr_id = 0, $objtype_id)
{
	if ($attr_id <= 0 or $objtype_id <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::deleteWhere('AttributeMap', array('attr_id'=>$attr_id, 'objtype_id'=>$objtype_id));
	return TRUE;
}

// This function returns all optional attributes for requested object
// as an array of records. NULL is returned on error and empty array
// is returned, if there are no attributes found.
function getAttrValues ($object_id, $strip_optgroup = FALSE)
{
	if ($object_id <= 0)
		throw new Exception('Invalid argument');
	$ret = array();
	$query =
		"select A.id as attr_id, A.name as attr_name, A.type as attr_type, C.name as chapter_name, " .
		"AV.uint_value, AV.float_value, AV.string_value, D.dict_value from " .
		"RackObject as RO inner join AttributeMap as AM on RO.objtype_id = AM.objtype_id " .
		"inner join Attribute as A on AM.attr_id = A.id " .
		"left join AttributeValue as AV on AV.attr_id = AM.attr_id and AV.object_id = RO.id " .
		"left join Dictionary as D on D.id = AV.uint_value and AM.chapter_id = D.chapter_id " .
		"left join Chapter as C on AM.chapter_id = C.id " .
		"where RO.id = ${object_id} order by A.type, A.name";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$record = array();
		$record['id'] = $row['attr_id'];
		$record['name'] = $row['attr_name'];
		$record['type'] = $row['attr_type'];
		switch ($row['attr_type'])
		{
			case 'uint':
			case 'float':
			case 'string':
				$record['value'] = $row[$row['attr_type'] . '_value'];
				$record['a_value'] = parseWikiLink ($record['value'], 'a');
				break;
			case 'dict':
				$record['value'] = parseWikiLink ($row[$row['attr_type'] . '_value'], 'o', $strip_optgroup);
				$record['a_value'] = parseWikiLink ($row[$row['attr_type'] . '_value'], 'a', $strip_optgroup);
				$record['chapter_name'] = $row['chapter_name'];
				$record['key'] = $row['uint_value'];
				break;
			default:
				$record['value'] = NULL;
				break;
		}
		$ret[$row['attr_id']] = $record;
	}
	Database::closeCursor($result);
	return $ret;
}

function commitResetAttrValue ($object_id = 0, $attr_id = 0)
{
	if ($object_id <= 0 or $attr_id <= 0)
	{
		showError ('Invalid arguments', __FUNCTION__);
		die;
	}
	Database::deleteWhere('AttributeValue', array('object_id'=>$object_id, 'attr_id'=>$attr_id));
	return TRUE;
}

// FIXME: don't share common code with use commitResetAttrValue()
function commitUpdateAttrValue ($object_id = 0, $attr_id = 0, $value = '')
{
	if ($object_id <= 0 or $attr_id <= 0)
		throw new Exception ('Invalid arguments');
	Database::inLifetime('Attribute', $attr_id);
	if (empty ($value))
		return commitResetAttrValue ($object_id, $attr_id);
	$result = Database::query("select type as attr_type from Attribute where id = ?", array(1=>$attr_id));
	$row = $result->fetch (PDO::FETCH_NUM);
	$attr_type = $row[0];
	Database::closeCursor($result);
	switch ($attr_type)
	{
		case 'uint':
		case 'float':
		case 'string':
			$column = $attr_type . '_value';
			break;
		case 'dict':
			$column = 'uint_value';
			break;
		default:
			showError ("Unknown attribute type '${attr_type}' met", __FUNCTION__);
			die;
	}
	Database::deleteWhere('AttributeValue', array('object_id'=>$object_id, 'attr_id'=>$attr_id));
	// We know $value isn't empty here.
	Database::insert(array($column=>$value, 'object_id'=>$object_id, 'attr_id'=>$attr_id), 'AttributeValue');
	return TRUE;
}

function commitUseupPort ($port_id = 0)
{
	if ($port_id <= 0)
	{
		showError ("Invalid argument", __FUNCTION__);
		die;
	}
	Database::update(array('reservation_comment'=>NULL), 'Port', $port_id);
	return TRUE;
	
}

function loadConfigCache ()
{
	$query = 'SELECT varname, varvalue, vartype, is_hidden, emptyok, description FROM Config ORDER BY varname';
	$result = Database::query ($query);
	$cache = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$cache[$row['varname']] = $row;
	Database::closeCursor($result);
	return $cache;
}

// setConfigVar() is expected to perform all necessary filtering
function storeConfigVar ($varname = NULL, $varvalue = NULL)
{
	if (empty ($varname) || $varvalue === NULL)
		throw new Exception ('Invalid arguments');
	$query = "update Config set varvalue='${varvalue}' where varname='${varname}' limit 1";
	$rc = Database::getDBLink()->exec($query);
	if ($rc == 0 or $rc == 1)
		return TRUE;
	throw new Exception ("Something went wrong for args '${varname}', '${varvalue}'");
}

// Database version detector. Should behave corretly on any
// working dataset a user might have.
function getDatabaseVersion ()
{
	$query = "select varvalue from Config where varname = 'DB_VERSION' and vartype = 'string'";
	$result = Database::getDBLink()->query ($query);
	if ($result == NULL)
	{
		$errorInfo = $dbxlink->errorInfo();
		if ($errorInfo[0] == '42S02') // ER_NO_SUCH_TABLE
			return '0.14.4';
		throw new Exception ('SQL query #1 failed with error ' . $errorInfo[2]);
	}
	$rows = $result->fetchAll (PDO::FETCH_NUM);
	if (count ($rows) != 1 || empty ($rows[0][0]))
	{
		Database::closeCursor($result);
		die (__FUNCTION__ . ': Cannot guess database version. Config table is present, but DB_VERSION is missing or invalid. Giving up.');
	}
	$ret = $rows[0][0];
	Database::closeCursor($result);
	return $ret;
}

// Return an array of virtual services. For each of them list real server pools
// with their load balancers and other stats.
function getSLBSummary ()
{
	$query = 'select vs.id as vsid, inet_ntoa(vip) as vip, vport, proto, vs.name, object_id, ' .
		'lb.rspool_id, pool.name as pool_name, count(rs.id) as rscount ' .
		'from IPv4VS as vs inner join IPv4LB as lb on vs.id = lb.vs_id ' .
		'inner join IPv4RSPool as pool on lb.rspool_id = pool.id ' .
		'left join IPv4RS as rs on rs.rspool_id = lb.rspool_id ' .
		'group by vs.id, object_id order by vs.vip, object_id';
	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$vsid = $row['vsid'];
		$object_id = $row['object_id'];
		if (!isset ($ret[$vsid]))
		{
			$ret[$vsid] = array();
			foreach (array ('vip', 'vport', 'proto', 'name') as $cname)
				$ret[$vsid][$cname] = $row[$cname];
			$ret[$vsid]['lblist'] = array();
		}
		// There's only one assigned RS pool possible for each LB-VS combination.
		$ret[$vsid]['lblist'][$row['object_id']] = array
		(
			'id' => $row['rspool_id'],
			'size' => $row['rscount'],
			'name' => $row['pool_name']
		);
	}
	Database::closeCursor($result);
	return $ret;
}

// Get the detailed composition of a particular virtual service, namely the list
// of all pools, each shown with the list of objects servicing it. VS/RS configs
// will be returned as well.
function getVServiceInfo ($vsid = 0)
{
	Database::inLifetime('IPv4VS', $vsid);
	$query1 = "select id, inet_ntoa(vip) as vip, vport, proto, name, vsconfig, rsconfig " .
		"from IPv4VS where id = ${vsid}";
	$result = Database::query ($query1);
	$vsinfo = array ();
	$row = $result->fetch (PDO::FETCH_ASSOC);
	foreach (array ('id', 'vip', 'vport', 'proto', 'name', 'vsconfig', 'rsconfig') as $cname)
		$vsinfo[$cname] = $row[$cname];
	$vsinfo['rspool'] = array();
	Database::closeCursor($result);
	unset ($result);
	$query2 = "select pool.id, name, pool.vsconfig, pool.rsconfig, object_id, " .
		"lb.vsconfig as lb_vsconfig, lb.rsconfig as lb_rsconfig from " .
		"IPv4RSPool as pool left join IPv4LB as lb on pool.id = lb.rspool_id " .
		"where vs_id = ${vsid} order by pool.name, object_id";
	$result = Database::query ($query2);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		if (!isset ($vsinfo['rspool'][$row['id']]))
		{
			$vsinfo['rspool'][$row['id']]['name'] = $row['name'];
			$vsinfo['rspool'][$row['id']]['vsconfig'] = $row['vsconfig'];
			$vsinfo['rspool'][$row['id']]['rsconfig'] = $row['rsconfig'];
			$vsinfo['rspool'][$row['id']]['lblist'] = array();
		}
		if ($row['object_id'] == NULL)
			continue;
		$vsinfo['rspool'][$row['id']]['lblist'][$row['object_id']] = array
		(
			'vsconfig' => $row['lb_vsconfig'],
			'rsconfig' => $row['lb_rsconfig']
		);
	}
	Database::closeCursor($result);
	return $vsinfo;
}

// Collect and return the following info about the given real server pool:
// basic information
// parent virtual service information
// load balancers list (each with a list of VSes)
// real servers list

function getRSPoolInfo ($id = 0)
{
	Database::inLifetime('IPv4RSPool', $id);
	$query1 = "select id, name, vsconfig, rsconfig from " .
		"IPv4RSPool where id = ${id}";
	$result = Database::query ($query1);
	$ret = array();
	$row = $result->fetch (PDO::FETCH_ASSOC);
	foreach (array ('id', 'name', 'vsconfig', 'rsconfig') as $c)
		$ret[$c] = $row[$c];
	Database::closeCursor($result);
	unset ($result);
	$ret['lblist'] = array();
	$ret['rslist'] = array();
	$query2 = "select object_id, vs_id, lb.vsconfig, lb.rsconfig from " .
		"IPv4LB as lb inner join IPv4VS as vs on lb.vs_id = vs.id " .
		"where rspool_id = ${id} order by object_id, vip, vport";
	$result = Database::query ($query2);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach (array ('vsconfig', 'rsconfig') as $c)
			$ret['lblist'][$row['object_id']][$row['vs_id']][$c] = $row[$c];
	Database::closeCursor($result);
	unset ($result);
	$query3 = "select id, inservice, inet_ntoa(rsip) as rsip, rsport, rsconfig from " .
		"IPv4RS where rspool_id = ${id} order by IPv4RS.rsip, rsport";
	$result = Database::query ($query3);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach (array ('inservice', 'rsip', 'rsport', 'rsconfig') as $c)
			$ret['rslist'][$row['id']][$c] = $row[$c];
	Database::closeCursor($result);
	return $ret;
}

function addRStoRSPool ($pool_id = 0, $rsip = '', $rsport = 0, $inservice = 'no', $rsconfig = '')
{
	if ($pool_id <= 0)
		throw new Exception ('Invalid arguments');
	if (empty ($rsport) or $rsport == 0)
		$rsport = NULL;
	Database::insert
	(
		array
		(
			'rsip' => array('left'=>'inet_aton(', 'value'=>$rsip, 'right'=>')'),
			'rsport' => $rsport,
			'rspool_id' => $pool_id,
			'inservice' => ($inservice == 'yes' ? 'yes' : 'no'),
			'rsconfig' => (empty ($rsconfig) ? NULL : $rsconfig)
		),
		'IPv4RS'
	);
	return TRUE;
}

function commitCreateVS ($vip = '', $vport = 0, $proto = '', $name = '', $vsconfig, $rsconfig, $taglist = array())
{
	if (empty ($vip) or $vport <= 0 or empty ($proto))
		return __FUNCTION__ . ': invalid arguments';
	$last_insert_id = Database::insert
	(
		array
		(
			'vip' => array('left'=>'inet_aton(', 'value'=>$vip, 'right'=>')'),
			'vport' => $vport,
			'proto' => $proto,
			'name' => (empty ($name) ? NULL : $name),
			'vsconfig' => (empty ($vsconfig) ? NULL : $vsconfig),
			'rsconfig' => (empty ($rsconfig) ? NULL : $rsconfig)
		),
		'IPv4VS'
	);
	return produceTagsForLastRecord ('ipv4vs', $taglist, $last_insert_id);
}

function addLBtoRSPool ($pool_id = 0, $object_id = 0, $vs_id = 0, $vsconfig = '', $rsconfig = '')
{
	if ($pool_id <= 0 or $object_id <= 0 or $vs_id <= 0)
	{
		throw new Exception ('Invalid arguments');
	}
	Database::insert
	(
		array
		(
			'object_id' => $object_id,
			'rspool_id' => $pool_id,
			'vs_id' => $vs_id,
			'vsconfig' => (empty ($vsconfig) ? NULL : $vsconfig),
			'rsconfig' => (empty ($rsconfig) ? NULL : $rsconfig)
		),
		'IPv4LB'
	);
	return TRUE;
}

function commitDeleteRS ($id = 0)
{
	if ($id <= 0)
		return FALSE;
	Database::delete('IPv4RS', $id);
	return '';
}

function commitDeleteVS ($id = 0)
{
	if ($id <= 0)
		return FALSE;
	Database::delete('IPv4VS', $id);
	return destroyTagsForEntity ('ipv4vs', $id);
}

function commitDeleteLB ($object_id = 0, $pool_id = 0, $vs_id = 0)
{
	if ($object_id <= 0 or $pool_id <= 0 or $vs_id <= 0)
		return FALSE;
	Database::deleteWhere('IPv4LB', array(
		'object_id'=>$object_id,
		'rspool_id'=>$pool_id,
		'vs_id'=>$vs_id));
	return TRUE;
}

function commitUpdateRS ($rsid = 0, $rsip = '', $rsport = 0, $rsconfig = '')
{
	if ($rsid <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	if (long2ip (ip2long ($rsip)) !== $rsip)
	{
		showError ("Invalid IP address '${rsip}'", __FUNCTION__);
		die;
	}
	if (empty ($rsport) or $rsport == 0)
		$rsport = NULL;
	Database::update(array(
		'rsip'=>array('left'=>'inet_aton(', 'value'=>$rsip, 'right'=>')'),
		'rsport'=>$rsport,
		'rsconfig'=>(empty ($rsconfig) ? NULL : $rsconfig)
		), 'IPv4RS', $rsid);

	return TRUE;
}

function commitUpdateLB ($object_id = 0, $pool_id = 0, $vs_id = 0, $vsconfig = '', $rsconfig = '')
{
	if ($object_id <= 0 or $pool_id <= 0 or $vs_id <= 0)
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::updateWhere(array(
		'vsconfig'=>(empty ($vsconfig) ? NULL : $vsconfig),
		'rsconfig'=>(empty ($rsconfig) ? NULL : $rsconfig),
		), 'IPv4LB', array (
		'object_id'=>$object_id,
		'rspool_id'=>$pool_id,
		'vs_id'=>$vs_id) );
	return TRUE;
}

function commitUpdateVS ($vsid = 0, $vip = '', $vport = 0, $proto = '', $name = '', $vsconfig = '', $rsconfig = '')
{
	if ($vsid <= 0 or empty ($vip) or $vport <= 0 or empty ($proto))
	{
		showError ('Invalid args', __FUNCTION__);
		die;
	}
	Database::update(array(
		'vip'=>array('left'=>'inet_aton(', 'value'=>$vip, 'right'=>')'),
		'vport'=>$vport,
		'proto'=>$proto,
		'name'=>(empty ($name) ? NULL : $name),
		'vsconfig'=>(empty ($vsconfig) ? NULL : $vsconfig),
		'rsconfig'=>(empty ($rsconfig) ? NULL : $rsconfig)
		), 'IPv4VS', $vsid);
	return TRUE;
}

// Return the list of virtual services, indexed by vs_id.
// Each record will be shown with its basic info plus RS pools counter.
function getVSList ($tagfilter = array(), $tfmode = 'any')
{
	$whereclause = getWhereClause ($tagfilter);
	$query = "select vs.id, inet_ntoa(vip) as vip, vport, proto, vs.name, vs.vsconfig, vs.rsconfig, count(rspool_id) as poolcount " .
		"from IPv4VS as vs left join IPv4LB as lb on vs.id = lb.vs_id " .
		"left join TagStorage on vs.id = TagStorage.entity_id and entity_realm = 'ipv4vs' " . 
		"where true ${whereclause} group by vs.id order by vs.vip, proto, vport";
	$result = Database::query ($query);
	$ret = array ();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach (array ('vip', 'vport', 'proto', 'name', 'vsconfig', 'rsconfig', 'poolcount') as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
	Database::closeCursor($result);
	return $ret;
}

// Return the list of RS pool, indexed by pool id.
function getRSPoolList ($tagfilter = array(), $tfmode = 'any')
{
	$whereclause = getWhereClause ($tagfilter);
	$query = "select pool.id, pool.name, count(rspool_id) as refcnt, pool.vsconfig, pool.rsconfig " .
		"from IPv4RSPool as pool left join IPv4LB as lb on pool.id = lb.rspool_id " .
		"left join TagStorage on pool.id = TagStorage.entity_id and entity_realm = 'ipv4rspool' " .
		"where true ${whereclause} group by pool.id order by pool.name, pool.id";
	$result = Database::query ($query);
	$ret = array ();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach (array ('name', 'refcnt', 'vsconfig', 'rsconfig') as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
	Database::closeCursor($result);
	return $ret;
}

function loadThumbCache ($rack_id = 0)
{
	$ret = NULL;
	$query = "select data from Registry where id = 'rackThumb_${rack_id}'";
	$result = Database::query ($query);
	$row = $result->fetch (PDO::FETCH_ASSOC);
	if ($row)
		$ret = base64_decode ($row['data']);
	Database::closeCursor($result);
	return $ret;
}

function saveThumbCache ($rack_id = 0, $cache = NULL)
{
	if ($rack_id == 0 or $cache == NULL)
		throw new Exception ('Invalid arguments');
	$data = base64_encode ($cache);
	$result = Database::query ("select count(*) from Registry where id='rackThumb_${rack_id}'");
	$row = $result->fetch ();
	if (isset($row[0]) and $row[0] > 0)
		Database::update(array('data'=>$data), 'Registry', "rackThumb_${rack_id}");
	else
		Database::insert(array('data'=>$data, 'id'=>"rackThumb_${rack_id}"), 'Registry');
}

function resetThumbCache ($rack_id = 0)
{
	Database::delete('Registry', "rackThumb_${rack_id}");
}

// Return the list of attached RS pools for the given object. As long as we have
// the LB-VS UNIQUE in IPv4LB table, it is Ok to key returned records
// by vs_id, because there will be only one RS pool listed for each VS of the
// current object.
function getRSPoolsForObject ($object_id = 0)
{
	if ($object_id <= 0)
		throw new Exception ('Invalid object_id');
	$query = 'select vs_id, inet_ntoa(vip) as vip, vport, proto, vs.name, pool.id as pool_id, ' .
		'pool.name as pool_name, count(rsip) as rscount, lb.vsconfig, lb.rsconfig from ' .
		'IPv4LB as lb inner join IPv4RSPool as pool on lb.rspool_id = pool.id ' .
		'inner join IPv4VS as vs on lb.vs_id = vs.id ' .
		'left join IPv4RS as rs on lb.rspool_id = rs.rspool_id ' .
		"where lb.object_id = ${object_id} " .
		'group by lb.rspool_id, lb.vs_id order by vs.vip, vport, proto, pool.name';
	$result = Database::query ($query);
	$ret = array ();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach (array ('vip', 'vport', 'proto', 'name', 'pool_id', 'pool_name', 'rscount', 'vsconfig', 'rsconfig') as $cname)
			$ret[$row['vs_id']][$cname] = $row[$cname];
	Database::closeCursor($result);
	return $ret;
}

function commitCreateRSPool ($name = '', $vsconfig = '', $rsconfig = '', $taglist = array())
{
	if (empty ($name))
		return __FUNCTION__ . ': invalid arguments';
	$insertid = Database::insert
	(
		array
		(
			'name' => (empty ($name) ? NULL : $name),
			'vsconfig' => (empty ($vsconfig) ? NULL : $vsconfig),
			'rsconfig' => (empty ($rsconfig) ? NULL : $rsconfig)
		),
		'IPv4RSPool'
	);
	return produceTagsForLastRecord ('ipv4rspool', $taglist, $insertid);
}

function commitDeleteRSPool ($pool_id = 0)
{
	if ($pool_id <= 0)
		return FALSE;
	Database::delete('IPv4RSPool', $pool_id);
	return destroyTagsForEntity ('ipv4rspool', $pool_id);
}

function commitUpdateRSPool ($pool_id = 0, $name = '', $vsconfig = '', $rsconfig = '')
{
	if ($pool_id <= 0)
	{
		showError ('Invalid arg', __FUNCTION__);
		die;
	}
	Database::update(array(
		'name'=>(empty ($name) ? NULL : $name),
		'vsconfig'=>(empty ($vsconfig) ? NULL : $vsconfig),
		'rsconfig'=>(empty ($rsconfig) ? NULL : $rsconfig)
		), 'IPv4RSPool', $pool_id);
	return TRUE;
}

function getRSList ()
{
	$query = "select id, inservice, inet_ntoa(rsip) as rsip, rsport, rspool_id, rsconfig " .
		"from IPv4RS order by rspool_id, IPv4RS.rsip, rsport";
	$result = Database::query ($query);
	$ret = array ();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		foreach (array ('inservice', 'rsip', 'rsport', 'rspool_id', 'rsconfig') as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
	Database::closeCursor($result);
	return $ret;
}

// Return the list of all currently configured load balancers with their pool count.
function getLBList ()
{
	$query = "select object_id, count(rspool_id) as poolcount " .
		"from IPv4LB group by object_id order by object_id";
	$result = Database::query ($query);
	$ret = array ();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[$row['object_id']] = $row['poolcount'];
	Database::closeCursor($result);
	return $ret;
}

// For the given object return: it vsconfig/rsconfig; the list of RS pools
// attached (each with vsconfig/rsconfig in turn), each with the list of
// virtual services terminating the pool. Each pool also lists all real
// servers with rsconfig.
function getSLBConfig ($object_id)
{
	if ($object_id <= 0)
		throw new Exception ('Invalid arg');
	$ret = array();
	$query = 'select vs_id, inet_ntoa(vip) as vip, vport, proto, vs.name as vs_name, ' .
		'vs.vsconfig as vs_vsconfig, vs.rsconfig as vs_rsconfig, ' .
		'lb.vsconfig as lb_vsconfig, lb.rsconfig as lb_rsconfig, pool.id as pool_id, pool.name as pool_name, ' .
		'pool.vsconfig as pool_vsconfig, pool.rsconfig as pool_rsconfig, ' .
		'rs.id as rs_id, inet_ntoa(rsip) as rsip, rsport, rs.rsconfig as rs_rsconfig from ' .
		'IPv4LB as lb inner join IPv4RSPool as pool on lb.rspool_id = pool.id ' .
		'inner join IPv4VS as vs on lb.vs_id = vs.id ' .
		'inner join IPv4RS as rs on lb.rspool_id = rs.rspool_id ' .
		"where lb.object_id = ${object_id} and rs.inservice = 'yes' " .
		"order by vs.vip, vport, proto, pool.name, rs.rsip, rs.rsport";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$vs_id = $row['vs_id'];
		if (!isset ($ret[$vs_id]))
		{
			foreach (array ('vip', 'vport', 'proto', 'vs_name', 'vs_vsconfig', 'vs_rsconfig', 'lb_vsconfig', 'lb_rsconfig', 'pool_vsconfig', 'pool_rsconfig', 'pool_id', 'pool_name') as $c)
				$ret[$vs_id][$c] = $row[$c];
			$ret[$vs_id]['rslist'] = array();
		}
		foreach (array ('rsip', 'rsport', 'rs_rsconfig') as $c)
			$ret[$vs_id]['rslist'][$row['rs_id']][$c] = $row[$c];
	}
	Database::closeCursor($result);
	return $ret;
}

function commitSetInService ($rs_id = 0, $inservice = '')
{
	if ($rs_id <= 0 or empty ($inservice))
		throw new Exception ('Invalid args');
	Database::update(array('inservice'=>$inservice), 'IPv4RS', $rs_id);
	return TRUE;
}

function executeAutoPorts ($object_id = 0, $type_id = 0)
{
	if ($object_id == 0 or $type_id == 0)
	{
		showError ('Invalid arguments', __FUNCTION__);
		die;
	}
	$ret = TRUE;
	foreach (getAutoPorts ($type_id) as $autoport)
		$ret = $ret and '' == commitAddPort ($object_id, $autoport['name'], $autoport['type'], '', '');
	return $ret;
}

// Return only implicitly listed tags, the rest of the chain will be
// generated/deducted later at higher levels.
// Result is a chain: randomly indexed taginfo list.
function loadEntityTags ($entity_realm = '', $entity_id = 0)
{
	if ($entity_realm == '' or $entity_id <= 0)
		throw new Exception ('Invalid or missing arguments');
	$ret = array();
	if (!in_array ($entity_realm, array ('file', 'ipv4net', 'ipv4vs', 'ipv4rspool', 'object', 'rack', 'user')))
		return $ret;
	$query = "select tt.id, tag from " .
		"TagStorage as ts inner join TagTree as tt on ts.tag_id = tt.id " .
		"where entity_realm = '${entity_realm}' and entity_id = ${entity_id} " .
		"order by tt.tag";
	$result = Database::query ($query);
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[$row['id']] = $row;
	Database::closeCursor($result);
	return getExplicitTagsOnly ($ret);
}

// Return a tag chain with all DB tags on it.
function getTagList ()
{
	$ret = array();
	$query = "select TagTree.id, parent_id, tag, entity_realm as realm, count(entity_id) as refcnt " .
		"from TagTree left join TagStorage on TagTree.id = tag_id " .
		"group by id, entity_realm order by tag";
	$result = Database::query ($query);
	$ci = 0; // Collation index. The resulting rows are ordered according to default collation,
	// which is utf8_general_ci for UTF-8.
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		if (!isset ($ret[$row['id']]))
			$ret[$row['id']] = array
			(
				'id' => $row['id'],
				'tag' => $row['tag'],
				'ci' => $ci++,
				'parent_id' => $row['parent_id'],
				'refcnt' => array()
			);
		if ($row['realm'])
			$ret[$row['id']]['refcnt'][$row['realm']] = $row['refcnt'];
	}
	Database::closeCursor($result);
	return $ret;
}

function commitCreateTag ($tagname = '', $parent_id = 0)
{
	if ($tagname == '' or $parent_id === 0)
		return "Invalid args to " . __FUNCTION__;
	Database::insert
	(
		array
		(
			'tag' => $tagname,
			'parent_id' => $parent_id
		),
		'TagTree'
	);
	return '';
}

function commitDestroyTag ($tagid = 0)
{
	if ($tagid == 0)
		return 'Invalid arg to ' . __FUNCTION__;
	Database::delete('TagTree', $tagid);
	return '';
}

function commitUpdateTag ($tag_id, $tag_name, $parent_id)
{
	if ($parent_id == 0)
		$parent_id = NULL;
	Database::update(array('tag'=>$tag_name, 'parent_id'=>$parent_id), 'TagTree', $tag_id);
	return '';
}
function getTagsForEntity($entity_realm, $entity_id)
{
	$result = Database::query("select TagStorage.tag_id as id, TagTree.tag as tag from TagStorage join TagTree on TagStorage.tag_id = TagTree.id where TagStorage.entity_realm = ? and TagStorage.entity_id = ?", array(1=>$entity_realm, 2=>$entity_id));
	$ret = array();
	while($row = $result->fetch())
	{
		$ret[$row['id']] = $row;
	}
	Database::closeCursor($result);
	return $ret;
}
// Drop the whole chain stored.
function destroyTagsForEntity ($entity_realm, $entity_id)
{
	Database::deleteWhere('TagStorage', array('entity_realm'=>$entity_realm, 'entity_id'=>$entity_id));
	return TRUE;
}

// Drop only one record. This operation doesn't involve retossing other tags, unlike when adding.
function deleteTagForEntity ($entity_realm, $entity_id, $tag_id)
{
	Database::deleteWhere('TagStorage', array('entity_realm'=>$entity_realm, 'entity_id'=>$entity_id, 'tag_id'=>$tag_id));
	return TRUE;
}

// Push a record into TagStorage unconditionally.
function addTagForEntity ($realm = '', $entity_id, $tag_id)
{
	if (empty ($realm))
		return FALSE;
	Database::insert
	(
		array
		(
			'entity_realm' => $realm,
			'entity_id' => $entity_id,
			'tag_id' => $tag_id,
		),
		'TagStorage'
	);
	return TRUE;
}

// Add records into TagStorage, if this makes sense (IOW, they don't appear
// on the implicit list already). Then remove any other records, which
// appear on the "implicit" side of the chain. This will make sure,
// that both the tag base is still minimal and all requested tags appear on
// the resulting tag chain.
// Return TRUE, if any changes were committed.
function rebuildTagChainForEntity ($realm, $entity_id, $extrachain = array())
{
	// Put the current explicit sub-chain into a buffer and merge all tags from
	// the extra chain, which aren't there yet.
	$newchain = $oldchain = loadEntityTags ($realm, $entity_id);
	foreach ($extrachain as $extratag)
		if (!tagOnChain ($extratag, $newchain))
			$newchain[] = $extratag;
	// Then minimize the working buffer and check if it differs from the original
	// chain we started with. If it is so, save the work and signal the upper layer.
	$newchain = getExplicitTagsOnly ($newchain);
	if (tagChainCmp ($oldchain, $newchain))
	{
		destroyTagsForEntity ($realm, $entity_id);
		foreach ($newchain as $taginfo)
			addTagForEntity ($realm, $entity_id, $taginfo['id']);
		return TRUE;
	}
	return FALSE;
}

// Presume, that the target record has no tags attached.
function produceTagsForLastRecord ($realm, $tagidlist, $last_insert_id = 0)
{
	if (!count ($tagidlist))
		return '';
	if (!$last_insert_id)
		return 'Didn\'t get last insert ID';
	$errcount = 0;
	foreach (getExplicitTagsOnly (buildTagChainFromIds ($tagidlist)) as $taginfo)
		if (addTagForEntity ($realm, $last_insert_id, $taginfo['id']) == FALSE)
			$errcount++;	
	if (!$errcount)
		return '';
	else
		return "Experienced ${errcount} errors adding tags in realm '${realm}' for entity ID == ${last_insert_id}";
}

function createIPv4Prefix ($range = '', $name = '', $is_bcast = FALSE, $taglist = array())
{
	// $range is in x.x.x.x/x format, split into ip/mask vars
	$rangeArray = explode('/', $range);
	if (count ($rangeArray) != 2)
		return "Invalid IPv4 prefix '${range}'";
	$ip = $rangeArray[0];
	$mask = $rangeArray[1];

	if (empty ($ip) or empty ($mask))
		return "Invalid IPv4 prefix '${range}'";
	$ipL = ip2long($ip);
	$maskL = ip2long($mask);
	if ($ipL == -1 || $ipL === FALSE)
		return 'Bad IPv4 address';
	if ($mask < 32 && $mask > 0)
		$maskL = $mask;
	else
	{
		$maskB = decbin($maskL);
		if (strlen($maskB)!=32)
			return 'Invalid netmask';
		$ones=0;
		$zeroes=FALSE;
		foreach( str_split ($maskB) as $digit)
		{
			if ($digit == '0')
				$zeroes = TRUE;
			if ($digit == '1')
			{
				$ones++;
				if ($zeroes == TRUE)
					return 'Invalid netmask';
			}
		}
		$maskL = $ones;
	}
	$binmask = binMaskFromDec($maskL);
	$ipL = $ipL & $binmask;
	$insertid = Database::insert
	(
		array
		(
			'ip' => sprintf ('%u', $ipL),
			'mask' => $maskL,
			'name' => $name
		),
		'IPv4Network'
	);

	if ($is_bcast and $maskL < 31)
	{
		$network_addr = long2ip ($ipL);
		$broadcast_addr = long2ip ($ipL | binInvMaskFromDec ($maskL));
		updateAddress ($network_addr, 'network', 'yes');
		updateAddress ($broadcast_addr, 'broadcast', 'yes');
	}
	return produceTagsForLastRecord ('ipv4net', $taglist, $insertid);
}

// FIXME: This function doesn't wipe relevant records from IPv4Address table.
function destroyIPv4Prefix ($id = 0)
{
	if ($id <= 0)
		return __FUNCTION__ . ': Invalid IPv4 prefix ID';
	Database::delete('IPv4Network', $id);
	if (!destroyTagsForEntity ('ipv4net', $id))
		return __FUNCTION__ . ': SQL query #2 failed';
	return '';
}

function loadScript ($name)
{
	$result = Database::query ("select script_text from Script where script_name = '${name}'");
	$row = $result->fetch (PDO::FETCH_NUM);
	if ($row !== FALSE)
		return $row[0];
	else
		return NULL;
}

function saveScript ($name, $text)
{
	if (empty ($name))
	{
		showError ('Invalid argument');
		return FALSE;
	}
	$q = Database::getDBLink()->prepare("replace into Script set script_name = ? , script_text = ?");
	$q->bindValue(1, $name);
	$q->bindValue(2, $text);
	$q->execute();
	return '';
}

function saveUserPassword ($user_id, $newp)
{
	$newhash = hash (PASSWORD_HASH, $newp);
	$q = Database::getDBLink()->prepare("update UserAccount set user_password_hash = ? where user_id = ?");
	$q->bindValue(1, $newhash);
	$q->bindValue(2, $user_id);
	$q->execute();
}

function objectIsPortless ($id = 0)
{
	if ($id <= 0)
		throw new Exception ('Invalid argument');
	$result = Database::query ("select count(id) from Port where object_id = ${id}"); 
	$row = $result->fetch (PDO::FETCH_NUM);
	$count = $row[0];
	Database::closeCursor($result);
	unset ($result);
	return $count === '0';
}

function recordExists ($id = 0, $realm = 'object')
{
	if ($id <= 0)
		return FALSE;
	$table = array
	(
		'object' => 'RackObject',
		'ipv4net' => 'IPv4Network',
		'user' => 'UserAccount',
	);
	$idcol = array
	(
		'object' => 'id',
		'ipv4net' => 'id',
		'user' => 'user_id',
	);
	$query = 'select count(*) from ' . $table[$realm] . ' where ' . $idcol[$realm] . ' = ' . $id;
	if (($result = Database::query ($query)) == NULL) 
	{
		showError ('SQL query failed', __FUNCTION__);
		return FALSE;
	}
	$row = $result->fetch (PDO::FETCH_NUM);
	$count = $row[0];
	Database::closeCursor($result);
	unset ($result);
	return $count === '1';
}

function tagExistsInDatabase ($tname)
{
	$result = Database::query ("select count(*) from TagTree where lower(tag) = lower('${tname}')");
	$row = $result->fetch (PDO::FETCH_NUM);
	$count = $row[0];
	Database::closeCursor($result);
	unset ($result);
	return $count !== '0';
}

function newPortForwarding ($object_id, $localip, $localport, $remoteip, $remoteport, $proto, $description)
{
	if (NULL === getIPv4AddressNetworkId ($localip))
		return "$localip: Non existant ip";
	if (NULL === getIPv4AddressNetworkId ($localip))
		return "$remoteip: Non existant ip";
	if ( ($localport <= 0) or ($localport >= 65536) )
		return "$localport: invaild port";
	if ( ($remoteport <= 0) or ($remoteport >= 65536) )
		return "$remoteport: invaild port";
	Database::insert
	(
		array
		(
			'object_id' => $object_id,
			'localip' => array('left'=>'INET_ATON(', 'value'=>$localip, 'right'=>')'),
			'remoteip' => array('left'=>'INET_ATON(', 'value'=>$remoteip, 'right'=>')'),
			'localport' => $localport,
			'remoteport' => $remoteport,
			'proto' => $proto,
			'description' => $description,
		),
		'IPv4NAT'
	);
	return '';
}

function deletePortForwarding ($object_id, $localip, $localport, $remoteip, $remoteport, $proto)
{
	Database::deleteWhere('IPv4NAT', array(
		'object_id'=>$object_id,
		'localip'=>array('left'=>'INET_ATON(', 'value'=>$localip, 'right'=>')'),
		'remoteip'=>array('left'=>'INET_ATON(', 'value'=>$remoteip, 'right'=>')'),
		'localport'=>$localport,
		'remoteport'=>$remoteport,
		'proto'=>$proto ));
	return '';
}

function updatePortForwarding ($object_id, $localip, $localport, $remoteip, $remoteport, $proto, $description)
{
	Database::update(array('description'=>$description), 'IPv4NAT', array(
		'object_id'=>$object_id,
		'localip'=>array('left'=>'INET_ATON(', 'value'=>$localip, 'right'=>')'),
		'remoteip'=>array('left'=>'INET_ATON(', 'value'=>$remoteip, 'right'=>')'),
		'localport'=>$localport,
		'remoteport'=>$remoteport,
		'proto'=>$proto ));

	return '';
}

function getNATv4ForObject ($object_id)
{
	$ret = array();
	$ret['out'] = array();
	$ret['in'] = array();
	$query =
		"select ".
		"proto, ".
		"INET_NTOA(localip) as localip, ".
		"localport, ".
		"INET_NTOA(remoteip) as remoteip, ".
		"remoteport, ".
		"ipa1.name as local_addr_name, " .
		"ipa2.name as remote_addr_name, " .
		"description ".
		"from IPv4NAT ".
		"left join IPv4Address as ipa1 on IPv4NAT.localip = ipa1.ip " .
		"left join IPv4Address as ipa2 on IPv4NAT.remoteip = ipa2.ip " .
		"where object_id='$object_id' ".
		"order by localip, localport, proto, remoteip, remoteport";
	$result = Database::query ($query);
	$count=0;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		foreach (array ('proto', 'localport', 'localip', 'remoteport', 'remoteip', 'description', 'local_addr_name', 'remote_addr_name') as $cname)
			$ret['out'][$count][$cname] = $row[$cname];
		$count++;
	}
	Database::closeCursor($result);
	unset ($result);

	$query =
		"select ".
		"proto, ".
		"INET_NTOA(localip) as localip, ".
		"localport, ".
		"INET_NTOA(remoteip) as remoteip, ".
		"remoteport, ".
		"IPv4NAT.object_id as object_id, ".
		"RackObject.name as object_name, ".
		"description ".
		"from ((IPv4NAT join IPv4Allocation on remoteip=IPv4Allocation.ip) join RackObject on IPv4NAT.object_id=RackObject.id) ".
		"where IPv4Allocation.object_id='$object_id' ".
		"order by remoteip, remoteport, proto, localip, localport";
	$result = Database::query ($query);
	$count=0;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		foreach (array ('proto', 'localport', 'localip', 'remoteport', 'remoteip', 'object_id', 'object_name', 'description') as $cname)
			$ret['in'][$count][$cname] = $row[$cname];
		$count++;
	}
	Database::closeCursor($result);

	return $ret;
}

// This function performs search and then calculates score for each result.
// Given previous search results in $objects argument, it adds new results
// to the array and updates score for existing results, if it is greater than
// existing score.
function mergeSearchResults (&$objects, $terms, $fieldname)
{
	$query =
		"select ro.name, label, asset_no, barcode, ro.id, Dictionary.id as objtype_id, " .
		"dict_value as objtype_name, asset_no from RackObject as ro inner join Dictionary " .
		"on objtype_id = Dictionary.id join Chapter on Chapter.id = Dictionary.chapter_id where Chapter.name = 'RackObjectType' and ";
	$count = 0;
	foreach (explode (' ', $terms) as $term)
	{
		if ($count) $query .= ' or ';
		$query .= "ro.${fieldname} like '%$term%'";
		$count++;
	}
	$query .= " order by ${fieldname}";
	$result = Database::query ($query);
// FIXME: this dead call was executed 4 times per 1 object search!
//	$typeList = getObjectTypeList();
	$clist = array ('id', 'name', 'label', 'asset_no', 'barcode', 'objtype_id', 'objtype_name');
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		foreach ($clist as $cname)
			$object[$cname] = $row[$cname];
		$object['score'] = 0;
		$object['dname'] = displayedName ($object);
		unset ($object['objtype_id']);
		foreach (explode (' ', $terms) as $term)
			if (strstr ($object['name'], $term))
				$object['score'] += 1;
		unset ($object['name']);
		if (!isset ($objects[$row['id']]))
			$objects[$row['id']] = $object;
		elseif ($objects[$row['id']]['score'] < $object['score'])
			$objects[$row['id']]['score'] = $object['score'];
	}
	return $objects;
}

function getLostIPv4Addresses ()
{
	dragon();
}

// File-related functions
function getAllFiles ()
{
	$query = "SELECT id, name, type, size, atime, comment FROM File ORDER BY name";
	$result = Database::query ($query);
	$ret=array();
	$count=0;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		$ret[$count]['id'] = $row['id'];
		$ret[$count]['name'] = $row['name'];
		$ret[$count]['type'] = $row['type'];
		$ret[$count]['size'] = $row['size'];
		$head = Database::getRevisionById(Database::getHeadRevisionForObject('File', $row['id']));
		$ret[$count]['mtime'] = strftime('%F %T', $head['timestamp']);
		$tail = Database::getRevisionById(Database::getTailRevisionForObject('File', $row['id']));
		$ret[$count]['ctime'] = strftime('%F %T', $tail['timestamp']);
		$ret[$count]['atime'] = $row['atime'];
		$ret[$count]['comment'] = $row['comment'];
		$count++;
	}
	Database::closeCursor($result);
	return $ret;
}

// Return a list of files which are not linked to the specified record. This list
// will be used by printSelect().
function getAllUnlinkedFiles ($entity_type = NULL, $entity_id = 0)
{
	if ($entity_type == NULL || $entity_id == 0)
		throw new Exception ('Invalid parameters');
	$sql =
		'SELECT File.id as id, name FROM File ' .
		'left join FileLink on File.id = FileLink.file_id '.
		'and entity_type = ? and entity_id = ? '.
		'WHERE FileLink.id is null ' .
		'ORDER BY name, id';
	$query = Database::query($sql, array(1=>$entity_type, 2=>$entity_id));
	$ret=array();
	while ($row = $query->fetch (PDO::FETCH_ASSOC))
		$ret[$row['id']] = $row['name'];
	return $ret;
}

// Return a filtered, detailed file list.  Used on the main Files listing page.
function getFileList ($entity_type = NULL, $tagfilter = array(), $tfmode = 'any')
{
	$whereclause = getWhereClause ($tagfilter);

	if ($entity_type == 'no_links')
		$whereclause .= 'AND FileLink.id is null ';
	elseif ($entity_type != 'all')
		$whereclause .= "AND entity_type = '${entity_type}' ";

	$query =
		'SELECT File.id, name, type, size, atime, comment ' .
		'FROM File ' .
		'LEFT JOIN FileLink ' .
		'ON File.id = FileLink.file_id ' .
		'LEFT JOIN TagStorage ' .
		"ON File.id = TagStorage.entity_id AND entity_realm = 'file' " .
		'WHERE size >= 0 ' .
		$whereclause .
		'ORDER BY name';

	$result = Database::query ($query);
	$ret = array();
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
	{
		foreach (array (
			'id',
			'name',
			'type',
			'size',
			'atime',
			'comment'
			) as $cname)
			$ret[$row['id']][$cname] = $row[$cname];
		$head = Database::getRevisionById(Database::getHeadRevisionForObject('File', $row['id']));
		$tail = Database::getRevisionById(Database::getTailRevisionForObject('File', $row['id']));

		$ret[$row['id']]['mtime'] = strftime('%F %T', $head['timestamp']);
		$ret[$row['id']]['ctime'] = strftime('%F %T', $tail['timestamp']);
	}
	
	Database::closeCursor($result);
	return $ret;
}

function getFilesOfEntity ($entity_type = NULL, $entity_id = 0)
{
	if ($entity_type == NULL || $entity_id == 0)
		throw new Exception ('Invalid parameters');
	$sql =
		'SELECT FileLink.file_id, FileLink.id AS link_id, name, type, size, atime, comment ' .
		'FROM FileLink LEFT JOIN File ON FileLink.file_id = File.id ' .
		'WHERE FileLink.entity_type = ? AND FileLink.entity_id = ? ORDER BY name';
	$query  = Database::query($sql, array(1=>$entity_type, 2=>$entity_id));
	$ret = array();
	while ($row = $query->fetch (PDO::FETCH_ASSOC))
	{
		$head = Database::getRevisionById(Database::getHeadRevisionForObject('File', $row['file_id']));
		$tail = Database::getRevisionById(Database::getTailRevisionForObject('File', $row['file_id']));

		$ret[$row['file_id']] = array (
			'id' => $row['file_id'],
			'link_id' => $row['link_id'],
			'name' => $row['name'],
			'type' => $row['type'],
			'size' => $row['size'],
			'ctime' => strftime('%F %T', $tail['timestamp']),
			'mtime' => strftime('%F %T', $head['timestamp']),
			'atime' => $row['atime'],
			'comment' => $row['comment'],
		);
	}
	return $ret;
}

function getFile ($file_id = 0)
{
	if ($file_id == 0)
		throw new Exception ('Invalid file_id');
	Database::inLifetime('File', $file_id);
	$query = Database::query('SELECT * FROM File WHERE id = ?', array(1=>$file_id));
	$row = $query->fetch (PDO::FETCH_ASSOC);
	$ret = array();
	$ret['id'] = $row['id'];
	$ret['name'] = $row['name'];
	$ret['type'] = $row['type'];
	$ret['size'] = $row['size'];
	$head = Database::getRevisionById(Database::getHeadRevisionForObject('File', $row['id']));
	$tail = Database::getRevisionById(Database::getTailRevisionForObject('File', $row['id']));
	$ret['ctime'] = strftime('%F %T', $tail['timestamp']);
	$ret['mtime'] = strftime('%F %T', $head['timestamp']);
	$ret['atime'] = $row['atime'];
	$ret['contents'] = $row['contents'];
	$ret['comment'] = $row['comment'];
	Database::closeCursor($query);

	// Someone accessed this file, update atime
	Database::update(array('atime'=>date('YmdHis')), 'File', $file_id);
	return $ret;
}

function getFileInfo ($file_id = 0)
{
	if ($file_id == 0)
		throw new Exception ('Invalid file_id');
	Database::inLifetime('File', $file_id);
	$query = Database::query('SELECT id, name, type, size, atime, comment FROM File WHERE id = ?', array(1=>$file_id));
	$row = $query->fetch (PDO::FETCH_ASSOC);
	$ret = array();
	$ret['id'] = $row['id'];
	$ret['name'] = $row['name'];
	$ret['type'] = $row['type'];
	$ret['size'] = $row['size'];
	$head = Database::getRevisionById(Database::getHeadRevisionForObject('File', $row['id']));
	$tail = Database::getRevisionById(Database::getTailRevisionForObject('File', $row['id']));
	$ret['ctime'] = strftime('%F %T', $tail['timestamp']);
	$ret['mtime'] = strftime('%F %T', $head['timestamp']);
	$ret['atime'] = $row['atime'];
	$ret['comment'] = $row['comment'];
	Database::closeCursor($query);
	return $ret;
}

function getFileLinks ($file_id = 0)
{
	if ($file_id <= 0)
		throw new Exception ('Invalid file_id');

	$query = Database::query('SELECT * FROM FileLink WHERE file_id = ? ORDER BY entity_id', array(1=>$file_id));
	$rows = $query->fetchAll (PDO::FETCH_ASSOC);
	$ret = array();
	foreach ($rows as $row)
	{
		// get info of the parent
		switch ($row['entity_type'])
		{
			case 'ipv4net':
				$page = 'ipv4net';
				$id_name = 'id';
				$parent = getIPv4NetworkInfo($row['entity_id']);
				$name = sprintf("%s (%s/%s)", $parent['name'], $parent['ip'], $parent['mask']);
				break;
			case 'ipv4rspool':
				$page = 'ipv4rspool';
				$id_name = 'pool_id';
				$parent = getRSPoolInfo($row['entity_id']);
				$name = $parent['name'];
				break;
			case 'ipv4vs':
				$page = 'ipv4vs';
				$id_name = 'vs_id';
				$parent = getVServiceInfo($row['entity_id']);
				$name = $parent['name'];
				break;
			case 'object':
				$page = 'object';
				$id_name = 'object_id';
				$parent = getObjectInfo($row['entity_id']);
				$name = $parent['dname'];
				break;
			case 'rack':
				$page = 'rack';
				$id_name = 'rack_id';
				$parent = getRackData($row['entity_id']);
				$name = $parent['name'];
				break;
			case 'user':
				$page = 'user';
				$id_name = 'user_id';
				global $accounts;
				foreach ($accounts as $account)
					if ($account['user_id'] == $row['entity_id'])
						$name = $account['user_name'];
				break;
		}

		// name needs to have some value for hrefs to work
        if (empty($name))
			$name = sprintf("[Unnamed %s]", formatEntityName($row['entity_type']));

		$ret[$row['id']] = array(
				'page' => $page,
				'id_name' => $id_name,
				'entity_type' => $row['entity_type'],
				'entity_id' => $row['entity_id'],
				'name' => $name
		);
	}
	return $ret;
}

// Return list of possible file parents along with the number of children.
// Used on main Files listing page.
function getFileLinkInfo ()
{
	$query = 'SELECT entity_type, COUNT(*) AS count FROM FileLink GROUP BY entity_type';

	$result = Database::query ($query);
	$ret = array();
	$ret[0] = array ('entity_type' => 'all', 'name' => 'ALL files');
	$clist = array ('entity_type', 'name', 'count');
	$total = 0;
	$i=2;
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		if ($row['count'] > 0)
		{
			$total += $row['count'];
			$row['name'] = formatEntityName ($row['entity_type']);
			foreach ($clist as $cname)
				$ret[$i][$cname] = $row[$cname];
				$i++;
		}
	Database::closeCursor($result);

	// Find number of files without any linkage
	$linkless_sql =
		'SELECT COUNT(*) ' .
		'FROM File LEFT JOIN FileLink on File.id = FileLink.file_id ' .
		'WHERE FileLink.id is null';
	$q_linkless = Database::query ($linkless_sql);
	$ret[1] = array ('entity_type' => 'no_links', 'name' => 'Files w/no links', 'count' => $q_linkless->fetchColumn ());
	Database::closeCursor($q_linkless);

	// Find total number of files
	$total_sql = 'SELECT COUNT(*) FROM File';
	$q_total = Database::query ($total_sql);
	$ret[0]['count'] = $q_total->fetchColumn ();
	Database::closeCursor($q_total);

	ksort($ret);
	return $ret;
}

function commitAddFile ($name, $type, $size, $contents, $comment)
{
	$now = date('YmdHis');
	$fileContent = file_get_contents($contents);
	$query  = Database::insert(array(
		'name'=>$name,
		'type'=>$type,
		'size'=>$size,
		'atime'=>$now,
		'contents'=>$fileContent,
		'comment'=>$comment), 'File');
	return '';
}

function commitLinkFile ($file_id, $entity_type, $entity_id)
{
	Database::insert(array('file_id'=>$file_id, 'entity_type'=>$entity_type, 'entity_id'=>$entity_id), 'FileLink');
	return '';
}

function commitReplaceFile ($file_id = 0, $contents)
{
	if ($file_id == 0)
	{
		showError ('Not all required args are present.', __FUNCTION__);
		return FALSE;
	}
	$fileContent = file_get_contents($contents);
	if ($fileContent)
	{
		$size = strlen($fileContent);
		Database::update(array('size'=>$size, 'contents'=>$fileContent), 'File', $file_id);
	}
	return '';
}

function commitUpdateFile ($file_id = 0, $new_name = '', $new_type = '', $new_comment = '')
{
	if ($file_id <= 0 or empty ($new_name) or empty ($new_type))
	{
		showError ('Not all required args are present.', __FUNCTION__);
		return FALSE;
	}
	Database::update(array('name'=>$new_name, 'type'=>$new_type, 'comment'=>$new_comment), 'File', $file_id);
	return '';
}

function commitUnlinkFile ($link_id)
{
	Database::delete('FileLink', $link_id);
	return '';
}

function commitDeleteFile ($file_id)
{
	Database::delete('FileLink', $file_id);
	return '';
}

function getChapterList ()
{
	$ret = array();
	$result = Database::query ('select id as chapter_no, name as chapter_name from Chapter order by Chapter.name');
	while ($row = $result->fetch (PDO::FETCH_ASSOC))
		$ret[$row['chapter_no']] = $row['chapter_name'];
	return $ret;
}

function makeMainHistory($start_rev, $end_rev)
{
	$ops = Operation::getOperationsSince($start_rev);
	$operations = array();
	foreach($ops as $op)
	{
		if ($op['rev']>$end_rev)
			break;
		$op['hr_timestamp'] = date('d/m/Y H:i:s', $op['timestamp']);
		$operations[$op['rev']] = $op;
	}
	list($byTable, $mainHistory) = Database::getMainHistory($start_rev, $end_rev);
	$rev_found_so_far = array();
	$prev_cache = array();
	for ($revision = $start_rev; $revision <= $end_rev; $revision++)
	{
		if (!array_key_exists($revision, $mainHistory))
			continue;
		$records = $mainHistory[$revision];
		foreach($records as $record)
		{
			$rev_found_so_far[] = $record;
			if (isset($prev_cache[$record['table'].'_'.$record['id']]))
			{
				$record['diff'] = array_diff_assoc($prev_cache[$record['table'].'_'.$record['id']], $record);
				$prev_cache[$record['table'].'_'.$record['id']] = $record;
			}
		}
		if (isset($operations[$revision]))
		{
			$operations[$revision]['records'] = $rev_found_so_far;
			$rev_found_so_far = array();
		}

	}
	return $operations;
}

// Return file id by file name.
function findFileByName ($filename)
{
	$query = Database::query('SELECT id FROM File WHERE name = ?', array(1=>$filename));
	if (($row = $query->fetch (PDO::FETCH_ASSOC)))
		return $row['id'];

	return NULL;
}
?>
