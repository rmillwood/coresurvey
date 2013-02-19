<?php  //the results page from a role analysis showing a radar chart with advice
/*
 'coresurvey' plug-in for Moodle
 Core Education UK
 http://www.core-ed.org.uk
 Author: Richard Millwood, based on code by Nigel Hulls of CORE Education NZ
 E-mail: richard.millwood2core-ed.org.uk
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // coresurvey instance ID

if ($id) {
    $cm         = get_coursemodule_from_id('coresurvey', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $coresurvey  = $DB->get_record('coresurvey', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $coresurvey  = $DB->get_record('coresurvey', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $coresurvey->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('coresurvey', $coresurvey->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}
require_login($course, true, $cm);

add_to_log($course->id, "coresurvey", "view", "view.php?id=$cm->id", "$coresurvey->id");

/// Print the page header
$PAGE->set_url('/mod/coresurvey/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($coresurvey->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * CoreSurvey Stuff
 */
// Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/public.php');

     // include the classes needed
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicRoleSurvey.php');

     $survey = new PublicRoleSurvey();

     // check and make sure that we are looking for a valid survey for this member
     if (   ! isset($survey->member_survey->m_id) OR $survey->member_survey->m_id != $USER->id) {
	 // invalid survey for this member and we need to go back to the overviw
	 coresurvey_page_redirect($CFG->wwwroot . '/mod/coresurvey/view.php?id=' . $cm->id);
     }

    // ok now we need to get the recommended role xml
     $survey->get_primary_roles();

     $summary = $survey->analysis_summary();

    // add the javascript
    $core_page->addJavascript($survey->raphael());

    $core_page->addBody($survey->createJavaAlignment());

     // need to load in jquery ui for the slider......
     //OLD WAY $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.core.js"></script>');
         $PAGE->requires->js('/mod/coresurvey/lib/jquery.ui/js/ui.core.js');
    //OLD WAY $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.slider.js"></script>');
         $PAGE->requires->js('/mod/coresurvey/lib/jquery.ui/js/ui.slider.js');
    $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/css/ui-lightness/jquery-ui-1.7.2.custom.css"/>');
     //Now in syles.css $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/public_survey.css"/>');

     // add tip tip
     //$core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/simpletip/jquery.simpletip-1.3.1.min.js"></script>');

     // load in survey JS
     //OLD WAY $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/public_survey.js"></script>');
    $PAGE->requires->js('/mod/coresurvey/lib/js/public_survey.js');

     // add the raphael library
    //OLD WAY $core_page->addHead('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/raphael-base.1.5.2.js"></script>');
    $PAGE->requires->js('/mod/coresurvey/lib/js/raphael-base.1.5.2.js',true);

     // add the tabs
     //$core_page->addJavascript('$("ul.regtabs").tabs("div.regpanes > div").history();');
     //$core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/tabs2.css">');



     // do the taken date / time
     $taken_date = date("H:i" , $survey->member_survey->end_date) . ' on ' . date("jS M Y" , $survey->member_survey->end_date);

      echo $OUTPUT->header();
 ?>
<div class="tcenter">
    <?php echo '<h1>' . $textr->get_data(11, null, $survey->member_survey->end_date) . '</h1>'; ?>
</div>
<?php echo $summary; ?>
<div id="roleAnalysisResultsInstructions">
    <?php echo $survey->matrix['instructions']['summary']; ?>
</div>

<div id="rose_chart"></div>

<div id="returnButton">
    <form action="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/view.php" method="get">
        <button type="submit">Return to Overview</button>
        <input type="hidden" name="id" value="<?php echo $cm->id; ?>">
    </form>
</div>
<div>
    <?php //echo $results; ?>
</div>
<?php
    echo "<!-- START: Body Files -->";
    echo $core_page->displayBody();
    echo "<!-- END: Body Files -->";
    // javascript
    echo $core_page->displayJavascript();

    /// Finish the page
echo $OUTPUT->footer();
    ?>