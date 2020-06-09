function confirm_cancella_tutti(msg,anno)
{
	// alert(anno);
	var r=confirm(msg.replace("_","\'"));
	if (r==true)
	{
		document.location.href = 'cancall.php?cancall=1&anno='+anno;
		return true;
	}
	else
	{
		return false;
	}
}

function confirm_cancella_sessione(msg,id)
{
	var r=confirm(msg.replace("_","\'"));
	if (r==true)
	{
		document.location.href = 'cancall.php?cancid='+id
		return true;
	}
	else
	{
		return false;
	}
}