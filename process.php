<?php

require 'inc/init.php';
ob_start();
try {
	// FIXME: find a better way to handle this error
	if ($_REQUEST['op'] == 'addFile' && !isset($_FILES['file']['error'])) {
		showError ("File upload error, it's size probably exceeds upload_max_filesize directive in php.ini");
		die;
	}
	fixContext();

	if (empty ($op) or !isset ($ophandler[$pageno][$tabno][$op]))
	{
		showError ("Invalid request in operation broker: page '${pageno}', tab '${tabno}', op '${op}'", __FILE__);
		die();
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
		$location = $ophandler[$pageno][$tabno][$op]();
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
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
        echo "<head><title> Exception </title>\n";
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
        echo "<link rel=stylesheet type='text/css' href=pi.css />\n";
        echo "<link rel=icon href='" . getFaviconURL() . "' type='image/x-icon' />";
        echo '</head> <body><h2>Uncaught exception: </h2><code>'.$e->getMessage().'</code> (<code>'.$e->getCode().'</code>)';
        echo '<p>at file <code>'.$e->getFile().'</code>, line <code>'.$e->getLine().'</code></p><pre>';
        print_r($e->getTrace());
        echo '</pre></body></html>';
}


?>
