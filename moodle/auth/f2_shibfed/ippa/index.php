<?php
/*
 * $Id: index.php 737 2012-11-23 09:19:34Z l.moretto $
 * 
 * This page needs to be able to be accessed from the outside.
 */
require_once('../../../config.php');
require_once('../lib.php');

$path = dirname(__FILE__);
//print_r($path."<br/>");

include('../i_shib.php');