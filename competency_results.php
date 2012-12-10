<?php  //the results page from a role analysis showing a radar chart with advice

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


/**
 * CoreSurvey Stuff
*/
// Now add in our bootstrap file to load in core classes.
require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/public.php');

// include the classes needed
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicSkillSurvey.php');

$survey = new PublicSkillSurvey();

// check and make sure that we are looking for a valid survey for this member
if (   ! isset($survey->member_survey->m_id)
                        OR $survey->member_survey->m_id != $USER->id) {
    // invalid survey for this member and we need to go back to the overviw
    coresurvey_page_redirect($CFG->wwwroot . '/mod/coresurvey/view.php?id=' . $cm->id);
}


// need to load in jquery ui for the slider......
$PAGE->requires->js('/mod/coresurvey/lib/jquery.ui/js/ui.core.js');
$PAGE->requires->js('/mod/coresurvey/lib/jquery.ui/js/ui.slider.js');

// load in survey JS
$PAGE->requires->js('/mod/coresurvey/lib/js/public_survey.js');


$analysis = $survey->tabulate($USER->firstname . ' ' . $USER->lastname);



      echo $OUTPUT->header();
?>
<div>
    <?php echo $analysis; ?>
</div>
<div class="core_survey_return_to_overview_button">
    <a
        href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/view.php?id=<?php echo $cm->id; ?>">
        <button type="button">Return to Overview</button>
    </a>
</div>
<div class="coresurvey_print_button">
    <form>
        <input type="button" value=" Print this guidance"
            onclick="window.print();return false;" />
    </form>
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

