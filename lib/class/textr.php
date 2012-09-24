<?php

class textr {

    private $data = array();

    /**
     * construct
     */

    public function __construct() {
	// load the current text fields

	$db_data = coresurvey_get_textr();

	if (! is_null($db_data)) {
	    $this->data = $this->deCompress($db_data->data);
	}
    } // end __construct

    /**
     * updates the textr data
     */

    public function update($data) {
	$obj = new stdClass();

	$obj->id = 1;
	$obj->data = $this->compress($data);

	return coresurvey_update_textr($obj);
    } // end update

    /**
     * Compresses data for storage
     */

    function compress($str) {
	if (empty($str)) {
	    return $str;
	} else {
	    return base64_encode(gzcompress(serialize($str)));
	}
    } // end function

    /**
     * Decompresses data from storage
     */

    function deCompress($str) {
	if (empty($str)) {
	    return $str;
	} else {
	    return unserialize(gzuncompress(base64_decode($str)));
	}
    }

    /**
     * gets the raw data
     */

    public function get_raw_data($n) {
	return isset($this->data[$n]) ? $this->data[$n] : '';
    } // end get-raw_data

    /**
     * returns populated data
     */

    public function get_data($n = null, $number = null, $date = null) {
	if (is_null($n) OR ! isset($this->data[$n])) {
	    return '';
	}

	global $USER;

	$txt = $this->data[$n];

	// do the text replacement
	// name
	$txt = str_replace('!##name##!', $USER->firstname . ' ' . $USER->lastname, $txt);

	// number
	if (! is_null($number)) {
	    $txt = str_replace('!##number##!', $number, $txt);
	}

	// date
	if (! is_null($date)) {
	    $txt = str_replace('!##date##!', date("H:i" , strtotime($date)) . ' on ' . date("jS M Y" , strtotime($date)), $txt);
	}

	return $txt;
    } // end get_data

} // end class

?>
