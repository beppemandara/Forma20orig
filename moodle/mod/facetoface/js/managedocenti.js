//$Id: managedocenti.js 1 2012-09-26 l.sampo $

/*$(document).ready(function($) {
	$("input[class='insession']").click(function(){
		if ($(this).is(':checked')) {
			$('input.insession:checkbox').attr("checked", false);
        }
	});
	$("input[class='outsession']").click(function(){
		if ($(this).is(':checked')) {
			$('input.outsession:checkbox').attr("checked", false);
        } 
	});
});*/

$(function() {
  $.localise('ui-multiselect', {language: 'it', path: '../../f2_lib/jquery/multiselect/locale/'});
  $(".multiselect").multiselect();
  $(".multiselect").multiselect({sortable: false, searchable: false});
});

function getDocentiJS(id_course) {
	var dataString = 'course='+id_course;
	
	$.ajax({
		type: "POST",
		url: "fornitori.php",
		cache: false,
		data: dataString,
		success: function(msg) {
			$('#list_docenti').html("");
            $('#list_docenti').html(msg);
        	$('.multiselect').multiselect('destroy');
        	$('.multiselect').multiselect(); 
		}
	});

}