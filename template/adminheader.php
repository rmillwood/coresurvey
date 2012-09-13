<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
 "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <title><?php echo $core_page->displayTitle(); ?></title>
    <link rel="stylesheet" href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/lib/css/YUI.css" type="text/css">
    <!-- load in the core css and main admin stylesheet -->
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/lib/css/core.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/lib/css/admin.css">
    <!-- extra Head files -->
    <?php
        echo $core_page->displayHead();
    ?>
</head>
<body>
    <div id="errormask"></div>
<div id="doc2" class="yui-t7">
    <div id="hd" role="banner">
        <a href="http://learnzone.macmillan.org.uk/" title="MacMillan LearnZone">
            <img id="mac_logo" src="<?php echo $CFG->wwwroot; ?>/mod/coresurvey/images/mac_logo.gif"/>
        </a>
        <a href="http://www.core-ed.org.uk" title="Core Education"></a>
        Core Survey Tool
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
            <?php
                echo $error->DisplayMessage();
                echo $status->DisplayMessage();
            ?>
