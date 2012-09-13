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
     

	// stupid hack so that the session / survey can be reset
	if (isset($_POST['reset']) && $_POST['reset'] == 1) {
		unset($_SESSION['role_coresurvey']);
	}

     // add the breadcrumb
     $core_page->addBreadcrumb('<a href="' . $CFG->wwwroot . '/mod/coresurvey/simulate/">Survey Simulations</a>');
     $core_page->addBreadcrumb("Roles Survey Simulation");

     $survey = new PublicRoleSurvey();

     // if the survey has been taken, we need to redirect to the results page...
     if ($survey->surveyTaken()) {
         // store the results in the session
         $survey->storeSession();
         coresurvey_page_redirect('/mod/coresurvey/simulate/role_results.php');
     }

     //coresurvey_debug($survey->data);

     $data = $survey->showSurvey();

     $core_page->addBody($survey->createJavaAlignment());

     // need to load in jquery ui for the slider......
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.core.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.slider.js"></script>');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/css/ui-lightness/jquery-ui-1.7.2.custom.css"/>');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/public_survey.css"/>');

     // load in survey JS
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/public_survey.js"></script>');

     // load in the form wizard
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/formToWizard.js"></script>');
     $core_page->addJavascript('$("#surveyform").formToWizard({ submitButton: "submitsurvey" })');

?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminheader.php'); ?>
            <div id="content">
                <h1>Simulate Roles Survey</h1>
				
                <?php echo $data; ?>
            </div>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminfooter.php'); ?>