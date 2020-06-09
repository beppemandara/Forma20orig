<?php
/*
 * Questa classe gestisce l'impaginazione di una tabelle 
 */
class paging_bar_f2 {
	/** @var $maxdisplay il numero massimo di pagine che vengono visualizzate nell'impaginazione */
	public $maxdisplay = 18;
	/** @var $totalcount il numero totale di elementi da visualizzare senza LIMIT nella query */
	public $totalcount;
	/** @var $page il numero di pagina corrente */
	public $page;
	/** @var $perpage il numero di ementi per pagina */
	public $perpage;
	/** @var $form_id l'id del form dove si effettuerà il submit */
	public $form_id;
	/** @var $post_extra array chiave=>valore che contiene alcuni dati utili che devono essere 
	 * inviati via POST es:($post_extra=array('column'=>$column,'sort'=>$sort)) UTILE per il POST di 
	 * altri valori (es. select checkboxdi alcune righe)
	 */
	public $post_extra;
	
	/**
	 * Constructor paging_bar_f2 with only the required params.
	 *
	 * @param int $totalcount il numero totale di elementi da visualizzare senza LIMIT nella query
	 * @param int $page il numero di pagina corrente
	 * @param int $perpage il numero di ementi per pagina
	 * @param string $form_id l'id del form dove si effettuerà il submit
	 * @param mixed $post_extra array chiave=>valore che contiene alcuni dati utili che devono essere 
	 * inviati via POST es:($post_extra=array('column'=>$column,'sort'=>$sort)) UTILE per il POST di 
	 * altri valori (es. select checkboxdi alcune righe)
	 */
    public function __construct($totalcount, $page, $perpage, $form_id, $post_extra = array()) {
        $this->totalcount = $totalcount;
        $this->page       = $page;
        $this->perpage    = $perpage;
        $this->form_id    = $form_id;
        $this->post_extra = $post_extra;
    }
 
    /**
     * costruisce l'oggetto paging_bar_f2 istanziando tutti gli attributi
     */    
	private function prepare() {
		
		$link = 'javascript:void(0)';
		if (!isset($this->totalcount) || is_null($this->totalcount)) {
			throw new coding_exception('paging_bar requires a totalcount value.');
		}
		if (!isset($this->page) || is_null($this->page)) {
			throw new coding_exception('paging_bar requires a page value.');
		}
		if (empty($this->perpage)) {
			throw new coding_exception('paging_bar requires a perpage value.');
		}


		if ($this->totalcount > $this->perpage) {
			$pagenum = $this->page - 1;
			
			if ($this->page > 0) {
				$this->previouslink = html_writer::link($link, get_string('previous'), array_merge(array('class'=>'next'),array('onclick'=>build_onclick($this->totalcount,$pagenum,$this->perpage,$this->form_id,$this->post_extra))));
			}

			if ($this->perpage > 0) {
				$lastpage = ceil($this->totalcount / $this->perpage);
			} else {
				$lastpage = 1;
			}

			if ($this->page > 15) {
				$startpage = $this->page - 10;

				$this->firstlink = html_writer::link($link, '1', array_merge(array('class'=>'first'),array('onclick'=>build_onclick($this->totalcount,1,$this->perpage,$this->form_id,$this->post_extra))));
			} else {
				$startpage = 0;
			}

			$currpage = $startpage;
			$displaycount = $displaypage = 0;

			while ($displaycount < $this->maxdisplay and $currpage < $lastpage) {
				$displaypage = $currpage + 1;

				if ($this->page == $currpage) {
					$this->pagelinks[] = $displaypage;
				} else {
					$pagelink = html_writer::link($link, $displaypage,array('onclick'=>build_onclick($this->totalcount,$displaypage-1,$this->perpage,$this->form_id,$this->post_extra)));
					$this->pagelinks[] = $pagelink;
				}

				$displaycount++;
				$currpage++;
			}

			if ($currpage < $lastpage) {
				$lastpageactual = $lastpage - 1;
				$this->lastlink = html_writer::link($link, $lastpage, array_merge(array('class'=>'last'),array('onclick'=>build_onclick($this->totalcount,$lastpageactual,$this->perpage,$this->form_id,$this->post_extra))));
			}

			$pagenum = $this->page + 1;

			if ($pagenum != $displaypage) {
				$this->nextlink = html_writer::link($link, get_string('next'), array_merge(array('class'=>'next'),array('onclick'=>build_onclick($this->totalcount,$pagenum,$this->perpage,$this->form_id,$this->post_extra))));
			}
		}
	}
	
	/**
	 * costruisce la stringa HTML che stamperà il div dell'impaginazione
	 * @return string
	 */

	public function print_paging_bar_f2() {
		$output = '';
		$pagingbar = clone($this);
		$pagingbar->prepare();
	
		if ($pagingbar->totalcount > $pagingbar->perpage) {
			$output .= get_string('page') . ':';
	
			if (!empty($pagingbar->previouslink)) {
				$output .= '&#160;(' . $pagingbar->previouslink . ')&#160;';
			}
	
			if (!empty($pagingbar->firstlink)) {
				$output .= '&#160;' . $pagingbar->firstlink . '&#160;...';
			}
	
			foreach ($pagingbar->pagelinks as $link) {
				$output .= "&#160;&#160;$link";
			}
	
			if (!empty($pagingbar->lastlink)) {
				$output .= '&#160;...' . $pagingbar->lastlink . '&#160;';
			}
	
			if (!empty($pagingbar->nextlink)) {
				$output .= '&#160;&#160;(' . $pagingbar->nextlink . ')';
			}
		}
	
		return html_writer::tag('div', $output, array('class' => 'paging'));
	}

}

/**
 * Costruisce l'action da applicare ai link dell'impaginazione e dell'intestazione delle tabelle
 * @param int $totalcount numero totale di elementi
 * @param int $page pagina dell'impaginazione che deve essere caricata
 * @param int $perpage numro di elementi da visualizzare per pagina
 * @param string $form_id id del form sulla quale fare il submit dei dati
 * @param $post_extra array chiave=>valore che contiene alcuni dati utili che devono essere 
 * inviati via POST es:($post_extra=array('column'=>$column,'sort'=>$sort)) UTILE per il POST di 
 * altri valori (es. select checkboxdi alcune righe)
 * @return string $onclick  action del link
 */
function build_onclick($totalcount,$page,$perpage,$form_id,$arg_post_extra=NULL){
	 
	$onclick="
	totalcount = document.createElement('input');
	totalcount.setAttribute('name', 'totalcount');
	totalcount.setAttribute('id', 'totalcount');
	totalcount.setAttribute('type', 'hidden');
	totalcount.setAttribute('value', '$totalcount');
	 
	page = document.createElement('input');
	page.setAttribute('name', 'page');
	page.setAttribute('id', 'page');
	page.setAttribute('type', 'hidden');
	page.setAttribute('value', '$page');
	 
	perpage = document.createElement('input');
	perpage.setAttribute('name', 'perpage');
	perpage.setAttribute('id', 'perpage');
	perpage.setAttribute('type', 'hidden');
	perpage.setAttribute('value', '$perpage');
	 
	document.getElementById('".$form_id."').appendChild(totalcount);
	document.getElementById('".$form_id."').appendChild(page);
			document.getElementById('".$form_id."').appendChild(perpage);
					";
		 
		foreach($arg_post_extra as $key=>$value){
		$onclick .="
		$key = document.createElement('input');
				$key.setAttribute('name', '$key');
				$key.setAttribute('type', 'hidden');
				$key.setAttribute('id', '$key');
				$key.setAttribute('value', '$value');
				document.getElementById('".$form_id."').appendChild($key);
						";
	}
						 
						$onclick .="document.forms['".$form_id."'].submit(); return false;";
	 
	return $onclick;
}

/**
 * Costruisce l'intestazione della tabella con i link per l'ordinamento
 * @param mixed $head_table array contenente l'intestazione della tabella
 * @param mixed $head_table_sort array contenente le colonne da ordinare
 * @param $post_extra  array chiave=>valore che contiene alcuni dati utili che devono essere
 * inviati via POST es:($post_extra=array('column'=>$column,'sort'=>$sort)) UTILE per il POST di
 * altri valori (es. select checkboxdi alcune righe)
 * @param int $total_rows numero totale di elementi
 * @param int $page pagina dell'impaginazione che deve essere caricata
 * @param int $perpage numro di elementi da visualizzare per pagina
 * @param string $form_id id del form sulla quale fare il submit dei dati
 * @return mixed $head contiene un array contenente l'intestazione della tabella con celle
 * @return mixed $tooltip_arr contiene un array contenente gli eventuali tooltip per ogni cella
 * cliccabili ed ordinabili
 */
function build_head_table($head_table,$head_table_sort,$post_extra,$total_rows,$page, $perpage, $form_id, $tooltip_arr = array()){
	global $OUTPUT;
	$selected_column = $post_extra['column'];
	$sort 			 = $post_extra['sort'];
	$head = array();
        $index = 0;
	foreach ($head_table as $column_table) {
		if(!in_array($column_table, $head_table_sort)) {
                        $tooltip = (sizeof($tooltip_arr) > 0 && $tooltip_arr[$index] != '') ? "title = '$tooltip_arr[$index]'" : "";
			$head[] = "<label $tooltip>".get_string($column_table,'local_f2_traduzioni')."</label>";
                } else {
			if ($selected_column != $column_table) {
				$columnicon = "";
			} else {
				//$page=0;
				$sort = $sort == "ASC" ? "DESC":"ASC";
				$columnicon = $sort == "ASC" ? "down":"up";
				$columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";
			}
			$tmp_post_extra = $post_extra;
			$tmp_post_extra['column'] =  $column_table;
			$tmp_post_extra['sort'] =  $sort;
                        $tooltip = (sizeof($tooltip_arr) > 0 && $tooltip_arr[$index] != '') ? "title = '$tooltip_arr[$index]'" : "";
			$head[] = "<a href=\"javascript:void(0)\" $tooltip onclick=\"".build_onclick($total_rows, $page, $perpage, $form_id, $tmp_post_extra)."\">".get_string($column_table,'local_f2_traduzioni')."</a>$columnicon";
		}
                $index++;
	}
	return $head;
}

function include_fileDownload_before_header(){
	global $PAGE;

	$PAGE->requires->js('/f2_lib/jquery/jquery-1.7.1.min.js');
	$PAGE->requires->js('/f2_lib/jquery/jquery-ui.min.js');
	$PAGE->requires->css('/f2_lib/jquery/css/jquery-ui-1.8.18.custom.css');
	$PAGE->requires->js('/f2_lib/jquery/jquery.fileDownload.js');
	$PAGE->requires->js('/f2_lib/jquery/reports.js');


}

function include_fileDownload_after_header(){
	$waiting_title = get_string('preparing_title','local_f2_traduzioni');
	$waiting_msg = get_string('preparing_msg','local_f2_traduzioni');
	$err_title = get_string('error_title','local_f2_traduzioni');
	$err_msg = get_string('error_msg','local_f2_traduzioni');
	
$str =  <<<EOT
<div id="preparing-file-modal" title="$waiting_title" style="display: none;">
    $waiting_msg
    <div class="ui-progressbar-value ui-corner-left ui-corner-right"
        style="width: 100%; height:22px; margin-top: 20px;"></div></div>
<div id="error-modal" title="$err_title" style="display: none;">$err_msg</div>
EOT;

	return $str;
	
}