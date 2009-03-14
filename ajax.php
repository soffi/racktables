<?php
require_once 'inc/exception.php';
ob_start();
try {
	require 'inc/init.php';
	fixContext();

	if (empty($op) or !isset($ajaxhandler[$pageno][$tabno][$op]))
	{
		throw new Exception ("Invalid request in ajax broker: page '${pageno}', tab '${tabno}', op '${op}'");
	}

	// We have a chance to handle an error before starting HTTP header.
	if (!permitted())
	{
		echo "NAK\nPermission denied";
		exit();
	}
	else
	{
		echo $ajaxhandler[$pageno][$tabno][$op]();
	}
	ob_end_flush();
}
catch (Exception $e)
{
        ob_end_clean();
        printException($e);
}


?>
