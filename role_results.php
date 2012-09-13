<?php  // $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $

/**
 * This page prints the Role survey
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

add_to_log($course->id, "coresurvey", "view", "view.php?id=$cm->id", "$coresurvey->id");

/// Print the page header
$strcoresurveys = get_string('modulenameplural', 'coresurvey');
$strcoresurvey  = get_string('modulename', 'coresurvey');

$navlinks = array();
$navlinks[] = array('name' => format_string($coresurvey->name), 'link' => "view.php?id=$cm->id", 'type' => 'activity');
$navlinks[] = array('name' => 'Overview', 'link' => "view.php?id=$cm->id", 'type' => 'activityinstance');
$navlinks[] = array('name'  => 'Role Analysis', 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

/**
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
     if (   ! isset($survey->member_survey->m_id)
	     OR $survey->member_survey->m_id != $USER->id) {
	 // invalid survey for this member and we need to go back to the overviw
	 coresurvey_page_redirect($CFG->wwwroot . '/mod/coresurvey/view.php?id=' . $cm->id);
     }

     //$xmldata = $survey->xmlResults();

    //$results = $survey->showCompletedSurvey();

    // ok now we need to get the recommended role xml
     $survey->get_primary_roles();

     $summary = $survey->analysis_summary();

    // add the javascript
    $core_page->addJavascript($survey->raphael());

    $core_page->addBody($survey->createJavaAlignment());

     // need to load in jquery ui for the slider......
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.core.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.slider.js"></script>');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/css/ui-lightness/jquery-ui-1.7.2.custom.css"/>');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/public_survey.css"/>');

     // add tip tip
     //$core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/simpletip/jquery.simpletip-1.3.1.min.js"></script>');

     // load in survey JS
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/public_survey.js"></script>');

     // add the raphael library
     //$core_page->addHead('<script rel="stylesheet" type="text/css" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/raphael.js"></script>');
     $core_page->addHead('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/raphael-base.1.5.2.js"></script>');

     // add the tabs
     //$core_page->addJavascript('$("ul.regtabs").tabs("div.regpanes > div").history();');
     //$core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/tabs2.css">');
     


     // do the taken date / time
     $taken_date = date("H:i" , strtotime($survey->member_survey->end_date)) . ' on ' . date("jS M Y" , strtotime($survey->member_survey->end_date));

 print_header_simple(format_string($coresurvey->name), '', $navigation, '', $core_page->displayHead(), true,
              update_module_button($cm->id, $course->id, $strcoresurvey), navmenu($course, $cm));
?>
<div class="tcenter">
    <?php echo $textr->get_data(11, null, $survey->member_survey->end_date); ?>
</div>
<?php echo $summary; ?>
<div class="dpad">
    <?php echo $survey->matrix['instructions']['summary']; ?>
</div>
<div id="rose_chart" class="tcenter"></div>
<div class="dpad tright">
    <form action="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/view.php" method="get">
	<button type="submit">
	    Return to Overview
	</button>
	<input type="hidden" name="id" value="<?php echo $cm->id; ?>">
    </form>
</div>
<div class="dpad">
        <?php //echo $results; ?>
</div>
<?php
    echo "<!-- START: Body Files -->";
    echo $core_page->displayBody();
    echo "<!-- END: Body Files -->";
    // javascript
    echo $core_page->displayJavascript();

    /// Finish the page
    print_footer($course);
?>