<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is built using the bootstrapbase template to allow for new theme's using
 * Moodle's new Bootstrap theme engine
 *
 * @package     theme_essential
 * @copyright   2013 Julian Ridden
 * @copyright   2014 Gareth J Barnard, David Bezemer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (empty($PAGE->layout_options['nofooter'])) {
    ?>
    <footer role="contentinfo" id="page-footer">
        <div class="container-fluid">
            <?php echo theme_essential_edit_button('theme_essential_footer'); ?>
            <div class="row-fluid footerblocks">
                <div class="span4 pull-left">
                    <div class="column">
                        <?php echo $OUTPUT->blocks('footer-left'); ?>
                    </div>
                </div>
                <div class="span4 center">
                    <div class="column">
                        <?php echo $OUTPUT->blocks('footer-middle'); ?>
                    </div>
                </div>
                <div class="span4 pull-right">
                    <div class="column">
                        <?php echo $OUTPUT->blocks('footer-right'); ?>
                    </div>
                </div>
            </div>
            <div class="footerlinks row-fluid">
                <!-- START MODIFICHE -->
                <ul>
                  <li>
                    <a href="http://www.regione.piemonte.it" class="blank" id="banner_RP" title="Regione Piemonte">
                      <strong>Regione Piemonte</strong>
                    </a>
                  </li>

                  <li>
                    <a href="http://www.consiglioregionale.piemonte.it" class="blank" id="banner_CRP" title="Consiglio Regionale del Piemonte">
                      <strong>Consiglio Regionale del Piemonte</strong>
                    </a>
                  </li>

                  <li>
                    <a href="http://www.csipiemonte.it" class="blank" id="banner_CSI" title="CSI-Piemonte">
                      <strong>CSI-Piemonte</strong>
                    </a>
                  </li>
                </ul>
                <br/>
                <!-- END MODIFICHE -->
                <!-- 2017-09-18 hr/-->
                <span class="helplink"><?php //echo page_doc_link(get_string('moodledocslink')); ?></span><!-- 2017-09-18 -->
                <?php if ($hascopyright) { ?>
                    <span class="copy">&copy;<?php echo userdate(time(), '%Y') . ' ' . $hascopyright; ?></span>
                <?php } ?>
                <?php if ($hasfootnote) {
                    echo '<div class="footnote span12">' . $hasfootnote . '</div>';
                } ?>
            </div>
            <div class="footerperformance row-fluid">
                <?php echo $OUTPUT->standard_footer_html(); ?>
            </div>
        </div>
    </footer>
    <a href="#top" class="back-to-top" ><i class="fa fa-angle-up "></i></a>
<?php } ?>

    <script type="text/javascript">
        jQuery(document).ready(function () {
            <?php
            if (theme_essential_not_lte_ie9()) {
              echo "jQuery('#essentialnavbar').affix({";
              echo "offset: {";
              echo "top: $('#page-header').height()";
              echo "}";
              echo "});";
              if ($breadcrumbstyle == '1') {
                  echo "$('.breadcrumb').jBreadCrumb();";
              }
            }
            if (theme_essential_get_setting('fitvids')) {
                echo "$('#page').fitVids();";
            }
            ?>
        });
    </script>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
