<?php
/* 
 * Core result class, can me Roles or Skills based depending on the
 * extended class
 *
 */

class Result extends DBhandler {
    public $id; // id in the database
    public $type; // the type of result, role or skill
    public $m_id; // the current logged in members ID
    public $survey_id; // the ID of the survey // version  number
    public $completed; // Boolean, stores whether the survey is finished or just paused
    public $start_date; // UNIX timestamp of when the survey was initially stored // used for pause
    public $end_date; // UNIX timestamp of when the survey was completed
    public $results; // the compressed storage of the results

    /**
     * Getters
     */

    /**
     * Data Setters
     */

    public function setMemberID($id) {
        $this->m_id = intval($id);
    }

    public function setSurveyID($id) {
        $this->survey_id = intval($id);
    }

    public function setCompleted($b = true) {
        $this->completed = $b == true ? true : '';
    }

    public function setResults($results) {
        if (is_array($results)) {
            $this->results = $results;
        } else {
            $this->results = array();
        }
    }

    public function setEndDate() {
        $this->end_date = date("Y-m-d H:i:s");
    }

    /**
     * Injects the data from an object / db row
     */

    public function inject($obj) {
        if (isset($obj->id)) {
            $this->id = $obj->id;
        }
        if (isset($obj->m_id)) {
            $this->m_id = $obj->m_id;
        }
        if (isset($obj->survey_id)) {
            $this->survey_id = $obj->survey_id;
        }
        if (isset($obj->completed)) {
            $this->completed = $obj->completed == 1 ? true : false;
        }
        if (isset($obj->start_date)) {
            $this->start_date = $obj->start_date;
        }
        if (isset($obj->end_date)) {
            $this->end_date = $obj->end_date;
        }
        if (isset($obj->results)) {
            $this->results = $this->deCompress($obj->results);
        }

	if (isset($obj->type)) {
	    $this->selected_type = $obj->type;
	}
    } // end function

    /**
     * preps an object for db storage
     */

    protected function dbPrep() {
        $o = new stdClass();

        $o->m_id = $this->m_id;
        $o->survey_id = $this->survey_id;
        $o->completed = $this->completed == true ? 1 : 0;
        $o->results = $this->compress($this->results);
        $o->start_date = $this->start_date;
        $o->end_date = $this->end_date;

        return $o;
    } // end function
} // end class

?>
