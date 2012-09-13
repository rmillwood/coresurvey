<?php
    /**
     * CORE Survey Tool for MacMillan Cancer Support
     * CORE Education
     * http://www.core-ed.net
     * Author: Nigel Hulls
     * E-mail: nigel.hulls@core-ed.net
     */

     // first of all Bootstrap the page using the Moodle config file, this gives
     // us access to the Moodle db functions, and also some other libraries
     // that we may want to pull in :-(

     require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

     // Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/admin.php');

      // include the classes needed
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicRoleSurvey.php');

     // add the breadcrumb
     $core_page->addBreadcrumb('<a href="' . $CFG->wwwroot . '/mod/coresurvey/simulate/">Survey Simulations</a>');
     $core_page->addBreadcrumb('<a href="' . $CFG->wwwroot . '/mod/coresurvey/simulate/roles.php">Roles Survey Simulation</a>');
     $core_page->addBreadcrumb("Roles Survey Simulation Results");

     $survey = new PublicRoleSurvey();

     $xmldata = $survey->xmlResults();

    $results = $survey->showCompletedSurvey();

     // ad din the amcharts example
     $core_page->addHead('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/chart/swfobject.js"></script>');

     $core_page->addBody($survey->createJavaAlignment());

     // need to load in jquery ui for the slider......
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.core.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.slider.js"></script>');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/css/ui-lightness/jquery-ui-1.7.2.custom.css"/>');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/public_survey.css"/>');

     // load in survey JS
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/public_survey.js"></script>');

     // add the tabs
     $core_page->addJavascript('$("ul.regtabs").tabs("div.regpanes > div").history();');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/tabs2.css">');

     // ok now we need to get the recommended role xml
     $Recommended = $survey->fetchRecommendedRole();
?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminheader.php'); ?>
            <div id="content">
                <h1>Roles Survey Simulation Results</h1>
                <!-- tabs -->
                <ul class="regtabs">
                    <li><a href="#SurveyResults">Survey Results</a></li>
                    <li><a href="#RecommendedRole">Recommended Role</a></li>
                </ul>
                <!-- Panes -->
                <div class="regpanes">
                    <div class="tabbkgd">
                        <div id="flashcontentone">
                            <strong>You need to upgrade your Flash Player</strong>
                        </div>

                        <script type="text/javascript">
                                // <![CDATA[
                                var so = new SWFObject("/mod/coresurvey/lib/chart/amradar.swf", "rolesurvey", "900", "600", "8", "#FFFFFF");
                                so.addVariable("settings_file", encodeURIComponent("/mod/coresurvey/lib/chart/role_settings.xml"));
                                so.addVariable("chart_data", "<?php echo $xmldata; ?>");
                                so.write("flashcontentone");
                                // ]]>
                        </script>
                    </div>
                    <div class="tabbkgd">
                        <?php echo $Recommended; ?>
                        <div id="flashcontenttwo">
                            <strong>You need to upgrade your Flash Player</strong>
                        </div>
                    </div>
                </div>
					<div class="dpad">
						<?php echo $results; ?>
					</div>
            </div>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminfooter.php'); ?>