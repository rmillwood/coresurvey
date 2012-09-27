<?php //Administration of role analysis

     require_once('../../../config.php');

     // Now add in our bootstrap file to load in core classes.
     require_once($CFG->dirroot . '/mod/coresurvey/bootstrap/admin.php');

     // add th the breadcrumb
     $core_page->addBreadcrumb("Roles Survey Administration");

     // include the classes needed
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/DBhandler.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Survey.php');
     require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleSurvey.php');


     // add tabs css
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/css/tabs2.css">');

     $survey = new RoleSurvey(true);

     //coresurvey_debug($survey);

     // add js for the addroles
     $core_page->addJavascript('DoEdits("addrole");');

     $addrole = $survey->addForm($core_page);

     $showroles = $survey->displayAdmin($core_page);

     $data = $survey->displayRolesAdmin();

     /*echo '<pre>';
     print_r($survey->roledata);
     exit;*/

     // add in the files necessary for the sliders..
     // need to load in jquery ui for the slider......
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.core.js"></script>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/js/ui.slider.js"></script>');
     $core_page->addHead('<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/mod/coresurvey/lib/jquery.ui/css/ui-lightness/jquery-ui-1.7.2.custom.css"/>');
     $core_page->addBody('<script type="text/javascript" src="' . $CFG->wwwroot . '/mod/coresurvey/lib/ckeditor/ckeditor.js"></script>');


?>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminheader.php'); ?>
            <div id="content">
                <h1>Role Analysis Settings</h1>
                <div class="core_edit_buttons tcenter bpad">
                    <button type="button" class="core_button button_add" id="addrole_button_add">Add a New Role</button>
                    <button type="button" class="core_button core_button_close button_close" id="addrole_button_close">Close Panel</button>
                </div>
                <div class="core_edit_container" id="addrole_container">
                    <?php echo $addrole; ?>
                </div>
                <?php echo $showroles; ?>
                <div id="rolecontainer">
                    <?php echo $data; ?>
                </div>
            </div>
<?php require_once($CFG->dirroot . '/mod/coresurvey/template/adminfooter.php'); ?>