//$Id$

function resize_iframe(iframeid) {
	var e = document.getElementById(iframeid);
	if (e) {
		resize_iframe_height(e);
	}
}

function show_div(divid) {
	var div_notif = document.getElementById(divid);
	if (div_notif) div_notif.removeAttribute('hidden');
}

function hide_div(divid) {
	var div_notif = document.getElementById(divid);
	if (div_notif) div_notif.setAttribute('hidden','hidden');
}

function invert_div_visibility(divid)
{
	var hidden =  document.getElementById(divid).getAttribute('hidden','hidden');
	if (hidden) 
	{
		show_div(divid);
	}
	else 
	{
		 hide_div(divid);
	}
}

function resize_iframe_width(e) {
	e.width = e.contentDocument.body.offsetWidth + 10;
}

function resize_iframe_height(e) {
	e.height = e.contentDocument.body.offsetHeight + 50;
}