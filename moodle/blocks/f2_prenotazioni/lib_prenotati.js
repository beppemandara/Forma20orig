function confirmBackRiepilogo(formid,msg,back)
{
	var form = document.getElementById(formid);
	if (form)
	{
		var modificato=0;
		for (var i = 0; i < form.elements.length; i++) 
		{
			if(form.elements[i].type == "checkbox")
			{
			    if(form.elements[i].checked != form.elements[i].defaultChecked)
			    {
			    	modificato = 1;
			    	break;
			    }
			}
		}
		if(modificato == 1)
		{
			var agree = confirm(msg);
			if (agree) document.location.href=back;
		}
		else document.location.href=back;
	}
	else document.location.href=back;
}

function checkPidInconsistenti(formid,msg)
{
	pulisciInconsistenzePrecedenti(formid);
	var form = document.getElementById(formid);
	var i = 0;
	var inconsistenze = 0;
	while(i < form.elements.length)
	{
		if(form.elements[i].type == "checkbox")
		{
			if (form.elements[i].name == "prenotazione_id_all[]") i++;
			else if (form.elements[i].name == "prenotazione_id_sett[]")
			{
				var chk_sett = form.elements[i].checked;
				var sett_el = document.getElementById(form.elements[i].id);
				i++; // controllo prenotazione_id_dir
				if (form.elements[i].name == "prenotazione_id_all[]") i++;
				if (form.elements[i].name == "prenotazione_id_dir[]")
				{
					var dir_el = document.getElementById(form.elements[i].id);
					var chk_dir = form.elements[i].checked;
					i++;
					if (chk_dir == true) 
					{
						if (chk_sett == false)
						{
							inconsistenze++;
							var s_span = document.getElementById(sett_el.value+'span_sett');
							s_span.innerHTML = '*';
							var dir_span = document.getElementById(dir_el.value+'span_dir');
							dir_span.innerHTML = '*';
						}
					}
				}
			}
			else i++;
		}
		else i++;
	}
	if (inconsistenze > 0) alert(msg);
	return (inconsistenze == 0);
}

function pulisciInconsistenzePrecedenti(formid)
{
	var form = document.getElementById(formid);
	var i = 0;
	while(i < form.elements.length)
	{
		if(form.elements[i].type == "checkbox")
		{
			if (form.elements[i].name == "prenotazione_id_all[]") i++;
			else if (form.elements[i].name == "prenotazione_id_sett[]")
			{
				var sett_el = document.getElementById(form.elements[i].id);
				var s_span = document.getElementById(sett_el.value+'span_sett');
				s_span.innerHTML = '';
				i++; // controllo prenotazione_id_dir
				if (form.elements[i].name == "prenotazione_id_all[]") i++;
				if (form.elements[i].name == "prenotazione_id_dir[]")
				{
					var dir_el = document.getElementById(form.elements[i].id);
					var dir_span = document.getElementById(dir_el.value+'span_dir');
					dir_span.innerHTML = '';
					i++;
				}
			}
			else i++;
		}
		else i++;
	}
}

function checkAnomalieValidazioniSettori(settore,msg)
{
	var num_anomalie = -1;
//	alert(num_anomalie);
	var num_anomalie = document.getElementById('numAnomalie').value;
	if (num_anomalie == 0)
	{
		document.location.href = 'manage_validazione_dir_all.php?settid='+settore;
	}
	else alert(msg);
}