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

     $core_page->addBreadcrumb("Reports");

     // include ui.jquery
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/js/jquery-1.3.2.min.js"></script>');

     // include the classes required
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Reports.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleSurvey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillSurvey.php');

     $summary = Reports::summary();

     // last year
     $last_year = date('Y') - 1;

     $lastmonth_timestamp = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));

     // javascript
     $core_page->addJavascript('$("#start").datepicker({ dateFormat: "dd-mm-yy" });');
     $core_page->addJavascript('$("#end").datepicker({ dateFormat: "dd-mm-yy" });');

     $core_page->addJavascript('
	$("#last_year").click(function() {
	    $("#start").val("01-01-' . $last_year . '");
	    $("#end").val("31-12-' . $last_year . '");
	});
    ');

     $core_page->addJavascript('
	 $("#last_month").click(function() {
	    $("#start").val("01-' . date('m-Y', $lastmonth_timestamp) . '");
	    $("#end").val("31-' . date('m-Y', $lastmonth_timestamp) . '");
	 });
     ');

     $report = $msg = $pdf = '';

     // do we need to do the stats
     if (isset($_POST['start']) && isset($_POST['end'])) {
	 // check and make sure that the dates are valid
	 $start = $_POST['start'];
	 $end = $_POST['end'];

	 if ($start > $end) {
	     $msg = "Invalid dates. Your start date must be less than your end date";
	 } else {
	     $r = new Reports();
	     $report = $r->main_report($start, $end);
	     $pdf =	'<div class="dpad tcenter">
			    <a href="' . $CFG->wwwroot . '/mod/coresurvey/reports/pdf.php?start=' . $start . '&end=' . $end . '">
				<img src="' . $CFG->wwwroot . '/mod/coresurvey/images/pdf.png"> Download as PDF
			    </a>
			</div>';
	 }
     }

?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminheader.php'); ?>
            <div id="content">
                <h1>Reports</h1>
		<?php echo $msg; ?>
                <div class="dpad tcenter">
		    <form action="" method="post" id="report_dates">
			<p class="dpad">
			    <label>Start Date: </label><input type="text" name="start" id="start" class="w10" value="<?php echo isset($_POST['start']) ? $_POST['start'] : date("d-m-Y", strtotime("-1 weeks")); ?>"> <label>End Date: </label><input type="text" id="end" name="end" class="w10" value="<?php echo isset($_POST['end']) ? $_POST['end'] : date('d-m-Y'); ?>"> <button type="submit">go</button>
			</p>
			<p>
			    <button type="button" id="last_year">
				last year
			    </button>
			    <button type="button" id="last_month">
				last month
			    </button>
			</p>

		    </form>
		</div>
		<?php echo $pdf; ?>
		<div class="dpad">
		    <?php echo $report; ?>
		</div>
            </div>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminfooter.php'); ?>