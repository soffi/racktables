<?php
require_once 'inc/exception.php';
// no ctx override is necessary
ob_start();
try {
require 'inc/init.php';
fixContext();
if (!permitted())
{
	renderAccessDenied();
	die;
}
// Only store the tab name after clearance is got. Any failure is unhandleable.
$_SESSION['RTLT'][$pageno] = $tabno;

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
echo '<head><title>' . getTitle ($pageno, $tabno) . "</title>\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
echo "<link rel=stylesheet type='text/css' href=pi.css />\n";
echo "<link rel=icon href='" . getFaviconURL() . "' type='image/x-icon' />";
echo "<style type='text/css'>\n";
// Print style information
foreach (array ('F', 'A', 'U', 'T', 'Th', 'Tw', 'Thw') as $statecode)
{
	echo "td.state_${statecode} {\n";
	echo "\ttext-align: center;\n";
	echo "\tbackground-color: #" . (getConfigVar ('color_' . $statecode)) . ";\n";
	echo "\tfont: bold 10px Verdana, sans-serif;\n";
	echo "}\n\n";
}
?>
	</style>
	<script language='javascript' type='text/javascript' src='js/jquery-1.3.1.min.js'></script>
	<script language='javascript' type='text/javascript' src='js/jquery/jquery.smartmodal_minimized.js'></script>
	<script language='javascript' type='text/javascript' src='js/live_validation.js'></script>
	<script language='javascript' type='text/javascript' src='js/codepress/codepress.js'></script>
	</head>
<body>
 <table border=0 cellpadding=0 cellspacing=0 width='100%' height='100%' class=maintable>
 <tr class=mainheader>
  <td colspan=2>
   <table width='100%' cellspacing=0 cellpadding=2 border=0>
   <tr>
    <td valign=top><a href='http://racktables.org/'><?php printImageHREF ('logo'); ?></a></td>
    <td valign=top><div class=greeting><?php printGreeting(); ?></div></td>
   </tr>
   </table>
  </td>
 </tr>

 <tr>
  <td class="menubar" colspan=2>
   <table border="0" width="100%" cellpadding="3" cellspacing="0">
   <tr>
<?php showPathAndSearch ($pageno); ?>
   </tr>
   </table>
  </td>
 </tr>

<tr>
<td id="historyBar">
<?php

$params = $_GET;

if ($prev_milestone != null)
{
        echo '<a href="'.makeHref(array_merge($_GET, array('r'=>$prev_milestone['rev']))).'">';
	printImageHREF('previousms', 'Previous milestone');
	echo '</a>';
}
else
	printImageHREF('previousms-disabled', 'Previous milestone');

if ($prev_op['rev'] >= 0)
{
        echo '<a href="'.makeHref(array_merge($_GET, array('r'=>$prev_op['rev']))).'">';
	printImageHREF('previousop', 'Previous operation');
	echo '</a>';
}
else
	printImageHREF('previousop-disabled', 'Previous operation');
if ($numeric_revision == $head_revision)
        echo '<input type="text" id="revisionInput" value="'.$this_op.'" disabled="disabled" class="headed"> ';
else
        echo '<input type="text" id="revisionInput" value="'.$this_op.'" disabled="disabled"> ';
echo '<input type="text" id="mileInput" value="'.$this_milestone.'" disabled="disabled">';
if (isset($next_op['rev']) and $next_op['rev'] <= $head_op_rev)
{
        echo '<a href="'.makeHref(array_merge($_GET, array('r'=>$next_op['rev']))).'">';
	printImageHREF('nextop', 'Next operation');
	echo '</a>';
}
else
	printImageHREF('nextop-disabled', 'Next operation');
if ($next_milestone != null)
{
        echo '<a href="'.makeHref(array_merge($_GET, array('r'=>$next_milestone['rev']))).'">';
	printImageHREF('nextms', 'Next milestone');
	echo '</a>';
}
else
	printImageHREF('nextms-disabled', 'Next milestone');

        echo '<span id="milestone">';

        if ($numeric_revision == $head_revision)
        {
                if ($head_milestone_rev < $head_op_rev)
                {
			$num_operations = count(Operation::getOperationsSince($head_milestone_rev));
                        echo "<input type=\"button\" id=\"numOperations\" title=\"$num_operations changes since MS $head_milestone\" value=\"$num_operations\" onclick=\"document.location.href='".
			makeHref(array('page'=>'history', 'tab'=>'default', 'start_rev'=>$head_milestone_rev+1)).
			"'\">";
                }
        }
	else
	{
		echo "<button onclick=\"document.location.href='".makeHref(array_merge($_GET, array('r'=>$head_op_rev)))."'\">Jump to HEAD</button>";
	}
        echo '</span>';


?>
</td>
</tr>




	<tr>
<?php
	showTabs ($pageno, $tabno);
?>
	</tr>

 <tr>
  <td colspan=2>
<?php
if (isset ($tabhandler[$pageno][$tabno]))
{
	if (isset ($page[$pageno]['bypass']) && isset ($page[$pageno]['bypass_type']))
	{
		switch ($page[$pageno]['bypass_type'])
		{
			case 'uint':
				assertUIntArg ($page[$pageno]['bypass'], 'index');
				break;
			case 'uint0':
				assertUIntArg ($page[$pageno]['bypass'], 'index', TRUE);
				break;
			case 'inet4':
				assertIPv4Arg ($page[$pageno]['bypass'], 'index');
				break;
			case 'string':
				assertStringArg ($page[$pageno]['bypass'], 'index');
				break;
			default:
				showError ('Dispatching error for bypass parameter', __FILE__);
				break;
		}
		try {
			$tabhandler[$pageno][$tabno] ($_REQUEST[$page[$pageno]['bypass']]);
		} catch (OutOfRevisionRangeException $e) {
			startPortlet('Out of revision range');
			echo '<p>Object \''.$e->getTable().'\' id \''.$e->getId().'\' does not exist in the current point of time</p>';
			list($appearOp, $appearOpRev) = Operation::getCorrespondingOperation($e->getAppeared());
			list($disappearOp, $disappearOpRev) = Operation::getCorrespondingOperation($e->getDisappeared());
			echo '<p>It is available from <a href="'.
				makeHref(array_merge($_GET, array('r'=>$appearOpRev))).
				'">operation '.$appearOp.'</a> to <a href="'.
				makeHref(array_merge($_GET, array('r'=>$disappearOpRev))).
				'">operation '.$disappearOp.'</a>';				
			finishPortlet();
		}
	}
	else
		$tabhandler[$pageno][$tabno] ();
}
elseif (isset ($page[$pageno]['handler']))
	$page[$pageno]['handler'] ($tabno);
else
	throw new Exception ("Failed to find handler for page '${pageno}', tab '${tabno}'");
?>
	</td>
	</tr>
	</table>
</body>
</html>
<?php
ob_end_flush();
} catch (Exception $e) {
	ob_end_clean();
	printException($e);
}

