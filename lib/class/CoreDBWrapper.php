<?php
/*
 * This is a db wrapper, all coresurvey admin and public functions will interface
 * through this file. This will help us upgrade to Moodle 2.0
 */

class CoreDBWrapper {

    /**
     * function for fetching a list of records
     */

    static function fetch_multiple($sql) {
        global $DB;
        return $DB->get_records_sql($sql);

    } // end function

    /**
     * fetches a single
     */

    static function fetch_single($sql) {
        global $DB;
        return $DB->get_record_sql($sql);
    }

    // executes some sql, nasty little hack to bypass moodle db methods
    static function run_sql($sql) {
        global $DB;

        return $DB->Execute($sql);

    } // end function

} // end class

?>
