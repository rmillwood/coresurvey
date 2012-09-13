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
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleSurvey.php');

     // add in the warnings
     $status->addMessage('You are about to Delete an Aspect from the Role Survey!');
     $status->addMessage('This cannot be undone!');
     $status->changeTitle('Warning');

     $survey = new RoleSurvey(true);

     $data = $survey->killAspectForm(intval($_GET['r']), intval($_GET['i']));


?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/SnippetHeader.php'); ?>
			<!-- YOUR DATA GOES HERE -->
				<div class="full bpad tcenter" id="snippetclose" style="display: none;">
					<button type="button" class="core_button" id="closewindow">Close Window</button>
				</div>
				<div id="SnippetHeader">Delete Aspect</div>
				<?php
					echo $error->DisplayMessage();
					echo $status->DisplayMessage();
				?>
                                <?php echo $data; ?>

<?php require_once($CFG->dirroot . '/mod/coresurvey/template/SnippetFooter.php'); ?>

