<?php // View the overview page with instructions, surveys paused and completed

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('a', 0, PARAM_INT);  // coresurvey instance ID

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

add_to_log($course->id, "coresurvey", "view", "view.php?id=$cm->id", "$coresurvey->id", $cm->id);

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
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicRoleSurvey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicSkillSurvey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Result.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleResult.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillResult.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleSurvey.php');

     // get any saved surveys
     $rdata = PublicRoleSurvey::fetchMemberSurveys();
     $sdata = PublicSkillSurvey::fetchMemberSurveys();

     // need to get a list of all of the Assessments available
     $ca = PublicSkillSurvey::fetchallcompetencytypes();

     $survey = new RoleSurvey(true);



echo $OUTPUT->header();

/// Print the main part of the page
echo $OUTPUT->heading($textr->get_data(1));
?>
<div id="content">
		<div class="dpad">
		    <?php echo $survey->matrix['instructions']['instructions']; ?>
		</div>
                <dl>
                    <dd class="w45 fleft">
                        <div>
				<form action="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/roles.php" method="GET">
				    <button type="submit"><?php echo $textr->get_data(2); ?></button>
				    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                                </form>
                            <?php
                                echo $rdata['html'];
                            ?>
                        </div>
                    </dd>
                    <dd class="w45 fright">
                        <div>
                            <?php echo $ca; ?>
                            <?php
                                echo $sdata['html'];
                            ?>
                        </div>
                    </dd>
                </dl>
		<div class="fclear tright dpad">
		    <a href="<?php echo $CFG->wwwroot; ?>/course/view.php?id=<?php echo $course->id; ?>">
			<button type="button">Quit</button>
		    </a>
		</div>
            </div>
<?php
// javascript
$core_page->displayJavascript();

/// Finish the page
echo $OUTPUT->footer();

?>
