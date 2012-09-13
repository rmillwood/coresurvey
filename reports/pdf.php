<?php
/**
 * Dompdf pdf
 */

     require_once('../../../config.php');

     // Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/admin.php');
     
     // include the classes required
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Reports.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleSurvey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillSurvey.php');
     
     $start	= $_GET['start'];
     $end	= $_GET['end'];
     
     $r = new Reports();
     $html = '<html>
		    <head>
		    </head>
		    <body>
			' . $r->main_report($start, $end) . '
		    </body>
	     <html>';
     
     require_once($CFG->dirroot . '/mod/coresurvey/lib/dom2pdf/dompdf_config.inc.php');
     
     $dompdf = new DOMPDF();
    $dompdf->load_html($html);
    $dompdf->render();
    $dompdf->stream("report.pdf");

?>
