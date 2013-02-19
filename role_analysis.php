<?php  // Undertake a role analysis
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

//CoreSurvey Stuff

// Now add in our bootstrap file to load in core classes.
require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/public.php');

// include the classes needed
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicRoleSurvey.php');

// stupid hack so that the session / survey can be reset
if (isset($_POST['reset']) && $_POST['reset'] == 1) {
    unset($_SESSION['role_coresurvey']);
}

$survey = new PublicRoleSurvey();

// if the survey has been taken, we need to redirect to the results page...
if ($survey->surveyTaken()) {
    // store the results in the session
    $survey->storeSession();
    coresurvey_page_redirect('role_results.php?id=' . $cm->id . '&s=' . $survey->new_member_result_id);
}

$data = $survey->showSurvey();

$core_page->addBody($survey->createJavaAlignment());

$survey_instructions = isset($survey->matrix['instructions']['role_summary']) ? $survey->matrix['instructions']['role_summary'] : 'Instructions';

// need to load in jquery ui for the slider......
$PAGE->requires->js('/mod/coresurvey/lib/jquery.ui/js/ui.core.js');
$PAGE->requires->js('/mod/coresurvey/lib/jquery.ui/js/ui.slider.js');
$core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui.1.8.7/south-street/jquery-ui-1.8.7.custom.css"/>');

// load in survey JS
$PAGE->requires->js('/mod/coresurvey/lib/js/public_survey.js');

echo $OUTPUT->header();

?>

<div>
    <h1><?php echo $textr->get_data(2); ?></h1>
    <p><?php echo $survey_instructions; ?></p>
    <?php echo $data; ?>
</div>

<?php
echo "<!-- START: Body Files -->";
echo $core_page->displayBody();
echo "<!-- END: Body Files -->";

/// Finish the page
echo $OUTPUT->footer();

?>
