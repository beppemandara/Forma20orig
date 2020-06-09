<?php
// nome della cartella del tema
$THEME->name    = 'forma20';
// tema da cui eredita i css
$THEME->parents = array('base');
// il css
$THEME->sheets = array('home','common','internal','960','ie6','jquerymegamenu','jqueryfancybox');
// definizione del layout per il tema
$THEME->layouts = array(
    'base' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
    ),
    'standard' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
    ),
    'course' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post'
    ),
    'coursecategory' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
    ),
    'incourse' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
    ),
    'frontpage' => array(
        'file' => 'standard.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
    ),
    'admin' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-pre',
    ),
    'mydashboard' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
        'options' => array('langmenu'=>true),
    ),
    'mypublic' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
    ),
    'login' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
        'options' => array('nofooter'=>true),
    ),
    'popup' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
        'options' => array('nofooter'=>true),
    ),
    'frametop' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
        'options' => array('nofooter'=>true),
    ),
    'maintenance' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
        'options' => array('nofooter'=>true, 'nonavbar'=>true),
    ),
    'print' => array(
        'file' => 'internal.php',
        'regions' => array('side-pre', 'side-post', 'side-top', 'side-dxheader', 'side-bottommain','admin-menu'),
        'defaultregion' => 'side-post',
        'options' => array('nofooter'=>true, 'nonavbar'=>false),
    ),
);
// file javascript da includere in ogni pagina
$THEME->javascripts = array('jquery-1.6.4.min', 'jquery.megamenu', 'jquery.fancybox');
$THEME->javascripts_footer = array();