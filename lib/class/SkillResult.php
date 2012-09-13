<?php
/*
 * Skill Result class, stores and manipilates a members results
 */

class SkillResult extends Result {
    public $type = "Skill";

    public $selected_type = 0;


    /**
     * Sets the type
     */

    public function setType($type = null) {
	$this->selected_type = $type;
    } // end setType

    /**
     * saves the survey in the db
     */

    public function saveResults() {
        $this->start_date = time();
        $this->end_date = time();
        $o = $this->dbPrep();

	$o->type = $this->selected_type;

        $this->id = coresurvey_save_skill_survey_member($o);
    } // end function

    /**
     * Loads a specific survey
     */

    public function load($id) {
        $res = coresurvey_fetch_skill_survey_member($id);
        $this->inject($res);
    } // end function

    /**
     * Saves a specific survey result
     */

    public function save() {
        $o = $this->dbPrep();
        $o->id = $this->id;

        return coresurvey_update_skill_survey_member($o);

    } // end function

    /**
     * deletes the result from the table
     */

    public function kill() {
	return coresurvey_delete_skill_survey_member($this->id);
    }
} // end class

?>