<?php
require_once 'inc/exception.php';
ob_start();
try {
	require 'inc/init.php';
	// FIXME: find a better way to handle this error
	if ($_REQUEST['op'] == 'addFile' && !isset($_FILES['file']['error'])) {
		showError ("File upload error, it's size probably exceeds upload_max_filesize directive in php.ini");
		die;
	}
	fixContext();

	if (empty ($op) or !isset ($ophandler[$pageno][$tabno][$op]))
	{
		throw new Exception ("Invalid request in operation broker: page '${pageno}', tab '${tabno}', op '${op}'");
	}

	// We have a chance to handle an error before starting HTTP header.
	if (!isset ($delayauth[$pageno][$tabno][$op]) and !permitted())
	{
		$errlog = array
		(
			'v' => 2,
			'm' => array (0 => array ('c' => 157)) // operation not permitted
		);
		$location = buildWideRedirectURL ($errlog);
	}
	else
	{
		Operation::init();
		Operation::setUser($accounts[$remote_username]['user_id']);
		Milestone::setUser($accounts[$remote_username]['user_id']);
		$location = $ophandler[$pageno][$tabno][$op]();
		Operation::finalize();
		if (empty ($location))
		{
			showError ('Operation handler failed to return its status', __FILE__);
		}
	}
	header ("Location: " . $location);
	ob_end_flush();
} 
catch (Exception $e) 
{
	ob_end_clean();
	printException($e);
}


?>
