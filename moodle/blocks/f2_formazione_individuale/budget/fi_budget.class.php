<?php

require_once($CFG->dirroot."/local/f2_support/lib.php");
/**
 * This file contains the parent class for Formazione Individuale budgets.
 *
 * @package    block
 * @subpackage f2_formazione_individuale
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

define("CONST_VISIBLE", 1);
define("CONST_FORMA_FRAMEWORKID", 1);
define("CONST_TIPO_PIANIFICAZIONE_I", 4);
define("CONST_TIPO_PIANIFICAZIONE_IL", 2);
/**
 * Class describing a budget
 */
class fi_budget {
    private static $valid_types = NULL;
    /**
     *
     * @var int Anno formativo 
     */
    var $year;
    /**
     *
     * @var int  Definisce la tipologia di budget da estrarre (form. ind. e/o form. ind. lingua)
     */    
    var $type;
    /**
     * Array chiave valore delle gerarchie visibili. Ad es. giunta=>orgid
     * @var type 
     */
    var $branches;
    /**
     *
     * @var array Id dei domini radice visibili (Giunta e/o Consiglio)
     */
    protected $org_rootids;
    /**
     *
     * @var int 
     */
    var $total_budget;
    /**
     *
     * @var string 
     */
    var $target_table;
    /**
     *
     * @var boolean 
     */
    var $exportable;
    
    /// Class Functions

    /**
     * 
     * @param int $year
     * @param array $types
     */
    function __construct($year, $type, $branches) {
        $this->year = $year;
        
        if (!isset(self::$valid_types)) {
            self::$valid_types = array();
            $p = get_parametro('p_f2_tipo_pianificazione_1');
            if($p) {
                self::$valid_types[$p->val_char] = $p->descrizione;
            } else {
                print_error('missing_p_f2_tipo_pianificazione', 'block_f2_formazione_individuale');
            }
            $p = get_parametro('p_f2_tipo_pianificazione_2');
            if($p) {
                self::$valid_types[$p->val_char] = $p->descrizione;
            } else {
                print_error('missing_p_f2_tipo_pianificazione', 'block_f2_formazione_individuale');
            }
        }
        //check valid types
        if ( in_array($type, array_keys(self::$valid_types)) ) {
            $this->type = $type;
        } else {
            print_error('tipopianificazioneinvalido', 'block_f2_formazione_individuale');
        }
        //TODO check valid org ids
        $this->branches = $branches;
        $this->org_rootids = array_values($branches);
        $this->exportable = false;
    }
    /**
     * Restituisce direzioni e budget per l'anno formativo ed il tipo di budget specificati.
     * 
     * @global type $DB
     * @param object $formdata Form data object
     * @return boolean
     */
    function get_fi_budget ($formdata) {
        global $DB;

        $params = array();
        list($sqlparents, $params) = $DB->get_in_or_equal($this->org_rootids, SQL_PARAMS_NAMED);
        $params["anno"] = $this->year;
        $params["frameworkid"] = CONST_FORMA_FRAMEWORKID;
        $params["tipo"] = $this->type;
        $sqlsort = in_array($formdata->sort, array('ASC', 'DESC')) ? $formdata->sort : 'ASC';
        $sqlfilter = '';
        if (isset($formdata->org) && !empty($formdata->org)) {
            $casesensitive = FALSE;
            $sqlfiltershort = $DB->sql_like('o.shortname', ':snamefilter', $casesensitive);
            $sqlfilterfull = $DB->sql_like('o.fullname', ':fnamefilter', $casesensitive);
            $params['snamefilter'] = "%$formdata->org%";
            $params['fnamefilter'] = $params['snamefilter'];
            $sqlfilter = " AND ($sqlfiltershort OR $sqlfilterfull)";
        }
        
        $sql = "
    SELECT ob.id, ob.money_bdgt, o.fullname, o.shortname
      FROM {$this->target_table} ob
      JOIN {org} o ON (o.id = ob.orgfk AND o.frameworkid = :frameworkid AND o.parentid $sqlparents)
     WHERE ob.anno = :anno 
       AND ob.tipo = :tipo
       $sqlfilter
  ORDER BY o.shortname $sqlsort";
      
        $page = $formdata->page;
        $perpage = $formdata->perpage;

        $rs = new stdClass();
        $rs->data = $DB->get_records_sql($sql, $params, $page*$perpage, $perpage);
        $rs->count = $DB->count_records_sql("SELECT count(*) from ($sql) as tmp", $params);
        return $rs;
    }
    /**
     * 
     * @global type $DB
     */
    function get_fi_budget_tot () {
        global $DB;
        
        $params = array();
        list($sqlparents, $params) = $DB->get_in_or_equal($this->org_rootids, SQL_PARAMS_NAMED);
        $params["anno"] = $this->year;
        $params["frameworkid"] = CONST_FORMA_FRAMEWORKID;
        $params["tipo"] = $this->type;
        
        $sql = "
    SELECT COALESCE(SUM(ob.money_bdgt), 0) as tot_budget
      FROM {$this->target_table} ob
      JOIN {org} o ON (o.id = ob.orgfk AND o.frameworkid = :frameworkid AND o.parentid $sqlparents)
     WHERE ob.anno = :anno 
       AND ob.tipo = :tipo";

        $this->total_budget = $DB->get_field_sql($sql, $params);
        
        return TRUE;
    }
    /**
     * 
     * @return boolean
     */
    function is_exportable() {
        return $this->exportable;
    }
    /**
     * 
     * @return string
     */
    function getTypeDescr() {
        return self::$valid_types[$this->type];
    }
    
    function getValidTypes() {
        return array_keys(self::$valid_types);
    }
}