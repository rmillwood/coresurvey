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

     // load extra model
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/textr.php');

     $textr = new textr();

     $core_page->addBreadcrumb("Editable text");

     // include ui.jquery
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/jquery-1.3.2.min.js"></script>');

     // include the classes required
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');

     $msg = '';

     // do we need to save the text??
     if (isset($_POST['go']) && $_POST['go'] == 1) {
	 $data = array(
	     1	=> isset($_POST['1']) ? $_POST['1'] : '',
	     2	=> isset($_POST['2']) ? $_POST['2'] : '',
	     3	=> isset($_POST['3']) ? $_POST['3'] : '',
	     4	=> isset($_POST['4']) ? $_POST['4'] : '',
	     5	=> isset($_POST['5']) ? $_POST['5'] : '',
	     6	=> isset($_POST['6']) ? $_POST['6'] : '',
	     7	=> isset($_POST['7']) ? $_POST['7'] : '',
	     8	=> isset($_POST['8']) ? $_POST['8'] : '',
	     9	=> isset($_POST['9']) ? $_POST['9'] : '',
	     10	=> isset($_POST['10']) ? $_POST['10'] : '',
	     11	=> isset($_POST['11']) ? $_POST['11'] : '',
	     12	=> isset($_POST['12']) ? $_POST['12'] : '',
	     13	=> isset($_POST['13']) ? $_POST['13'] : '',
	     14	=> isset($_POST['14']) ? $_POST['14'] : '',
	     15	=> isset($_POST['15']) ? $_POST['15'] : '',
	     16	=> isset($_POST['16']) ? $_POST['16'] : '',
	     17	=> isset($_POST['17']) ? $_POST['17'] : '',
	     18	=> isset($_POST['18']) ? $_POST['18'] : '',
	 );


	 if ($textr->update($data)) {
	     $msg = 'Text has been updated';
	     coresurvey_page_redirect($CFG->wwwroot . '/mod/coresurvey/text_admin/text.php');
	 } else {
	     $msg = 'Error, could not update :-(';
	 }

     }



?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminheader.php'); ?>
            <div id="content">
                <h1>Editable Text</h1>
		<?php echo $msg; ?>
		<p class="dpad">
		    Complete the form to edit the text. You may use the tokens listed below:
		</p>
		<ul>
		    <li>
			!##name##! = Logged in User's name
		    </li>
		    <li>
			!##number##! = Number
		    </li>
		    <li>
			!##date##! = Date
		    </li>
		</ul>
                <div class="dpad tcenter">
		    <form action="" method="post" id="redit_text" class="tleft">
			<fieldset class="dpad adminform">
			    <legend>
				Overview
			    </legend>
			    <label>Title</label>
			    <input type="text" name="1" value="<?php echo $textr->get_raw_data(1); ?>" class="w95">
			    <label>Create Role Analysis Button</label>
			    <input type="text" name="2" value="<?php echo $textr->get_raw_data(2); ?>" class="w95">
			    <label>Create Competency Assessment Button</label>
			    <input type="text" name="3" value="<?php echo $textr->get_raw_data(3); ?>" class="w95">
			    <label>Paused Role Analysis</label>
			    <input type="text" name="5" value="<?php echo $textr->get_raw_data(5); ?>" class="w95">
			    <label>Completed Role Analysis</label>
			    <input type="text" name="6" value="<?php echo $textr->get_raw_data(6); ?>" class="w95">
			    <label>Paused Competency Assessment</label>
			    <input type="text" name="7" value="<?php echo $textr->get_raw_data(7); ?>" class="w95">
			    <label>Completed Competency Assessment</label>
			    <input type="text" name="8" value="<?php echo $textr->get_raw_data(8); ?>" class="w95">
			</fieldset>
			<fieldset class="dpad adminform">
			    <legend>
				Role Analysis
			    </legend>
			    <label>Pause Survey Title</label>
			    <input type="text" name="13" value="<?php echo $textr->get_raw_data(13); ?>" class="w95">
			    <label>Pause Survey Button</label>
			    <input type="text" name="14" value="<?php echo $textr->get_raw_data(14); ?>" class="w95">
			</fieldset>
			<fieldset class="dpad adminform">
			    <legend>
				Competency Assessment
			    </legend>
			    <label>Title</label>
			    <input type="text" name="12" value="<?php echo $textr->get_raw_data(12); ?>" class="w95">
			    <label>Pause Survey Title</label>
			    <input type="text" name="9" value="<?php echo $textr->get_raw_data(9); ?>" class="w95">
			    <label>Pause Survey Button</label>
			    <input type="text" name="10" value="<?php echo $textr->get_raw_data(10); ?>" class="w95">
			</fieldset>
			<fieldset class="dpad adminform">
			    <legend>
				Role Analysis Summary
			    </legend>
			    <label>Title</label>
			    <input type="text" name="11" value="<?php echo $textr->get_raw_data(11); ?>" class="w95">
			</fieldset>
			<fieldset class="dpad adminform">
			    <legend>
				Competency Assessment Summary
			    </legend>
			    <label>Title</label>
			    <input type="text" name="15" value="<?php echo $textr->get_raw_data(15); ?>" class="w95">
			    <label>Column One Title</label>
			    <input type="text" name="16" value="<?php echo $textr->get_raw_data(16); ?>" class="w95">
			    <label>Column Two Title</label>
			    <input type="text" name="17" value="<?php echo $textr->get_raw_data(17); ?>" class="w95">
			    <label>Column Three Title</label>
			    <input type="text" name="18" value="<?php echo $textr->get_raw_data(18); ?>" class="w95">
			</fieldset>
			<fieldset class="dpad adminform tcenter">
			    <legend>
				Submit
			    </legend>
			    <button type="submit">Submit</button>
			    <input type="hidden" name="go" value="1">
			</fieldset>
		    </form>
		</div>
            </div>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminfooter.php'); ?>