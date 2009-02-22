<?php

function printException($e)
{
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
	echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
	echo "<head><title> Exception </title>\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	echo "<link rel=stylesheet type='text/css' href=pi.css />\n";
	echo "<link rel=icon href='" . getFaviconURL() . "' type='image/x-icon' />";
	echo '</head> <body><h2>Uncaught exception: '.get_class($e).'</h2><code>'.$e->getMessage().'</code> (<code>'.$e->getCode().'</code>)';
	echo '<p>at file <code>'.$e->getFile().'</code>, line <code>'.$e->getLine().'</code></p><pre>';
	print_r($e->getTrace());
	echo '</pre></body></html>';

}

?>
