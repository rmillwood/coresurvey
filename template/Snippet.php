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
?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/SnippetHeader.php'); ?>
			<!-- YOUR DATA GOES HERE -->
				<div class="full bpad tcenter" id="snippetclose" style="display: none;">
					<button type="button" class="core_button" id="closewindow">Close Window</button>
				</div>
				<div id="SnippetHeader">Hello</div>
				<?php 
					echo $error->DisplayMessage(); 
					echo $status->DisplayMessage();
				?>
			
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/SnippetFooter.php'); ?>

