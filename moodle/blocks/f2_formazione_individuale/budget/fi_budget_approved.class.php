<?php

define("TBL_FI_APPROVEDBDGT", "{f2_org_budget}");

/**
 * 
 */
class fi_budget_approved extends fi_budget {
    /**
     * 
     */
    function __construct($year, $type, $branches) {
        parent::__construct($year, $type, $branches);
        $this->exportable = true;
        $this->target_table = TBL_FI_APPROVEDBDGT;
    }
    /**
     * 
     * @param int $year Anno formativo
     * @return int Numero di record di budegt per l'anno formativo
     */
    public function budget_exists() {
        global $DB;
        $params = array();
        list($sqltypes, $params) = $DB->get_in_or_equal($this->getValidTypes(), SQL_PARAMS_NAMED);
        $params['year'] = $this->year;
        return $DB->count_records_sql("SELECT count(*) from {$this->target_table} where anno = :year and tipo $sqltypes", $params);
    }
    /**
     * Crea un oggetto PHPExcel e restituisce il relativo writer.
     * @global object $CFG
     * @param object $data
     * @return object Excel writer
     */
    function export_xls($data) {
        global $CFG;
        require_once($CFG->dirroot.'/f2_lib/phpexcel177/PHPExcel.php');
        
        $btype = $this->getTypeDescr();
        $timenow = time();
        $objPHPExcel = PHPExcel_IOFactory::load($CFG->dirroot.'/f2_lib/phpexcel177/template/template_csi.xls');

        // Set properties
        $objPHPExcel->getProperties()->setCreator("CSI")
                                     ->setLastModifiedBy("CSI")
                                     ->setTitle("CSI")
                                     ->setSubject("CSI")
                                     ->setDescription("CSI")
                                     ->setKeywords("CSI")
                                     ->setCategory("CSI");

        $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValueByColumnAndRow(0,1,'Direzione')
                    ->setCellValueByColumnAndRow(1,1,'Budget '.$btype)
                    ;


        $post_values->page=0;
        $post_values->perpage=0;

        $row=1;
        foreach($data as $data_row){

            $row++;
            $col = 0;
            $orgname = "{$data_row->shortname} - {$data_row->fullname}";

            $objPHPExcel->getActiveSheet()
                ->setCellValueByColumnAndRow($col++ , $row, $orgname)
                ->setCellValueByColumnAndRow($col++ , $row, round($data_row->money_bdgt,2));
        }

        $row++;
        $col = 0;

        //RIGA DEI TOTALI
        $objPHPExcel->getActiveSheet()
        ->setCellValueByColumnAndRow($col++ , $row, get_string('total').':')
        ->setCellValueByColumnAndRow($col++ , $row, round($this->total_budget,2));
        //FINE RIGA DEI TOTALI


        $objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        // stampaorizzontale
        $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle($btype);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        return $objWriter;
    }
}
