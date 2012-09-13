<?php
    /**
     * CORE Survey Tool for MacMillan Cancer Support
     * CORE Education
     * http://www.core-ed.net
     * Author: Nigel Hulls
     * E-mail: nigel.hulls@core-ed.net
     */

     // first of all Bootstrap the page using the Moodle config file, this gives
     // us access to the Moodle db functions, and also some other libraries
     // that we may want to pull in :-(

     require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

     // Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/admin.php');


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
 "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Core Survey</title>
    <link rel="stylesheet" href="http://yui.yahooapis.com/2.7.0/build/reset-fonts-grids/reset-fonts-grids.css" type="text/css">
    <!-- load in the core css and main admin stylesheet -->
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/lib/css/core.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/lib/css/admin.css">
    <!-- extra Head files -->
    <?php
        echo $core_page->displayHead();
    ?>
</head>
<body>
<div id="doc2" class="yui-t7">
    <div id="hd" role="banner">
        <a href="http://learnzone.macmillan.org.uk/" title="MacMillan LearnZone"><img id="mac_logo" src="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/images/mac_logo.gif" /></a>
        <a href="http://www.core-ed.org.uk" title="Core Education"></a>
        Core Survey
    </div>
    <div id="breadcrumbcontainer">
        <?php
            // display breadcrumb
            echo $core_page->displayBreadcrumb();
        ?>
        <div class="fclear"></div>
    </div>
    <div id="bd" role="main">
        <div class="yui-g">
            <!-- YOUR DATA GOES HERE -->
            <div id="content">
                <h1>Page Title</h1>
            </div>
        </div>
    </div>
    <div id="ft" role="contentinfo">
        <p class="tright pbad">
            &copy; Core Education and MacMillan Cancer Support <?php echo date('Y'); ?>
        </p>
        <dl class="dpad">
            <dt class="w50 fleft">
                    <a href="http://www.core-ed.org.uk">CORE Education UK Ltd</a>
            </dt>
            <dd class="w50 fleft">
                    <a href="http://http://www.macmillan.org.uk/">Macmillan Cancer Support</a>
            </dd>
        </dl>
    </div>
</div>
<?php
    /**
     * Displays any Body files required. All Javascript should be run here
     */
     echo $core_page->displayBody();
?>
</body>
</html>
