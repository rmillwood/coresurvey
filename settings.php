<?php
/*
 'coresurvey' plug-in for Moodle
 Core Education UK
 http://www.core-ed.org.uk
 Author: Richard Millwood, based on code by Nigel Hulls of CORE Education NZ
 E-mail: richard.millwood2core-ed.org.uk
 */

/*
settings base file
we are reconfiguring this to in effect become a directory page, with
branches through to other pages for config.....
*/

// bootstrap the module
require_once($CFG->dirroot . '/mod/coresurvey/lib.php');

$role_str = '<a href="' . $CFG->wwwroot . '/mod/coresurvey/roles_admin/roles.php" title="Roles"><img src="' . $CFG->wwwroot . '/mod/coresurvey/images/roles.png" align="absmiddle"/> Administer Roles</a>';
$settings->add(new admin_setting_heading("coresurvey_roles_heading", "Roles", $role_str));

$skill_str = '<a href="' . $CFG->wwwroot . '/mod/coresurvey/skills_admin/skills.php" title="Competencies"><img src="' . $CFG->wwwroot . '/mod/coresurvey/images/jigsaw.png" align="absmiddle"/> Administer Competencies</a>';
$settings->add(new admin_setting_heading("coresurvey_skills_heading", "Competencies", $skill_str));

$text_str =	'<a href="' . $CFG->wwwroot . '/mod/coresurvey/text_admin/text.php" title="Text"><img src="' . $CFG->wwwroot . '/mod/coresurvey/images/text.png" align="absmiddle"> Administer Text</a>';
$settings->add(new admin_setting_heading("coresurvey_text_heading", "Text", $text_str));

$report_str = '<a href="' . $CFG->wwwroot . '/mod/coresurvey/reports/index.php" title="Reports"><img src="' . $CFG->wwwroot . '/mod/coresurvey/images/chart.png" align="absmiddle"/> View Reports</a>';
$settings->add(new admin_setting_heading("coresurvey_reports_heading", "Reports", $report_str));

?>

