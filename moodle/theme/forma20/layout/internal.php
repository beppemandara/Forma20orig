<?php
$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$hassidetop = $PAGE->blocks->region_has_content('side-top', $OUTPUT);
$hassidedxheader = $PAGE->blocks->region_has_content('side-dxheader', $OUTPUT);
$hassidebottommain = $PAGE->blocks->region_has_content('side-bottommain', $OUTPUT);
echo $OUTPUT->doctype(); ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <?php echo $OUTPUT->standard_head_html() ?>
    <script type="text/javascript">
jQuery(document).ready(function(){
 jQuery(".megamenu").megamenu({ 'activate_action':'click','show_method':'fadeIn', 'hide_method': 'fadeOut', 'enable_js_shadow':false});
});
</script>
<script type="text/javascript">
	$(document).ready(function() {
		$(".fancybox").fancybox();
	});
</script>	

</head>
<body id="<?php p($PAGE->bodyid); ?>" class="<?php p($PAGE->bodyclasses); ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>
<div id="page" class="container_16">
<?php if ($PAGE->heading || (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar())) { ?>
	<!-- start nuova regione -->
	<div id="region-top" class="grid_16 alpha omega">
		<?php if ($hassidetop) { ?>
			<?php echo $OUTPUT->login_info(); 
      require_once("menu.php");  
      ?>
		<?php } else { ?>
			<?php echo $OUTPUT->login_info(); ?>
		<?php } ?>
	</div>
	<!-- end nuova regione -->
	<div id="page-header" class="grid_6 alpha">
        <?php if ($PAGE->heading) { ?>
            <h1 class="headermain"><a href="/index.php"><strong><?php echo $PAGE->heading ?></strong></a></h1>
            <div class="headermenu"><?php
                //echo $OUTPUT->login_info();
                if (!empty($PAGE->layout_options['langmenu'])) {
                    echo $OUTPUT->lang_menu();
                }
                echo $PAGE->headingmenu
            ?></div>
        <?php } ?>
        <?php if (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar()) { ?>
            <div class="navbar clearfix">
                <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
                <div class="navbutton"> <?php echo $PAGE->button; ?></div>
            </div>
        <?php } ?>
    </div>
    <!-- start nuova regione -->
    <?php if ($hassidedxheader) { ?>
	    <div id="region-dxheader" class="grid_10 omega">
	    	<?php echo $OUTPUT->blocks_for_region('side-dxheader') ?>
	    </div>
	<?php } ?>
    <!-- end nuova regione -->
<?php } ?>
<div id="page-content" class="grid_16 alpha omega">
    <div id="region-main-box" >
        <div id="region-post-box">

        
                <div id="region-main" class="grid_16 alpha">
                    <div class="region-content">
                       <?php echo $OUTPUT->main_content() ?>
                        <?php if ($hassidebottommain) { ?>
                    		 <?php echo $OUTPUT->blocks_for_region('side-bottommain') ?>
                    	<?php } ?>
                    </div>
                </div>
        


			
 
                <?php //if ($hassidepost) { ?>
           <!--     <div id="region-post" class="grid_7 omega">
                    <div class="region-content" >      -->
                        <?php //echo $OUTPUT->blocks_for_region('side-post') ?>
                        <?php //print_object($USER); ?>
                   <!-- </div>
                </div> -->
            <?php// } ?>
        </div>
    </div>
</div>
<?php if (empty($PAGE->layout_options['nofooter'])) { ?>
    <div id="page-footer" class="grid_16 alpha omega">
		<ul>
			<li><a href="http://www.regione.piemonte.it" class="blank" id="banner_RP" title="Regione Piemonte"><strong>Regione Piemonte</strong></a></li>
			<li><a href="http://www.consiglioregionale.piemonte.it" class="blank" id="banner_CRP" title="Consiglio Regionale del Piemonte"><strong>Consiglio Regionale del Piemonte</strong></a></li>
			<li><a href="http://www.csipiemonte.it" class="blank" id="banner_CSI" title="CSI-Piemonte"><strong>CSI-Piemonte</strong></a></li>
              <li><a class="fancybox" href="#inline" title="Men&ugrave; Amministrazione">Menu di amministrazione</a></li>
		</ul>
    </div>
    <?php } ?>
</div>
	<div id="inline" style="width:400px;display: none;">
	<?php echo $OUTPUT->blocks_for_region('admin-menu') ?>
	</div>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>