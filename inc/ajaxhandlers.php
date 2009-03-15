<?php

function verifyRackCode()
{
	$code = str_replace ('\r', '', str_replace ('\n', "\n", $_REQUEST['code']));
	$result = getRackCode($code);
	if ($result['result'] == 'ACK')
		return 'ACK';
	else
		return "NAK\n".$result['load'];
}

function getObjectsEmptyPorts()
{
	$ret = "ACK\n";
	$objects = getObjectsEmptyPortsOfType($_REQUEST['type']);
	foreach($objects as $object)
	{
		$ret .= displayedName($object)."\t".$object['id']."\n";
	}
	return $ret;
}

function getEmptyPorts()
{
	$ret = "ACK\n";
	$ports = getEmptyPortsOfTypeForObject($_REQUEST['object_id'], $_REQUEST['type']);
	foreach($ports as $port)
		$ret .= $port['name']."\t".$port['id']."\n";
	return $ret;
}



?>
