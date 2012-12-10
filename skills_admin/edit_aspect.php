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

     require_once('../../../config.php');

     // Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/admin.php');

     // validate
     if (! isset($_GET['r']) || ! isset($_GET['i'])) {
         echo "No Role or Index defined!!!";
         exit;
     }

     // include the classes needed
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillSurvey.php');

      // add in the files necessary for the sliders..
     // need to load in jquery ui for the slider......
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.core.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.slider.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/ckeditor/ckeditor.js"></script>');

     $survey = new SkillSurvey(true);

     $data = $survey->editAspectForm(intval($_GET['r']), intval($_GET['i']));

     $core_page->addJavascript($data['java']);
?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/SnippetHeader.php'); ?>
			<!-- YOUR DATA GOES HERE -->
				<div class="full bpad tcenter" id="snippetclose" style="display: none;">
					<button type="button" class="core_button" id="closewindow">Close Window</button>
				</div>
				<div id="SnippetHeader">Edit Skill / Competency</div>
				<?php
					echo $error->DisplayMessage();
					echo $status->DisplayMessage();
				?>
                                <?php echo $data['html']; ?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/SnippetFooter.php'); ?>

