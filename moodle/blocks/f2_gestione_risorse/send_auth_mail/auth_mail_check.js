function checkAll(from,to)
{
	var i = 0;
	var chk = document.getElementsByName(to);
	var resCheckBtn = document.getElementsByName(from);
	var resCheck = resCheckBtn[i].checked;
	var tot = chk.length;
	for (i = 0; i < tot; i++)
	{
		chk[i].checked = resCheck;
		edit_table(chk[i].value,resCheck);	
	}
}

function resetAll(from,to)
{
	var i = 0;
	var chk = document.getElementsByName(to);
	var resCheckBtn = document.getElementsByName(from);
//	var resCheck = resCheckBtn[i].checked;
	resCheckBtn[i].checked = false;
	var tot = chk.length;
	for (i = 0; i < tot; i++)
	{
		chk[i].checked = false;
		edit_table_reset(chk[i].value);	
	}
}

function checkSelected(cname,errmsg,confmsg)
{
	var selected = 0;
	var chk = document.getElementsByName(cname);
	var tot = chk.length;
	var i = 0;
	for (i = 0; i < tot; i++) 
	{
		if (chk[i].checked)
		{	
			selected++;
			break;
		}
	}
	if (selected == 0) 
	{
		alert(errmsg);
		return false;
	}
	else
	{
		return confirm(confmsg.replace("_","\'"));
	}
}

function edit_table(id,resCheck)
{
	var txt = document.getElementById('sirpedid_'+id);
	var txt1 = document.getElementById('sirpdataedid_'+id);
//	var btn = document.getElementById('applica_'+id);
	
//	if(txt.getAttribute("readonly") == "readonly"){
	var chk_el = document.getElementById('edizione_id_'+id);

	if (chk_el.checked == true && resCheck == true)
	{
		txt.removeAttribute("readonly");
		txt.removeAttribute("style");
		txt.setAttribute("style","width:50px");	
		txt1.removeAttribute("readonly");
		txt1.removeAttribute("style");
		txt1.setAttribute("style","width:100px");	
//		btn.removeAttribute("style");
//		btn.setAttribute("visibility","visible");
	}
	else
	{
		txt.setAttribute("readonly","readonly");
   		txt.setAttribute("style","border:none; width:50px");	
   		txt1.setAttribute("readonly","readonly");
   		txt1.setAttribute("style","border:none; width:100px");	
//   	btn.setAttribute("style","visibility:hidden");
	}
}

function edit_table_reset(id)
{
	var txt = document.getElementById('sirpedid_'+id);
	var txt1 = document.getElementById('sirpdataedid_'+id);
	txt.value = txt.defaultValue;
	txt.setAttribute("readonly","readonly");
	txt.setAttribute("style","border:none; width:50px");
	txt1.value = txt1.defaultValue;
	txt1.setAttribute("readonly","readonly");
	txt1.setAttribute("style","border:none; width:100px");
}