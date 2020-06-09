//$Id: custom.js 661 2012-11-02 17:35:59Z l.moretto $
$(function() {
//Hack to show dataTable empty table message ("sEmptyTable");
$('table#id_tab_autosearch tbody[class="empty"] tr').remove();

oTable = $('#id_tab_autosearch').dataTable({
	"bJQueryUI": true,
	"bProcessing":true,
	"bPaginate":true,
	"sPaginationType": "full_numbers",
	"oLanguage": {
		"sEmptyTable": "Nessun fornitore disponibile"
	}
});

});