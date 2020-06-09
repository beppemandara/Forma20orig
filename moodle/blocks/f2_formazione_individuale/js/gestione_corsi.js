//$Id$
//Usato in gestione_corsi.php e allega_determina.php
$(document).ready(function(){
	$(".paging a" ).click( function(){
        $("#course_frm").attr("action", this.href);
        $("#course_frm").submit();
		return false;
	});
	
	num_check_totali = num_check_checked();

	$("[type='checkbox'][name='id_course[]']").click(function() {
		if($(this).is(':checked'))
		   num_check_totali = num_check_totali+1;
		else
		   num_check_totali = num_check_totali-1;
		
		$("#span_elementi_sel").html(num_check_totali);
	});
});

function num_check_checked(){
	var num_check = 0;
	var num_check_hidden = $("[type='hidden'][name='id_course[]']").length; //numero di check selezionate nelle altre pagine

	$("[type='checkbox'][name='id_course[]']").each(function() {
		if($(this).is(':checked'))
		   num_check = num_check+1;
	});
	//var num_check = $("[type='checkbox'][name='id_course[]'][checked='checked']").length;//numero di check selezionate nella pagina corrente
	var num_check_totali = num_check_hidden+num_check;
	$("#span_elementi_sel").html(num_check_totali);
	return num_check_totali;
}