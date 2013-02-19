<?php
/**
 * Page Class
 */

class AdminPage {
    public $base_dir;
    public  $base_url;
    private $title = array();
    private $head_files = array();
    private $body_files = array();
    private $javascript = array();
    private $breadcrumb = array();

    /**
     * Constructor method
     * Loads some of the files into the system
     */

    function AdminPage($CFG = '') {
        // create the root directory, using Moodle CFG, or hack with $_SERVER
        $this->base_dir = $CFG != '' ? $CFG->dirroot : $_SERVER['DOCUMENT_ROOT'];
        $this->base_url = $CFG != '' ? $CFG->wwwroot : 'http://' . $_SERVER['HTTP_HOST'];

        // add the main Moodle settings page breadcrumb
        $this->addBreadcrumb('<a href="' . $this->base_url . '/admin/">Moodle Admin</a>');
        $this->addBreadcrumb('<a href="' . $this->base_url . '/admin/settings.php?section=modsettingcoresurvey">CORE Survey</a>');

        // now add the files
        // add jquery
        $this->addHead('<script type="text/javascript" src="' .$this->base_url . '/mod/coresurvey/lib/js/jquery-1.3.2.min.js"></script>');
        // add jquery tools
        $this->addHead('<script type="text/javascript" src="' . $this->base_url . '/mod/coresurvey/lib/js/jquery.tools.min.js"></script>');
        // add main library
        $this->addBody('<script type="text/javascript" src="' . $this->base_url . '/mod/coresurvey/lib/js/coresurvey.js"></script>');
        // add colorbox
        $this->addHead('<link rel="stylesheet" type="text/css" href="' . $this->base_url . '/mod/coresurvey/lib/css/colorbox.css"/>');
        $this->addBody('<script type="text/javascript" src="' . $this->base_url . '/mod/coresurvey/lib/js/jquery.colorbox-min.js"></script>');

        // add to the Page Title
        $this->addTitle("CORE Survey");
        

    } // end constructor

    /**
     * Adds to the title
     */

    function addTitle($str) {
        if (! empty($str)) {
            $this->title[] = $str;
        }
    } // end function

    /**
     * Adds to the Head files
     */

    function addHead($str) {
        if (! empty($str)) {
            $this->head_files[] = $str;
        }
    } // end function

    /**
     * Adds to the Body Files
     */
    function addBody($str) {
        if (! empty($str)) {
            $this->body_files[] = $str;
        }
    } // end function

    /**
     * Adds to the Javascript
     */

    function addJavascript($str) {
        if (! empty($str)) {
            $this->javascript[] = $str;
        }
    } // end function

    /**
     * Adds to the Breadcrumb
     */

    function addBreadcrumb($str) {
        if (! empty($str))
        {
            $this->breadcrumb[] = $str;
        }
    }

    /**
     * Displays the head files
     */

    function displayHead() {
        if (empty($this->head_files))
        {
            return;
        }

        $s = '';

        foreach($this->head_files as $head => $data)
        {
            $s .= $data . "\n";
        }

        return $s;
    } // end function

    /**
     * Displays the Body Files
     */

    function displayBody() {
        if (empty($this->body_files))
        {
            return;
        }

        $s = '';

        foreach($this->body_files as $body => $data)
        {
            $s .= $data . "\n";
        }

        return $s;
    } // end function

    /**
     * Displays the breadcrumb file
     */

    function displayBreadcrumb() {
        // don't display if it's empty
        if (empty($this->breadcrumb)) {
            return;
        }

        $s = '';

        $s .= '<ul id="corebreadcrumb">' . "\n";

        foreach($this->breadcrumb as $key => $data) {
            $s .= '<li>' . $data . '</li>' . "\n";
        }



        $s .= ' </ul>';

        return $s;
    }  // end function

    /**
     * Displays any javascript
     */

    function displayJavascript() {
        // don't do if it's empty
        if (empty($this->javascript)) {
            return;
        }

        $s = '<script type="text/javascript">
                $(document).ready(function() {' . "\n";

        foreach ($this->javascript as $key => $val) {
            $s .= $val . "\n";
        }

        $s .=   '   });
                </script>' . "\n";

        return $s;
    } // end function

    /**
     * Displays the title
     */

    function displayTitle() {
        if (empty($this->title)) {
            return;
        }

        $s = '';

        foreach($this->title AS $key => $data) {
            $s .= ' > ' . $data;
        } // end loop

        return $s;
    } // end function
} // end class

?>
