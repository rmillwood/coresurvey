<?php
    /**
     * CORE Survey Tool for MacMillan Cancer Support
     * CORE Education
     * http://www.core-ed.net
     * Author: Nigel Hulls
     * E-mail: nigel.hulls@core-ed.net
     */

     // first of all Bootstrap the page using the Moodle config file, this gives
     // us access to the Moodle db functions, and also some other libraries
     // that we may want to pull in :-(

     require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

     // Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/admin.php');

      // include the classes needed
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicRoleSurvey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/PublicSkillSurvey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Result.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleResult.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillResult.php');

      // add the breadcrumb
     $core_page->addBreadcrumb("Survey Simulation");

     // get any saved surveys
     $rdata = PublicRoleSurvey::fetchMemberSurveys();
     $sdata = PublicSkillSurvey::fetchMemberSurveys();

?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminheader.php'); ?>
            <div id="content">
                <h1>Survey Simulation</h1>
                <dl>
                    <dt class="w45 fleft">
                            <strong>Roles Survey</strong>
                                <a href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/simulate/roles.php">
                                    <img src="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/images/play.png" /> Roles Survey
                                </a>
                            <?php
                                echo $rdata;
                            ?>
                     </dt>
                    <dd class="w45 fright">
                        <div>
                            <h2>Skills Survey</h2>
                             <p>
                                <a href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/simulate/skills.php">
                                    <img src="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/images/play.png"/> Skills Survey
                                </a>
                            </p>
                            <?php
                                echo $sdata;
                            ?>
                        </div>
                    </dd>
                </dl>
            </div>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminfooter.php'); ?>