<?php
/*
 * A. Albertin, G. Mandarà - CSI Piemonte - febbraio 2014
 * 
 * Legge e visualizza i dati di un corso collegato
 * 
 * E' inclusa dai file: log_corso_1, cancella_corso_1
 * 
 * Richiede:
 *     che l'id del corso sia presente in $_REQUEST['id']
 *     che siano accessibili le function di lettura dal db
 * 
 * NOTA: blocco che potrebbe essre modificato nelle prossime versioni del programma
 */
$id = $_REQUEST['id'];
$rec_mdl_f2_forma2riforma_mapping = new EML_RECmdl_f2_forma2riforma_mapping();
$rec_mdl_course = new EML_RECmdl_course();
$ret_code = Get_mdl_f2_forma2riforma_mapping($id, $rec_mdl_f2_forma2riforma_mapping);
$id_forma20 = $rec_mdl_f2_forma2riforma_mapping->id_forma20;
$ret_code = Get_mdl_course_Forma20($id_forma20, $rec_mdl_course);
$table = new html_table();
$table->width = "100%";
    $table->head = array ();
    $table->align[] = 'left';
    $table->size[] = '20%';
    $table->align[] = 'left';
    $table->size[] = '80%';
    $row = array ();
    $row[] = get_string('f2r_etichetta_corso','block_f2_apprendimento');
    $row[] = $rec_mdl_f2_forma2riforma_mapping->shortname." - ".$rec_mdl_course->fullname;
    $table->data[] = $row;
    $row = array ();
    $row[] = get_string('f2r_etichetta_perc_x_cfv','block_f2_apprendimento');
    $row[] = $rec_mdl_f2_forma2riforma_mapping->perc_x_cfv;
    $table->data[] = $row;
    $row = array ();
    $row[] = get_string('f2r_etichetta_va_default','block_f2_apprendimento');
    $row[] = $rec_mdl_f2_forma2riforma_mapping->va_default;
    $table->data[] = $row;
    $row = array ();
    $row[] = get_string('f2r_etichetta_nota','block_f2_apprendimento');
    $row[] = $rec_mdl_f2_forma2riforma_mapping->nota;
    $table->data[] = $row;
    $shortname = $rec_mdl_f2_forma2riforma_mapping->shortname;
echo html_writer::table($table);
?>