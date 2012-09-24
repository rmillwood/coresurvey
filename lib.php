<?php  // $Id: lib.php,v 1.7.2.5 2009/04/22 21:30:57 skodak Exp $

/**
 * Library of functions and constants for module coresurvey
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the coresurvey specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

/**
 * defines a constant that we will use to check whether access is allowed to
 * access any of the script pages.
 * Scripts will check using:
 * defined('CORESURVEY_INCLUDE_TEST') OR die('not allowed');
 */

define('CORESURVEY_INCLUDE_TEST', 1);

$coresurvey_EXAMPLE_CONSTANT = 42;     /// for example

/**
 * Saves a new instance of the coresurvey into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $coresurvey An object from the form in mod_form.php
 * @param mod_coresurvey_mod_form $mform
 * @return int The id of the newly inserted coresurvey record
 */
function coresurvey_add_instance(stdClass $coresurvey, mod_coresurvey_mod_form $mform = null) {
    global $DB;

    $coresurvey->timecreated = time();

    # You may have to add extra stuff in here #

    return $DB->insert_record('coresurvey', $coresurvey);
}

/**
 * Updates an instance of the coresurvey in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $coresurvey An object from the form in mod_form.php
 * @param mod_coresurvey_mod_form $mform
 * @return boolean Success/Fail
 */
function coresurvey_update_instance(stdClass $coresurvey, mod_coresurvey_mod_form $mform = null) {
    global $DB;

    $coresurvey->timemodified = time();
    $coresurvey->id = $coresurvey->instance;

    # You may have to add extra stuff in here #

    return $DB->update_record('coresurvey', $coresurvey);
}

/**
 * Removes an instance of the coresurvey from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function coresurvey_delete_instance($id) {
    global $DB;

    if (! $coresurvey = $DB->get_record('coresurvey', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #

    $DB->delete_records('coresurvey', array('id' => $coresurvey->id));

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function coresurvey_user_outline($course, $user, $mod, $coresurvey) {
    $summary = new stdClass();
    $summary->time = 0;
    $summary->info = 'analysed roles and/or assessed comptencies';
    return $summary;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function coresurvey_user_complete($course, $user, $mod, $coresurvey) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in coresurvey activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function coresurvey_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function coresurvey_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of coresurvey. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $coresurveyid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function coresurvey_get_participants($coresurveyid) {
    return false;
}


/**
 * This function returns if a scale is being used by one coresurvey
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $coresurveyid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function coresurvey_scale_used($coresurveyid, $scaleid) {
    $return = false;

    //$rec = $DB->get_record("coresurvey","id","$coresurveyid","scale","-$scaleid");
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of coresurvey.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any coresurvey
 */
function coresurvey_scale_used_anywhere($scaleid) {
    global $DB;
    if ($scaleid and $DB->record_exists('coresurvey', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function coresurvey_install() {
    return true;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function coresurvey_uninstall() {
    return true;
}


//////////////////////////////////////////////////////////////////////////////////////
/// Any other coresurvey functions go here.  Each of them must have a name that
/// starts with coresurvey_
/// Remember (see note in first lines) that, if this section grows, it's HIGHLY
/// recommended to move all funcions below to a new "localib.php" file.
require_once("$CFG->dirroot/mod/coresurvey/locallib.php");


?>
