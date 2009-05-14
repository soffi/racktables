// JavaScript functions

// Used for (un)checking an entire row of rackspace atoms
function toggleRowOfAtoms (rackId, rowId) {
	var checkboxId;
	for (var i=0; i<=2; i++) {
		checkboxId = "atom_" + rackId + "_" + rowId + "_" + i;

		// Abort if the box is disabled
		if (document.getElementById(checkboxId).disabled == true) continue;

		// Box isn't disabled, toggle it
		if (document.getElementById(checkboxId).checked == false) {
			document.getElementById(checkboxId).checked = true;
		} else {
			document.getElementById(checkboxId).checked = false;
		}
	}
}

// Used for (un)checking an entire column of rackspace atoms
function toggleColumnOfAtoms (rackId, columnId, numRows) {
	var checkboxId;
	for (var i=1; i<numRows+1; i++) {
		checkboxId = "atom_" + rackId + "_" + i + "_" + columnId;

		// Abort if the box is disabled
		if (document.getElementById(checkboxId).disabled == true) continue;

		// Box isn't disabled, toggle it
		if (document.getElementById(checkboxId).checked == false) {
			document.getElementById(checkboxId).checked = true;
		} else {
			document.getElementById(checkboxId).checked = false;
		}
	}
}

var alwaysShowHistoryBar = false;

function onClickHistoryBarHandle()
{
	if ($.cookie("showHistoryBar") == "show")
	{
		$.cookie("showHistoryBar", "hide", {path: '/', expires: 365});
	}
	else
	{
		$.cookie("showHistoryBar", "show", {path: '/', expires: 365});
	}
	renderHistoryBar();
}

function renderHistoryBar()
{
	if (alwaysShowHistoryBar == true) return;
	if ($.cookie("showHistoryBar") == "show")
	{
		$('#HistoryBarHandle')[0].className="show";
		$('#HistoryBarHandle')[0].innerHTML="Show history bar";
		$('#HistoryBarRow')[0].className="hide";
	}
	else
	{
		$('#HistoryBarHandle')[0].className="hide";
		$('#HistoryBarHandle')[0].innerHTML="Hide history bar";
		$('#HistoryBarRow')[0].className="show";
	}
	$.cookie("showHistoryBar", $.cookie("showHistoryBar"), {path: '/', expires: 365});
}
$(document).ready(function(){
	renderHistoryBar();
	$('#HistoryBarHandle').click(onClickHistoryBarHandle);
});

