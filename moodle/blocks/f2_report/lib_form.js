//$Id$

$(document).ready(function(){

		var scegli = '<option value="-1">Scegli...</option>';
		var attendere = '<option value="-1">Attendere...</option>';
		
//		$("select#id_report_pentaho_param_sessione").html(scegli);
//		$("select#id_report_pentaho_param_sessione").attr("disabled", "disabled");
		
		$("select#id_report_pentaho_param_anno_formativo").change(function(){
			var anno = $("select#id_report_pentaho_param_anno_formativo option:selected").attr('value');
			
			if ($("select#id_report_pentaho_param_sessione")[0])
			{
				$("select#id_report_pentaho_param_sessione").html(attendere);
				$("select#id_report_pentaho_param_sessione").attr("disabled", "disabled");
				
				$.post("js_form.php", {ajax_anno_formativo_per_sessione:anno}, function(data){
					$("select#id_report_pentaho_param_sessione").removeAttr("disabled"); 
					$("select#id_report_pentaho_param_sessione").html(data);	
				});
				
			}
			else if ($("select#id_report_pentaho_param_corso")[0])
			{
				$("select#id_report_pentaho_param_corso").html(attendere);
				$("select#id_report_pentaho_param_corso").attr("disabled", "disabled");
				
				$.post("js_form.php", {ajax_anno_formativo_per_corso:anno}, function(data){
					$("select#id_report_pentaho_param_corso").removeAttr("disabled"); 
					$("select#id_report_pentaho_param_corso").html(data);	
				});
			}
		});		
	});