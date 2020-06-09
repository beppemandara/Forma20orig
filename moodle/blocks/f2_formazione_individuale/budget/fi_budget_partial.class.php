<?php
define("TBL_FI_PARTIALBDGT", "{f2_fi_partialbudget}");

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class fi_budget_partial extends fi_budget {
    /**
     *
     * @var boolean 
     */
    var $modified;
    /**
     * 
     */
    function __construct($year, $type, $branches) {
        parent::__construct($year, $type, $branches);
        $this->exportable = false;
        $this->target_table = TBL_FI_PARTIALBDGT;
    }
    /**
     * Crea le righe di budget parziale per tutte le direzioni visbili e per tutti i tipi
     * di formazione gestiti. Tutti i budget sono valorizzati a $money (default 0).
     * @global object $DB
     * @global object $USER
     * @param int $money Default zero.
     * @return boolean True if successfull, else show error page
     */
    public function setup_budget_for_year($money = 0) {
        global $DB, $USER;
        $result = true;
        
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach($this->getValidTypes() as $type) {
                $params = array();
                switch ($type) {
                    case CONST_TIPO_PIANIFICAZIONE_IL:
                        if (!array_key_exists('giunta', $this->branches)) {
                            continue 2;
                        }
                        $sqlparents = "= :giuntaid";
                        $params['giuntaid'] = $this->branches['giunta'];
                        break;
                    case CONST_TIPO_PIANIFICAZIONE_I:
                        list($sqlparents, $params) = $DB->get_in_or_equal($this->org_rootids, SQL_PARAMS_NAMED);
                        break;
                    default:
                        print_error('tipopianificazioneinvalido', 'block_f2_formazione_individuale');
                }
                $params['type'] = $type;
                $params['year'] = $this->year;
                $params['money'] = $money;
                $params['time'] = date('Y-n-j H:i:s');
                $params['who'] = $USER->username;
                $params['frameworkid'] = CONST_FORMA_FRAMEWORKID;
                $params['visible'] = CONST_VISIBLE;
                
                $sql = "
        INSERT INTO {$this->target_table} 
                    (anno, orgfk, tipo, money_bdgt, lstupd, usrname)
             SELECT :year, o.id, :type, :money, :time, :who
               FROM {org} o
              WHERE o.frameworkid = :frameworkid 
                AND o.parentid $sqlparents 
                AND o.visible = :visible";

                $DB->execute($sql, $params);
            }
        } catch (moodle_exception $e) {
            $transaction->rollback($e);
        }
        $transaction->allow_commit();
        
        return true;
    }
    /**
     * Crea le righe di budget per le eventuali nuove direzioni attivate dopo la creazione
     * del budget iniziale.
     * @global object $DB
     * @global object $USER
     * @param int $money Default zero.
     * @return boolean True if success, else error.
     */
    public function setup_budget_new_orgs_only($money = 0) {
        global $DB, $USER;
        $result = true;
        
        $transaction = $DB->start_delegated_transaction();
        try {
            foreach($this->getValidTypes() as $type) {
                $params = array();
                switch ($type) {
                    case CONST_TIPO_PIANIFICAZIONE_IL: //AK-LM: formazione individuale lingue solo per giunta
                        if (!array_key_exists('giunta', $this->branches)) {
                            continue 2;
                        }
                        $sqlparents = "= :giuntaid";
                        $params['giuntaid'] = $this->branches['giunta'];
                        break;
                    case CONST_TIPO_PIANIFICAZIONE_I:
                        list($sqlparents, $params) = $DB->get_in_or_equal($this->org_rootids, SQL_PARAMS_NAMED);
                        break;
                    default:
                        print_error('tipopianificazioneinvalido', 'block_f2_formazione_individuale');
                }
                $params['type'] = $type;
                $params['type2'] = $type;
                $params['year'] = $this->year;
                $params['money'] = $money;
                $params['time'] = date('Y-n-j H:i:s');
                $params['who'] = $USER->username;
                $params['frameworkid'] = CONST_FORMA_FRAMEWORKID;
                $params['visible'] = CONST_VISIBLE;
                $sql = "
        INSERT INTO {$this->target_table} 
                    (anno, orgfk, tipo, money_bdgt, lstupd, usrname)
             SELECT :year, o.id, :type, :money, :time, :who
               FROM {org} o
          LEFT JOIN {$this->target_table} pb on (pb.orgfk = o.id AND pb.tipo = :type2)
              WHERE o.frameworkid = :frameworkid 
                AND o.parentid $sqlparents
                AND o.visible = :visible
                AND pb.orgfk is NULL";

                $DB->execute($sql, $params);
            }
        } catch (dml_exception $e) {
            $transaction->rollback($e);
        }
        $transaction->allow_commit();
        
        return true;
    }
    /**
     * 
     * @param int $year Anno formativo
     * @return int Numero di record di budegt per l'anno formativo
     */
    public function budget_exists() {
        global $DB;
        return $DB->count_records_sql("SELECT count(*) from {$this->target_table} where anno = :year", array('year'=>$this->year));
    }
    /**
     * Salva la modifica allo specifico budget.
     * @global object $DB
     * @global object $USER
     * @param int $budgetid
     * @param float $money
     * @return boolean true if success, else print error
     */
    public function update($budgetid, $money) {
        global $DB, $USER;
        
        if ($budgetid > 0) {
            $dataobject = new stdClass();
            $dataobject->id = $budgetid;
            $dataobject->money_bdgt = $money;
            $dataobject->lstupd = date('Y-n-j H:i:s');
            $dataobject->usrname = $USER->username;

            $transaction = $DB->start_delegated_transaction();
            try {
                $table = trim($this->target_table, "{}");               
                $DB->update_record($table, $dataobject);
            } catch (dml_exception $e) {
                $transaction->rollback($e);
            }
            $transaction->allow_commit();
        }
        
        return true;
    }
    
    public function approve() {
        global $DB, $USER;
        
        $params = array();
        list($sqlparents, $params) = $DB->get_in_or_equal($this->org_rootids, SQL_PARAMS_NAMED);
        $params['year'] = $this->year;
        $params['time'] = date('Y-n-j H:i:s');
        $params['who'] = $USER->username;
        $params['frameworkid'] = CONST_FORMA_FRAMEWORKID;
        $params['visible'] = CONST_VISIBLE;
        $params['type'] = $this->type;
        
        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records('f2_org_budget', array('anno'=>$this->year, 'tipo'=>$this->type));
            
            $sql = "INSERT INTO {f2_org_budget} 
                    (anno, orgfk, tipo, money_bdgt, days_bdgt, lstupd, usrname)
                    SELECT ob.anno, ob.orgfk, ob.tipo, ob.money_bdgt, 0, :time, :who
                    FROM {$this->target_table} ob
                    JOIN {org} o ON (o.id = ob.orgfk AND o.frameworkid = :frameworkid AND o.parentid $sqlparents)
                   WHERE ob.anno = :year 
                     AND ob.tipo = :type";
                    
            $DB->execute($sql, $params);
        } catch (dml_exception $e) {
            $transaction->rollback($e);
        }
        $transaction->allow_commit();
    }
}
