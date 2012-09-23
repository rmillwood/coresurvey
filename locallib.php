<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
}
/*
 * coresurvey extra library files
 * Note:: I'm using a database abstraction class to unify all database calls
 * for this module, this will make it easier to upgrade to Moodle 2.0
 */

require_once($CFG->dirroot . '/mod/coresurvey/lib/class/CoreDBWrapper.php');

// make sure that the main library file lib.php has been called as well
// this should never happen, lib.php should always be called
// but just in case


require_once("$CFG->dirroot/mod/coresurvey/lib.php");

/**
 * Debug function for displaying data
 */

function coresurvey_debug($object) {
    echo '<pre class="tleft">';

    print_r($object);

    echo '</pre>';
}

/**
     * Gets the last survey by version number from the db and loads it into
     * the object
     */

    function coresurvey_get_last_role() {
        global $DB;
        if (! $survey = $DB->get_records_sql( 'SELECT * FROM {coresurveyrole} ORDER BY versionnumber DESC LIMIT 1')) { exit; }

/**  REPLACES      if (! $survey = CoreDBWrapper::fetch_multiple(    "SELECT *
*                                            FROM " . "coresurveyrole
*                                            ORDER BY versionnumber DESC
*                                            LIMIT 1")) {
*             exit;
*        }
*/


        // stupid hack as we don't know the id of the record, and this is used as
        // the array key

        foreach ($survey AS $key => $val) {
            return $val;
        }

        return $survey;
    } // end function



    /**
     * Loads a specific role survey
     */

    function coresurvey_get_specific_role($id = 0) {
        global $DB;
        $survey = false;
        if (! $survey = $DB->get_record_sql( 'SELECT * FROM {coresurveyrole} WHERE id = ?', array( intval($id) ))) {  }

/** REPLACES
*        if (! $survey = CoreDBWrapper::fetch_single("SELECT * FROM " . "coresurveyrole
*                                                    WHERE id = " . intval($id))) {
*        }
*/
        return $survey;

    } // end function


    /**
     * Loads a specific skill survey
     */

    function coresurvey_get_specific_skill($id = 0) {
        global $DB;
        $survey = false;
        if (! $survey = $DB->get_record_sql( 'SELECT * FROM {coresurveyskill} WHERE id = ?', array( intval($id) ))) {  }

/** REPLACES
*        if (! $survey = CoreDBWrapper::fetch_single("SELECT * FROM " . "coresurveyskill
*                                                    WHERE id = " . intval($id))) {
*        }
*/
        return $survey;

    } // end function

    /**
     * Gets the last survey by version number from the db and loads it into
     * the object
     */

    function coresurvey_get_last_skill() {
        global $DB;
        if (! $survey = $DB->get_records_sql( 'SELECT * FROM {coresurveyskill} ORDER BY versionnumber DESC LIMIT 1')) { exit; }

/**  REPLACES
*        if (! $survey = CoreDBWrapper::fetch_multiple(    "SELECT *
*                                            FROM " . "coresurveyskill
*                                            ORDER BY versionnumber DESC
*                                            LIMIT 1")) {
*            exit;
*        }
*/
        // stupid hack as we don't know the id of the record, and this is used as
        // the array key

        foreach ($survey AS $key => $val) {
            return $val;
        }

        return $survey;
    } // end function


    /**
     * returns a list of saved survey data by member
     */

    function coresurvey_get_role_surveys_member() {
        global $USER, $DB;
        $survey = $DB->get_records_sql( 'SELECT * FROM {coresurveyrole_member} WHERE m_id = ? ORDER BY start_date ASC ', array($USER->id));



/**  REPLACES
*        $survey = CoreDBWrapper::fetch_multiple("SELECT * FROM " . "coresurveyrole_member
*                                                 WHERE m_id = " . intval($USER->id) . "
*                                                 ORDER BY start_date ASC");
*/
        return $survey;
    } // end function


    /**
     * gets all role surveys for a specific time period
     */

    function coresurvey_get_role_surveys_member_by_date($start, $end) {
	global $USER, $DB;

	$start = date('Y-m-d', strtotime($start)) . ' 00:00:00';
	$end = date('Y-m-d', strtotime($end)) . ' 23:59:59';
	$res = $DB->get_records_sql( 'SELECT * FROM {coresurveyrole_member} WHERE end_date>= ? AND end_date <= ?', array( $start , $end ));

/**  REPLACES
*	$res = CoreDBWrapper::fetch_multiple("SELECT * FROM " . "coresurveyrole_member
*					    WHERE end_date >= '" . $start . "'
*					    AND end_date <= '" . $end . "'");
*/
		return $res;

    } // end function


    /**
     * gets all competency surveys for a specific time period
     */

    function coresurvey_get_skill_surveys_member_by_date($start, $end) {
	global $USER, $DB;

	$start = date('Y-m-d', strtotime($start)) . ' 00:00:00';
	$end = date('Y-m-d', strtotime($end)) . ' 23:59:59';
	$res = $DB->get_records_sql( 'SELECT * FROM {coresurveyskill_member} WHERE end_date>= ? AND end_date <= ?', array( $start , $end ));

/**  REPLACES
*	$res = CoreDBWrapper::fetch_multiple("SELECT * FROM " . "coresurveyskill_member
*					    WHERE end_date >= '" . $start . "'
*					    AND end_date <= '" . $end . "'");
*/
		return $res;

    } // end function


    /**
     * returns a list of saved survey data by member
     */

    function coresurvey_get_skills_surveys_member() {
        global $USER, $DB;
        return $DB->get_records_sql('SELECT * FROM {coresurveyskill_member} WHERE m_id = ? ORDER BY start_date ASC', array(intval($USER->id)));
/** REPLACES
 *       $survey = CoreDBWrapper::fetch_multiple("SELECT * FROM " . "coresurveyskill_member WHERE m_id = " . intval($USER->id) . " ORDER BY start_date ASC");
 *          return $survey;
*/
    } // end function


    /**
     * saves a survey into the db, gets the object passed into it
     */

    function coresurvey_save_role_survey($obj) {
        global $DB;

        $success = $DB->insert_record('coresurveyrole', $obj);

        return $success;

    } // end function


    /**
     * saves a survey into the db, gets the object passed into it
     */

    function coresurvey_save_skill_survey($obj) {
        global $DB;

        $success = $DB->insert_record('coresurveyskill', $obj);

        return $success;

    } // end function

    /**
     * saves a members role survey results
     */

    function coresurvey_save_role_survey_member($obj) {
        global $DB;
        return $DB->insert_record('coresurveyrole_member', $obj);
    }

    /**
     * saves a members role survey results
     */

    function coresurvey_save_skill_survey_member($obj) {
        global $DB;

        return $DB->insert_record('coresurveyskill_member', $obj);
    }

    /**
     * Updates a members role survey results
     */

    function coresurvey_update_role_survey_member($obj) {
        global $DB;
        return $DB->update_record('coresurveyrole_member', $obj);
    }

     /**
     * Updates a members role survey results
     */

    function coresurvey_update_skill_survey_member($obj) {
        global $DB;
        return $DB->update_record('coresurveyskill_member', $obj);
    }

    /**
     * updates an editable text record
     */

    function coresurvey_update_textr($obj) {
        global $DB;
        return $DB->update_record('coresurvey_textr', $obj);
    }

    /**
     * fetches all of the editable records
     */

    function coresurvey_get_textr() {
	global $DB;
	return $DB->get_record_sql('SELECT * FROM {coresurvey_textr} WHERE id = 1');

/** REPLACES
* 	return CoreDBWrapper::fetch_single("SELECT * FROM mdl." . "coresurvey_textr WHERE id = 1");
*/

        } //end function

    /**
     * removes a survey
     */

    function coresurvey_delete_skill_survey_member($id) {
    global $DB;
	return $DB->delete_records('coresurveyskill_member', array($id));
    }


    /**
     * fetches a members role survey result
     */

    function coresurvey_fetch_role_survey_member($id) {
        global $DB;
        return $DB->get_record_sql('SELECT * FROM {coresurveyrole_member} WHERE id = ? ORDER BY end_date DESC', array(intval($id)));

/** REPLACES
*        return CoreDBWrapper::fetch_single("SELECT * FROM " . "coresurveyrole_member WHERE id = " . intval($id) . " ORDER BY end_date DESC");
*/
    } //end function


    /**
     * fetches a members skill survey result
     */

    function coresurvey_fetch_skill_survey_member($id) {
        global $DB;
        return $DB->get_record_sql('SELECT * FROM {coresurveyskill_member} WHERE id = ? ORDER BY end_date DESC', array(intval($id)));

/** REPLACES
*        return CoreDBWrapper::fetch_single("SELECT * FROM " . "coresurveyskill_member WHERE id = " . intval($id) . " ORDER BY end_date DESC");
*/
    } //end function


/**
 * sanitises and recodes input for form and db operations
 */

    function coresurvey_unsan($str) {
         if( phpversion() < '5.2.3' ) {
            $str = htmlentities($str, ENT_QUOTES, 'UTF-8');
        } else {
            $str = htmlentities($str, ENT_QUOTES, 'UTF-8', true);
        }
        return $str;
    } //end function


    /**
     * Updates the taken count for a skill survey
     */

    function coresurvey_update_skill_taken_count($id = 0) {
        global $CFG;

        $sql = sprintf("UPDATE " . "{coresurveyskill} SET taken = taken + 1 WHERE id = %d", intval($id));
        CoreDBWrapper::run_sql($sql);
    } //end function


    /**
     * Updates the taken count for a role survey
     */

    function coresurvey_update_role_taken_count($id = 0) {
        global $CFG;

        $sql = sprintf("UPDATE " . "{coresurveyrole} SET taken = taken + 1 WHERE id = %d", intval($id));
        CoreDBWrapper::run_sql($sql);
    } //end function


    /**
     * preps something for putting in the db, note this is BAD!
     * should be suing db->real_escape_string or similar
     * NOW REMOVED addslashes as per migration from 1.9 to 2.3
     */

    function coresurvey_san($str) {
        return $str;
    } // end function


    /**
     * Redirects a page
     */

    function coresurvey_page_redirect($str) {
        header(sprintf("Location: %s", $str));
        exit;
    } // end function

?>
