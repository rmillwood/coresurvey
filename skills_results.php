<?php  // $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $

/**
 * This page prints the Skills survey
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
$navlinks[] = array('name' => $strcoresurveys, 'link' => "view.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($coresurvey->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

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
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/skills.css"/>');

     // load in survey JS
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/public_survey.js"></script>');

     // add the tabs
     $core_page->addJavascript('$("ul.regtabs").tabs("div.regpanes > div").history();');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/tabs2.css">');

     // ok now we need to get the recommended role xml
     $Recommended = $survey->fetchRecommendedRole();


 print_header_simple(format_string($coresurvey->name), '', $navigation, '', $core_page->displayHead(), true,
              update_module_button($cm->id, $course->id, $strcoresurvey), navmenu($course, $cm));
?>
<h1>Skills Survey Simulation Results</h1>
<p class="dpad">
    Richard, we need some text in here to describe this page
</p>
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

<?php
    echo "<!-- START: Body Files -->";
    echo $core_page->displayBody();
    echo "<!-- END: Body Files -->";
    // javascript
    echo $core_page->displayJavascript();

    /// Finish the page
    print_footer($course);
?>

