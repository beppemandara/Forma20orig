<?php

/* $Id: l.sampo $ */

define('DATABASE', $CFG->dbname);
define('COHORT', 'mdl_cohort');
define('PROFILO_ECONOMICO', 'mdl_f2_posiz_econom_qualifica');

$macrocategories = array(	array('A','cohortA','Cohort A'),
							array('B','cohortB','Cohort B'),
							array('C','cohortC','Cohort C'),
							array('D','cohortD','Cohort D'),
							array('Dir','cohortDir','Cohort Dir'),
							array('UE','cohortUE','Cohort UE'));

define('MACROCATEGORIES', serialize($macrocategories));

?>