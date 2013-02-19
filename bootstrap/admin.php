<?php
/* 
 * Loads in the core classes need for the admin pages
 */

// load in the coresurvey module library
require_once($CFG->dirroot . '/mod/coresurvey/lib.php');

// load message classes
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Message.php');
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Error.php');
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Status.php');

$error = new Error;
$status = new Status;

// load AdminPage
require_once($CFG->dirroot . '/mod/coresurvey/lib/class/AdminPage.php');
$core_page = new AdminPage($CFG);

?>
