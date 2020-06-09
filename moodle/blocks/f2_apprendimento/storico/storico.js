
function edit_table(id)
{
//    alert(id);
    var presenza_read = document.getElementById('presenza_read_'+id);
    var presenza_write = document.getElementById('presenza_write_'+id);
    var va_read = document.getElementById('va_read_'+id);
    var va_write = document.getElementById('va_write_'+id);
    var cfv_read = document.getElementById('cfv_read_'+id);
    var cfv_write = document.getElementById('cfv_write_'+id);
    var partecipazione_read = document.getElementById('partecipazione_read_'+id);
    var partecipazione_write = document.getElementById('partecipazione_write_'+id);
    var btn = document.getElementById('applica_'+id);

    if (presenza_read.style.display == "none") {
        // sono in modalità "write" e devo passare a "readonly"
        presenza_read.setAttribute("style","display:block");
        presenza_write.setAttribute("style","display:none");
        va_read.setAttribute("style","display:block");
        va_write.setAttribute("style","display:none");
        cfv_read.setAttribute("style","display:block");
        cfv_write.setAttribute("style","display:none");
        partecipazione_read.setAttribute("style","display:block");
        partecipazione_write.setAttribute("style","display:none");
        btn.setAttribute("style","visibility:hidden");
    } else {
        // sono in modalità "readonly" e devo passare a "write"
        presenza_write.setAttribute("style","display:block");
        presenza_read.setAttribute("style","display:none");
        va_write.setAttribute("style","display:block");
        va_read.setAttribute("style","display:none");
        cfv_write.setAttribute("style","display:block");
        cfv_read.setAttribute("style","display:none");
        partecipazione_write.setAttribute("style","display:block");
        partecipazione_read.setAttribute("style","display:none");
        btn.removeAttribute("style");
        btn.setAttribute("visibility","visible");
    }
}

function confirmSubmit(value,if_presenza)
{

if(if_presenza){
	var id_input_presenza = document.getElementById('presenza_'+value).value;
}

var id_input_presenza_storico = document.getElementById('presenza_storico_'+value).value;
var id_input_cfv = document.getElementById('cfv_'+value).value;

var regexp = /^(\d{0,2})(\.\d{0,1})?$/;
var regexp_cfv = /^(\d{0,3})(\.\d{0,2})?$/;

var return_value = 0;

if(if_presenza){
return_value = regexp.test(id_input_presenza) && regexp.test(id_input_presenza_storico) && regexp_cfv.test(id_input_cfv);
}else{
return_value = regexp.test(id_input_presenza_storico) && regexp_cfv.test(id_input_cfv);
}

if(return_value){
	var agree=confirm("Confermi la modifica?");
	if (agree)
		return true ;
	else
		return false ;
}
else{
	alert('Valori inseriti non validi.');
	return false;
}

}