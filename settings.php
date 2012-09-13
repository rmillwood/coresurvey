<?php
/* 
 * settings base file
 * we are reconfiguring this to in effect become a directory page, with
 * branches through to other pages for config.....
 */

// bootstrap the module
require_once($CFG->dirroot . '/mod/coresurvey/lib.php');

//coresurvey_debug($CFG);

/**
 * Roles based administration
 */

$role_str =     '<a href="' . $CFG->wwwroot . '/mod/coresurvey/roles_admin/roles.php" title="Administer the Roles Survey">
                    <img src="' . $CFG->wwwroot . '/mod/coresurvey/images/roles.png" align="absmiddle"/> Administer Roles Survey
                </a>';
$settings->add(new admin_setting_heading("coresurvey_roles_heading", "Roles Survey Component", $role_str));

/**
 * Skills based administration
 */
// skills icon and link
$skill_str =    '<a href="' . $CFG->wwwroot . '/mod/coresurvey/skills_admin/skills.php" title="Administer the Skills Survey">
                    <img src="' . $CFG->wwwroot . '/mod/coresurvey/images/jigsaw.png" align="absmiddle"/> Administer Skills Survey
                </a>';
// add a test setting
$settings->add(new admin_setting_heading("coresurvey_skills_heading", "Skills Survey Component", $skill_str));

/**
 * editable text fields
 */

$text_str =	'<a href="' . $CFG->wwwroot . '/mod/coresurvey/text_admin/text.php" title="Manage Editable text">
		    <img src="' . $CFG->wwwroot . '/mod/coresurvey/images/text.png" align="absmiddle"> Administer Editable text
		</a>';	
// add the setting
$settings->add(new admin_setting_heading("coresurvey_text_heading", "Editable Text", $text_str));

/**
 * Reports
 */

$report_str =   '<a href="' . $CFG->wwwroot . '/mod/coresurvey/reports/index.php" title="View Reports">
                    <img src="' . $CFG->wwwroot . '/mod/coresurvey/images/chart.png" align="absmiddle"/> View Reports
                </a>';
// add a test setting
$settings->add(new admin_setting_heading("coresurvey_reports_heading", "Reports", $report_str));

/**
 * Survey Simulations
 */

//$sim_hdr =  '<a href="' . $CFG->wwwroot . '/mod/coresurvey/simulate/" title="Simulate Surveys">
//                <img src="' . $CFG->wwwroot . '/mod/coresurvey/images/simulate.png" align="absmiddle"/> Simulate Surveys
//             </a>';
//$settings->add(new admin_setting_heading("coresurvey_simulate_heading", "Simulate Surveys", $sim_hdr));
?>

