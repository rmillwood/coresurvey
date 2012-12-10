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

     require_once('../../../config.php');

     // Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/admin.php');

     // add th the breadcrumb
     $core_page->addBreadcrumb("Skills Survey Administration");

     // include the classes needed
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillSurvey.php');


     // add tabs css
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/tabs2.css">');

     $survey = new SkillSurvey(true);

     //coresurvey_debug($survey);

     // add js for the addskills
     $core_page->addJavascript('DoEdits("addrole");');

     $addrole = $survey->addForm($core_page);

     $showskills = $survey->displayAdmin($core_page);

     $data = $survey->displaySkillsAdmin();

     // add in the files necessary for the sliders..
     // need to load in jquery ui for the slider......
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.core.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.slider.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/ckeditor/ckeditor.js"></script>');

?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminheader.php'); ?>
            <div id="content">
                <h1>Skills Survey Administration</h1>
                <div class="core_edit_buttons tcenter bpad">
                    <button type="button" class="core_button button_add" id="addrole_button_add">Add a New Role</button>
                    <button type="button" class="core_button core_button_close button_close" id="addrole_button_close">Close Panel</button>
                </div>
                <div class="core_edit_container" id="addrole_container">
                    <?php echo $addrole; ?>
                </div>
                <?php echo $showskills; ?>
                <div id="skillcontainer">
                    <?php echo $data; ?>
                </div>
            </div>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminfooter.php'); ?>