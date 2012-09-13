<?php // $Id: index.php,v 1.7.2.3 2009/08/31 22:00:00 mudrd8mz Exp $

/**
 * This page lists all the instances of coresurvey in a particular course
 *
 * @author  Your Name <your@email.address>
 * @version $Id: index.php,v 1.7.2.3 2009/08/31 22:00:00 mudrd8mz Exp $
 * @package mod/coresurvey
 */

/// Replace coresurvey with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_course_login($course);

add_to_log($course->id, 'coresurvey', 'view all', "index.php?id=$course->id", '');


/// Get all required stringscoresurvey

//$strcoresurveys = get_string('modulenameplural', 'coresurvey');
//$strcoresurvey  = get_string('modulename', 'coresurvey');
$strcoresurveys = get_string('modulenameplural', 'coresurvey');
$strcoresurvey  = get_string('modulename', 'coresurvey');


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strcoresurveys, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strcoresurveys, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $coresurveys = get_all_instances_in_course('coresurvey', $course)) {
    notice('There are no instances of coresurvey', "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($coresurveys as $coresurvey) {
    if (!$coresurvey->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$coresurvey->coursemodule.'">'.format_string($coresurvey->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$coresurvey->coursemodule.'">'.format_string($coresurvey->name).'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($coresurvey->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

print_heading($strcoresurveys);
print_table($table);

/// Finish the page

print_footer($course);

?>
