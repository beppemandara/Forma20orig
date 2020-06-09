<?php
// $Id: remove_forzatura.php 979 2013-01-15 17:04:46Z c.arnolfo $
require_once '../../config.php';
require_once 'lib.php';


global $USER;
$forzature_ids = $_POST['forzatura_id'];

if (!empty($forzature_ids))
{
        foreach ($forzature_ids as $forzatura_id)
        {
                $retval = delete_forzatura($forzatura_id);
                if (!$retval) echo '<div>'.get_string('error_in_delete','block_f2_gestione_risorse').' su forzatura id = '.$forzatura_id.'</div>';
        }
        redirect(new moodle_url('forzature.php'));
}