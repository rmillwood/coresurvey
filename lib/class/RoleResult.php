<?php
/*
 * Role Result class, stores and manipilates a members results
 */

class RoleResult extends Result {
    public $type = "Role";

    /**
     * saves the survey in the db
     */

    public function saveResults() {
        $this->start_date = time();
        $this->end_date = time();
        $o = $this->dbPrep();

        $this->id = coresurvey_save_role_survey_member($o);
    } // end function

    /**
     * Loads a specific survey
     */

    public function load($id) {
        $res = coresurvey_fetch_role_survey_member($id);
        $this->inject($res);
    } // end function

    /**
     * Saves a specific survey result
     */

    public function save() {
        $o = $this->dbPrep();
        $o->id = $this->id;

        return coresurvey_update_role_survey_member($o);

    } // end function
} // end class

?>
