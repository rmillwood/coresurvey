<?php  // $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $

/**
 * This page prints a particular instance of coresurvey
 *
 * @author  Nigel Hulls <nigel.hulls@core-ed.net>
 * @version $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $
 * @package mod/coresurvey
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // coresurvey instance ID

if ($id) {
    if (! $cm = get_coursemodule_from_id('coresurvey', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = $DB->get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $coresurvey = $DB->get_record('coresurvey', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

} else if ($a) {
    if (! $coresurvey = $DB->get_record('coresurvey', 'id', $a)) {
        error('Course module is incorrect');
    }
    if (! $course = $DB->get_record('course', 'id', $coresurvey->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('coresurvey', $coresurvey->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

add_to_log($course->id, "coresurvey", "view", "instructions.php?id=$cm->id", "$coresurvey->id");

/// Print the page header
$strcoresurveys = get_string('modulenameplural', 'coresurvey');
$strcoresurvey  = get_string('modulename', 'coresurvey');

$navlinks = array();
$navlinks[] = array('name' => $strcoresurveys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($coresurvey->name), 'link' => "view.php?id=" . $course->id, 'type' => 'activityinstance');
$navlinks[] = array('name' => "Instructions", 'link' => '');

$navigation = build_navigation($navlinks);

/**
 * CoreSurvey Stuff
 */
// Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/public.php');

      // include the classes needed
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');

     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleSurvey.php');

     $survey = new RoleSurvey(true);

     // change the session so that it exists
     $_SESSION['survey_ins'] = true;


print_header_simple(format_string($coresurvey->name), '', $navigation, '', $core_page->displayHead(), true,
              update_module_button($cm->id, $course->id, $strcoresurvey), navmenu($course, $cm));

/// Print the main part of the page
?>
<div id="content">
    <h1>
	Summary
    </h1>
    <?php echo $survey->matrix['instructions']['summary']; ?>
    <hr>
    <h1>
	Warning
    </h1>
    <?php echo $survey->matrix['instructions']['warning']; ?>
    <div class="dpad tright">
	<a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=<?php echo $course->id; ?>">
	    <button type="button">Cancel</button>
	</a>
    </div>
    <hr>
    <h1>
	Instructions
    </h1>
    <?php echo $survey->matrix['instructions']['instructions']; ?>
    <div class="dpad tright">
	<a href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/view.php?id=<?php echo $course->id; ?>&ins=yes">
	    <button type="button">Continue</button>
	</a>
    </div>
</div>
<?php
// javascript
$core_page->displayJavascript();

/// Finish the page
print_footer($course);

?>