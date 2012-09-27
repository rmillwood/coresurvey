<?php
/*
 * Skill Survey class
 * All db operations are abstracted into Moodle lib.php format for compliance with
 * Moodle standards
 */

class SkillSurvey extends Survey {
    public $id; // id in database
    public $createdate; // unix time for the modified date
    public $skilldata = array(); // the actual data string
    public $versionnumber; // the version of the survey, is incremented
    public $taken; // how many times the survey has been taken.

    // the alignments
    private $alignment = array(
                                0 => 'Advanced',
                                1 => 'Competent',
                                2 => 'Still learning',
                                3 => 'Beginner',
                                4 => 'Not yet begun'
                              );

    public $surveys = array();

    /**
     * constructor
     *  Use $fetch to get the last survey by version number
     */

    function SkillSurvey($fetch = true) {
        global $core_page;

        if ($fetch) {
            $model = coresurvey_get_last_skill();
            //coresurvey_debug($model);

            $this->id = $model->id;
            $this->createdate = $model->createdate;
            $this->skilldata = $this->deCompress($model->skilldata);
	    $this->matrix = $this->deCompress($model->matrix);
            $this->versionnumber = $model->versionnumber;
            $this->taken = intval($model->taken);

         } // end if we need to fetch a survey

         // are there any operations being performed on the survey?
         if (isset($_POST['addrole']) && $_POST['addrole'] == 1) {
             if ($this->addRole()) {

                 // save the new survey
                 if ($this->saveSurvey()) {
                    // redirect the page
                    coresurvey_page_redirect($core_page->base_url . '/mod/coresurvey/skills_admin/skills.php');
                 } else {
                     // there was a problem
                     global $error;
                     $error->AddMessage('Your Survey could not be saved due to a glitch in the system');
                 }
             } // end if successfull
         } // end add new skill

         // is a skill being deleted????
         if (isset($_POST['deleterole']) && isset($this->skilldata[$_POST['deleterole']])) {
             // ok we have a legitimate skill deletion
             unset($this->skilldata[intval($_POST['deleterole'])]);
             // remove the skill from the weightings
             $this->removeRolefromWeightings(intval($_POST['deleterole']));

             if ($this->saveSurvey()) {
                 global $status, $core_page;
                 $status->changeTitle('Success');
                 $status->addMessage('Role was successfully removed from the Survey');
                 $core_page->addJavascript('parent.$.fn.colorbox.close();');
                 $core_page->addJavascript('parent.LoadSkills();');
             } else {
                 global $error;
                 $error->changeTitle('Oops, something went wrong :-(');
                 $error->addMessage('Your Skill could not be removed due to a glitch in the system!');
             }
         }

         // is a skill being edited??
         if (isset($_POST['editrole']) && isset($this->skilldata[intval($_POST['editrole'])])) {
             $key = intval($_POST['editrole']);
             // ok we have a legitimate skill edit
             if ($this->EditRole($key)) {
                // ok the edit has validated, lets save it
                 if ($this->saveSurvey()) {
                    // redirect the page
                    coresurvey_page_redirect($core_page->base_url . '/mod/coresurvey/skills_admin/skills.php');
                 } else {
                     // there was a problem
                     global $error;
                     $error->AddMessage('Your Survey could not be saved due to a glitch in the system');
                 }
             } else {
                 global $error;
                 $error->changeTitle('Oops, it appears that there was a problem... :-(');
                 // also show the edit form
                 $core_page->addJavascript('ShowEdits("editrole_' . $key . '");');
             }
         } //

         // is an aspect being added???
         if (isset($_POST['addaspect']) && isset($this->skilldata[intval($_POST['addaspect'])])) {
              $key = intval($_POST['addaspect']);
              // ok we have a legitimate add aspect
              if ($this->addAspect($key)) {
                  // it valiadated and was added to the skilldata variable
                  if ($this->saveSurvey()) {
                      coresurvey_page_redirect($core_page->base_url . '/mod/coresurvey/skills_admin/skills.php#' . $this->clean($this->skilldata[$key]['name']));
                  } else {
                      global $error;
                     $error->AddMessage('Your Survey could not be saved due to a glitch in the system');
                  }
              } else {
                  global $error;
                  $error->changeTitle('Oops, it appears that there was a problem.');
                  $core_page->addJavascript('ShowEdits("addaspect_' . $key . '");');
              }
         } // end add aspect

         // delete an aspect
         if (isset($_POST['deleteaspect']) && isset($_POST['role']) && isset($_POST['aspect'])) {
            // ok we have a legitimate kill aspect
            if ($this->killAspect(intval($_POST['role']), intval($_POST['aspect']))) {
                if ($this->saveSurvey()) {
                    $core_page->addHead('<script type="text/javascript">
                                            parent.$.fn.colorbox.close();
                                            parent.LoadSkills();
                                         </script>');
                } else {
                    global $error;
                     $error->AddMessage('Your Survey could not be saved due to a glitch in the system');
                }
            } else {
                global $error;
                $error->addMessage('Hmm, that aspect doesn\'t seem to exist');
            }
         } // end delete aspect

         // edit an aspect
         if (isset($_POST['editaspect']) && isset($_POST['role'])) {
             // ok we have an aspect that has been edited
             $skill = intval($_POST['role']);
             $aspect = intval($_POST['editaspect']);
             if ($this->editAspect($skill, $aspect)) {
                 if ($this->saveSurvey()) {
                    $core_page->addHead('<script type="text/javascript">
                                            parent.$.fn.colorbox.close();
                                            parent.LoadSkills();
                                         </script>');
                } else {
                    global $error;
                     $error->AddMessage('Your Survey could not be saved due to a glitch in the system');
                }
             } else {
                 global $error;
                 $error->changeTitle('Oops, houston we have a problem.');
             }
         } // end edit aspect

	 /**
	  * If instructions are edited
	  */

	 if (isset($_POST['edit_instructions'])) {
	     // populate
	     $this->matrix['instructions']['instructions'] = $_POST['instructions'];
	     $this->matrix['instructions']['learning_opportunities'] = $_POST['learning_opportunities'];

	     if ($this->saveSurvey()) {
		 // success
	     } else {
		 global $error;
		 $error->AddMessage('Your Instructions could not be saved :-(');
	     }
	 }

    } // end constructor

    /**
     * kills the aspect
     */

    protected function killAspect($skill, $aspect) {
        // only do if it exists
        if (isset($this->skilldata[$skill]['aspects'][$aspect])) {
            unset($this->skilldata[$skill]['aspects'][$aspect]);
            return true;
        } else {
            return false;
        }
    } // end function


    /**
     * validates and adds an aspect
     */

    protected function addAspect($key) {
        global $error;

        $proceed = true;
        $tmp = array();

        // validate title
        if (isset($_POST['aspect_title_' . $key]) && ! empty($_POST['aspect_title_' . $key])) {
            $tmp['title'] = coresurvey_unsan($_POST['aspect_title_' . $key]);
        } else {
            $proceed = false;
            $error->addMessage('You must enter a title for this Aspect');
        }

        // comment
        $tmp['comment'] = isset($_POST['aspect_comments_' . $key]) ? coresurvey_unsan($_POST['aspect_comments_' . $key]) : '';

        // do the alignments statements
        foreach($this->alignment AS $idx => $val) {
            if (isset($_POST['aspect_alignment_' . $key . '_' . $idx]) && ! empty($_POST['aspect_alignment_' . $key . '_' . $idx])) {
                $tmp['alignment'][$idx] = coresurvey_unsan($_POST['aspect_alignment_' . $key . '_' . $idx]);
            } else {
                // nope, doesn't match
                $error->addMessage('You missed the "' . $val . '" Alignment Statement');
                $proceed = false;
            }
        } // end alignments

        // finally do the weightings
        // we are going to use the skills for this
        foreach ($this->skilldata AS $idx => $skill) {
            $tmp['weightings'][$idx] = isset($_POST['aspect_weighting_' . $key . '_' . $idx]) ? intval($_POST['aspect_weighting_' . $key . '_' . $idx]) : 0;
        } // end weightings

        // finally if it works, add it
        if ($proceed) {
            $this->skilldata[$key]['aspects'][] = $tmp;
        }


        return $proceed;
    } // end function

    /**
     * edits an aspect
     */

    protected function editAspect($skill, $aspect) {
        global $error;
        $proceed = true;
        $tmp = array();

        // validate title
        if (isset($_POST['aspect_title']) && ! empty($_POST['aspect_title'])) {
            $tmp['title'] = coresurvey_unsan($_POST['aspect_title']);
        } else {
            $proceed = false;
            $error->addMessage('You must enter a title for this Skill / Competency');
        }

        // comment
        $tmp['comment'] = isset($_POST['aspect_comments']) ? coresurvey_unsan($_POST['aspect_comments']) : '';

         // do the alignments statements
        foreach($this->alignment AS $idx => $val) {
            if (isset($_POST['aspect_alignment_' . $idx]) && ! empty($_POST['aspect_alignment_' . $idx])) {
                $tmp['alignment'][$idx] = coresurvey_unsan($_POST['aspect_alignment_' . $idx]);
            } else {
                // nope, doesn't match
                $error->addMessage('You missed the "' . $val . '" Alignment Statement');
                $proceed = false;
            }
        } // end alignments

        // finally do the weightings
        // we are going to use the skills for this

        foreach ($this->skilldata AS $idx => $val) {
            $tmp['weightings'][$idx] = isset($_POST['aspect_weighting_' . $idx]) ? intval($_POST['aspect_weighting_' . $idx]) : 0;
        } // end weightings


	// now do the resources
	$tmp['resources']['reading']	    = isset($_POST['reading']) ? $_POST['reading'] : '';
	$tmp['resources']['elearning']	    = isset($_POST['elearning']) ? $_POST['elearning'] : '';
	$tmp['resources']['face_to_face']   = isset($_POST['face_to_face']) ? $_POST['face_to_face'] : '';
	$tmp['resources']['other_sources']  = isset($_POST['other_sources']) ? $_POST['other_sources'] : '';
	$tmp['resources']['websites']	    = isset($_POST['websites']) ? $_POST['websites'] : '';

        // finally if it works, add it
        if ($proceed) {
            $this->skilldata[$skill]['aspects'][$aspect] = $tmp;
        }
        //coresurvey_debug($_POST);
        //coresurvey_debug($tmp);
        //exit;

        return $proceed;

    } // end function

    /**
     * Edit skills, uses $key to identify the skill...
     */

    protected function EditRole($key) {
        global $error;

        $proceed = true;

        // populate from the posted data
        if (isset($_POST['name_' . $key]) && ! empty($_POST['name_' . $key])) {
            $this->skilldata[$key]['name'] = coresurvey_unsan($_POST['name_' . $key]);
        } else {
            $proceed = false;
            $error->addMessage('You must enter a name for this Role');
        }

        if (isset($_POST['description_' . $key]) && ! empty($_POST['description_' . $key])) {
            $this->skilldata[$key]['description'] = coresurvey_unsan($_POST['description_' . $key]);
        } else {
            $proceed = false;
            $error->addMessage('You must enter a description for this Role');
        }

		if (isset($_POST['color_' . $key]) && ! empty($_POST['color_' . $key])) {
			$this->skilldata[$key]['color'] = coresurvey_unsan($_POST['color_' . $key]);
		} else {
			$proceed = false;
			$error->AddMessage('You must enter a color for this Role');
		}

        $this->skilldata[$key]['comment'] = isset($_POST['comment_' . $key]) ? coresurvey_unsan($_POST['comment_' . $key]) : '';

        return $proceed;
    } // end function

    /**
     * Saves a survey, by incrementing version number, changing date to today
     * and compressing data
     */

    protected function saveSurvey() {
        $this->versionnumber = $this->versionnumber + 1;
        $this->createdate = time();
        $this->skilldata = $this->compress($this->skilldata);
	$this->matrix = $this->compress($this->matrix);

        $success = coresurvey_save_skill_survey($this);

        // ok final step is to decompress the skilldata, just in case any other
        // functions are using the data

        $this->skilldata = $this->deCompress($this->skilldata);
	$this->matrix = $this->deCompress($this->matrix);
        return $success;
    } // end function

    /**
     * Adds a new Skill
     */

    protected function addRole() {
        global $error, $status, $core_page;

        $tmp = array();

        $proceed = true;

        if (isset($_POST['name']) && ! empty($_POST['name'])) {
            $tmp['name'] = coresurvey_unsan($_POST['name']);
        } else {
            $error->AddMessage('You must provide a name for this Role');
            $proceed = false;
        }

        if (isset($_POST['description']) && ! empty($_POST['description'])) {
            $tmp['description'] = coresurvey_unsan($_POST['description']);
        } else {
            $error->AddMessage('You must provide a Description for this Role');
            $proceed = false;
        }

		if (isset($_POST['color']) && ! empty($_POST['color'])) {
			$tmp['color'] = coresurvey_unsan($_POST['color']);
		} else {
			$error->AddMessage("You must choose a color for this Role");
			$proceed = false;
		}
        $tmp['comment'] = isset($_POST['comment']) ? coresurvey_unsan($_POST['comment']) : '';

        $tmp['aspects'] = array();

        if ($proceed) {
            // add to the array
            $this->skilldata[] = $tmp;
            // now we need to add this skill to the weightings for all aspects.
            $this->addSkilltoWeightings($tmp['name']);


        } else {
            $error->ChangeTitle('Oops, you missed something');
            $core_page->addJavascript('ShowEdits("addrole");');
        }

        return $proceed;
    } // end function

    /**
     * Loops through the entire dataset and adds the new skill to each
     * aspects weightings
     */

    protected function addSkilltoWeightings($name) {
        $idx = -1;
        // first of all find the index of the new skill
        foreach ($this->skilldata AS $key => $val) {
            if ($val['name'] == $name) {
                $idx = $key;
            }
        } // end loop

        // check for error, this should not error out, but this checks just in case!
        if ($idx == -1) return;

        // we now have an index, loop though all of the skills, and aspects
        foreach($this->skilldata AS $key => $skill) {
            // loop though all of the aspects
            foreach ($skill['aspects'] AS $akey => $aspect) {
                // add the new weighting
                $this->skilldata[$key]['aspects'][$akey]['weightings'][$idx] = 0;
            } // end aspect loop
        } // end skill loop
    } // end function

    /**
     * Loops through the entire dataset and removes the skill from the weightings
     */

    protected function removeRolefromWeightings($idx) {
        // we now have an index, loop though all of the skills, and aspects
        foreach($this->skilldata AS $key => $skill) {
            // loop though all of the aspects
            foreach ($skill['aspects'] AS $akey => $aspect) {
                // add the new weighting
                unset($this->skilldata[$key]['aspects'][$akey]['weightings'][$idx]);
            } // end aspect loop
        } // end skill loop
    } // end function

    /**
     * Static function that does html needed for a form
	*	has the page passed in
     */

    function addForm($page) {
        $s = '';

        $s .=   '<form action="" method="POST">
                    <fieldset class="adminform">
                        <legend>Add a New Role</legend>
                        <p>Complete the Form to create a New Role.</p>
                        <label for="name">Role Name <span class="required">*</span></label>
                        <input type="text" class="w95" name="name" value="' . (isset($_POST['name']) ? coresurvey_unsan($_POST['name']) : '') . '"/>
						<label for="color">Role Color (used to differentiate Role in Polar map) <span class="required">*</span></label>
						<dl>
							<dt class="w30 fleft tright">
								<input type="text" class="w20" name="color" id="color" value="' . (isset($_POST['color']) ? coresurvey_unsan($_POST['color']) : '#ffffff') . '"/>
							</dt>
							<dd class="w50 fleft">
								<div id="picker"></div>
							</dd>
						</dl>
						<div class="fclear"></div>
                        <label for="description">Role Description <span class="required">*</span></label>
                        <textarea name="description" class="w95">' . (isset($_POST['description']) ? coresurvey_unsan($_POST['description']) : '') . '</textarea>
                        <label for="comment">Role Comments</label>
                        <textarea name="comment" class="w95">' . (isset($_POST['comment']) ? coresurvey_unsan($_POST['comment']) : '') . '</textarea>
                        <div class="dpad tcenter">
                            <button type="submit">Add Role</button>
                            <input type="hidden" name="addrole" value="1"/>
                        </div>' . "\n";
        $s .=   '   </fieldset>
                </form>' . "\n";

		// ok, now add in the colorpicker
		$page->addHead('<link rel="stylesheet" type="text/css" href="' . $page->base_url . '/mod/coresurvey/lib/farbtastic/farbtastic.css"/>');
		$page->addBody('<script type="text/javascript" src="' . $page->base_url . '/mod/coresurvey/lib/farbtastic/farbtastic.js"></script>');
		$page->addJavascript('$("#picker").farbtastic("#color");');

        return $s;
    } // end function

    /**
     * creates html needed to display the skills, gets the page object injected
     */

    function displayAdmin($page) {
        // if there are no skills then don't display them!!

        if (empty($this->skilldata)) {
            global $error;
            $error->AddMessage('There are no Roles defined! Your survey can not be taken!');
            return '<p class="dpad">There are no Roles defined for this survey, you should correct this immediately!</p>';
        }

        // ok it appears that there are some skills....
    } // end function

    /**
     * Displays the Skills in Tabbed fashion
     */

    function displaySkillsAdmin() {
        global $core_page;
        $s = $j = '';

        // if it's empty then return
        if (empty($this->skilldata)) return;

        //coresurvey_debug($this->skilldata);


        // first of all do the tabs
        $s = '<ul class="regtabs">' . "\n";

        // Instructions tab
        $s .=	'<li><a href="#instructions">Instructions & generic learning opportunities</a></li>';

        // Role tabs
        foreach($this->skilldata AS $key => $info) {
            $s .=   '<li><a href="#' . $this->clean($info['name']) . '">' . $info['name'] . '</a></li>';
        }

        $s .= '</ul>' . "\n";

        // now create the panes
        $s .=   '<div class="regpanes">' . "\n";

        // instuctions pane
        $s .= '<div class="tabbkgd">
		       <form action="" method="POST">
			   <fieldset class="adminform">
			   <legend>Edit instructions & generic learning opportunities</legend>
			   <p>Competency assessment instructions</p>
			   <textarea name="instructions" class="w95">' . (isset($this->matrix['instructions']['instructions']) ? $this->matrix['instructions']['instructions'] : '') . '</textarea>
			   <p>Learning Opportunities</p>
			   <textarea name="Report generic learning_opportunities" class="w95">' . (isset($this->matrix['instructions']['learning_opportunities']) ? $this->matrix['instructions']['learning_opportunities'] : '') . '</textarea>
			   <div class="dpad tcenter">
			   <button type="submit">Submit</button>
			   <input type="hidden" name="edit_instructions" value="0">
			   </div>
			   </fieldset>
		       </form>
		       </div>';

        foreach($this->skilldata AS $key => $info)  {
            $s .=   '<div class="tabbkgd">' . "\n";

            // add in the edit....
            $tmp = $this->DisplayRoleEdit($key);
            $s .= $tmp['html'];
            $j .= $tmp['java'];

            // do the description of the Skill
            $s .=   '<p class="dpad"><span class="colorblock" style="background: ' . $this->skilldata[$key]['color'] . '"><b>Color: ' . $this->skilldata[$key]['color'] . '</b></span><br/><b>Description: </b>' . $info['description'] . '<br/>
                    <b>Comment:</b> ' . $info['comment'] . '</p>';

            // add the aspect
            $tmp = $this->addAspectForm($key);
            $s .=   $tmp['html'];
            $j .=   $tmp['java'];

            // display the aspects for this Skill
            if (! empty($info['aspects'])) {
                $tmp =   $this->displayAspects($key);
                $s .=   $tmp['html'];
                $j .=   $tmp['java'];
            } else {
                $s .=   '<p class="dpad">There are no Skills / Competency in this Role!</p>';
            }

            // add the delete button
            $s .=   '<div class="dpad w20 fright core_error_box">
                        <form action="" method="POST">
                            <button type="button" class="core_button button_delete" id="deleterolebutton_' . $key . '">Delete this Role?</button>
                            <input type="hidden" name="deleterole" value="' . $key . '"/>
                        </form>
                    </div>
                    <div class="fclear"></div>';
            // add delete javascript
            $j .= '$("#deleterolebutton_' . $key . '").colorbox({width: "80%", height: "80%", iframe: true, Overlayclose: false, href: "' . $core_page->base_url . '/mod/coresurvey/skills_admin/delete-role.php?r=' . $key . '"});';

            $s .= '</div>' . "\n";
        } // end loop


	$j .= 'CKEDITOR.replace( "instructions" );' . "\n";
	$j .= 'CKEDITOR.replace( "learning_opportunities" );' . "\n";

        // end the panes
        $s .=   '</div>' . "\n";

        // add the javascript.....
        $j .= '$("ul.regtabs").tabs("div.regpanes > div").history();';

        // factor javascript
        $j =    '<script type="text/javascript">
                    $(document).ready(function() {
                        ' . $j . '
                    });
                </script>' . "\n";

        return $s . $j;
    } // end function

    /**
     * displays the html needed to Kill a survey
     */

    function KillRoleForm($key) {

        // make sure that it exists
        if (! isset($this->skilldata[$key])) {
            return '<p class="dpad">Sorry, this skill does not exist!!</p>';
        }

        $s = '';

        // create the form
        $s .=   '<form action="" method="POST">
                    <fieldset class="adminform">
                        <legend>Remove Role: ' . (isset($this->skilldata[$key]['name']) ? $this->skilldata[$key]['name'] : 'Removed') . '</legend>
                        <div class="dpad tcenter">
                            <button type="submit">Delete Role</button>
                            <input type="hidden" name="deleterole" value="' . $key . '"/>
                        </div>
                    </fieldset>
                </form>';

        return $s;
    } // end function

    /**
     * Does the editing information and display
     */

    protected function DisplayRoleEdit($key) {
        $tmp = array('html' => '', 'java' => '');
        $s = $j = '';

		// do the color, this is a hack as the color field was added at a later stage
		$color = isset($this->skilldata[$key]['color']) ? $this->skilldata[$key]['color'] : "#ffffff";

        // add in the buttons
        $s .=   '<div class="core_edit_buttons tright bpad">
                    <button type="button" class="core_button button_edit fright" id="editrole_' . $key . '_button_add">Edit Role: ' . $this->skilldata[$key]['name'] . '</button>
                    <button type="button" class="core_button core_button_close button_close" id="editrole_' . $key . '_button_close">Close Panel</button>
                    <div class="fclear"></div>
                </div>
                <div class="core_edit_container dpad" id="editrole_' . $key . '_container">
                    <form action="" method="POST">
                    <fieldset class="adminform">
                        <legend>Edit Role: ' . $this->skilldata[$key]['name'] . '</legend>
                        <p>Complete the Form to edit this Role.</p>
                        <label for="name_' . $key . '">Role Name <span class="required">*</span></label>
                        <input type="text" class="w95" name="name_' . $key . '" value="' . (isset($_POST['name_' . $key]) ? coresurvey_unsan($_POST['name_' . $key]) : $this->skilldata[$key]['name']) . '"/>
						<label for="color_' . $key . '">Role Color (used to differentiate Role in Polar map) <span class="required">*</span></label>
						<dl>
							<dt class="w30 fleft tright">
								<input type="text" class="w30" name="color_'. $key .'" id="color_' . $key . '" value="' . (isset($_POST['color_' . $key]) ? coresurvey_unsan($_POST['color_' . $key]) : $color) . '"/>
							</dt>
							<dd class="w50 fleft">
								<div id="picker_' . $key . '"></div>
							</dd>
						</dl>
						<div class="fclear"></div>
                        <label for="description_' . $key . '">Role Description <span class="required">*</span></label>
                        <textarea name="description_'. $key . '" class="w95">' . (isset($_POST['description_' . $key]) ? coresurvey_unsan($_POST['description_' . $key]) : $this->skilldata[$key]['description']) . '</textarea>
                        <label for="comment_' . $key . '">Role Comments</label>
                        <textarea name="comment_' . $key . '" class="w95">' . (isset($_POST['comment_' . $key]) ? coresurvey_unsan($_POST['comment_' . $key]) : $this->skilldata[$key]['comment']) . '</textarea>
                        <div class="dpad tcenter">
                            <button type="submit">Edit role</button>
                            <input type="hidden" name="editrole" value="' . $key .'"/>
                        </div>' . "\n";
        $s .=   '   </fieldset>
                </form>
                </div>' . "\n";

        $j .= 'DoEdits("editrole_' . $key . '");' . "\n";
		$j .=	'$("#picker_' . $key . '").farbtastic("#color_' . $key . '");' . "\n";

        $tmp['html'] = $s;
        $tmp['java'] = $j;

        return $tmp;
    }

    /**
     * Adds the create new aspect form
     */

    protected function addAspectForm($key) {
        $tmp = array('html' => '', 'java' => '');

        $s = $j = '';

        $af = $this->aspectForm($key);

        // add in the edit containers
        $s .=   '<div class="core_edit_buttons tcenter bpad">
                    <button type="button" class="core_button button_add" id="addaspect_' . $key . '_button_add">Add a New Skill / Competency</button>
                    <button type="button" class="core_button core_button_close button_close" id="addaspect_' . $key . '_button_close">Close Panel</button>
                </div>
                <div class="core_edit_container" id="addaspect_' . $key . '_container">
                    ' . $af['html'] . '
                </div>';

        $j .= 'DoEdits("addaspect_' . $key . '");';


        $tmp['html'] = $s;
        $tmp['java'] = $j . "\n" . $af['java'];

        return $tmp;
    } // end function

    /**
     * The aspect form
     */

    protected function aspectForm($key) {
        $tmp = array('html' => '', 'java' => '');
        $s = $j = '';

        $s .=   '<form action="" method="POST">' . "\n";

        // comments and title
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Skill / Competency Title and Comments</legend>
                    <label for="aspect_title_' . $key . '">Title <span class="required">*</span></label>
                    <input type="text" class="w95" name="aspect_title_' . $key . '" value="' . (isset($_POST['aspect_title_' . $key]) ? coresurvey_unsan($_POST['aspect_title_' . $key]) : '') .'"/>
                    <label for="aspect_comments_' . $key . '">Admin Comment</label>
                    <textarea class="w95" name="aspect_comment_' . $key . '"></textarea>
                </fieldset>' . "\n";

        // alignments
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Alignment Statements</legend>' . "\n";

        // loop through the alignments
        foreach ($this->alignment AS $idx =>$val) {
            $s .=   '<label for="aspect_alignment_' . $key . '_' . $idx . '">' . $val . '<span class="required">*</span></label>
                    <textarea name="aspect_alignment_' . $key . '_' . $idx . '" class="w95">' . (isset($_POST['aspect_alignment_' . $key . '_' . $idx]) ? coresurvey_unsan($_POST['aspect_alignment_' . $key . '_' . $idx]) : '') . '</textarea>' . "\n";
        } // end loop

        $s .=   '</fieldset>';

        // weightings
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Skill / Competency Weightings</legend>
                        <dl class="w95 fcenter">' . "\n";

        /*
        // loop through the skills
        foreach($this->skilldata AS $idx => $val) {
            $s .=   '<dt class="w60 fleft">
                        <label for="">' . $val['name'] . ' </label>
                    </dt>
                    <dd class="w40 fright">
                      ' . $this->CreateWeightingSelect('aspect_weighting_' . $key . '_' . $idx) . '
                    </dd>
                    <dt class="fclear"></dt>' . "\n";

        }
         */

        // version 2 with sliders
        foreach($this->skilldata AS $idx => $val) {
            $s .=   '<dt class="w30 fleft">
                        <label for="">' . $val['name'] . '</label>
                        <input type="hidden" name="aspect_weighting_' . $key . '_' . $idx . '" id="aspect_weighting_' . $key . '_' . $idx . '" value="0"/>
                     </dt>
                     <dd class="w10 fleft tcenetr">
                        <div id="aspect_weighting_header_' . $key . '_' . $idx . '">
                            0%
                        </div>
                    </dd>
                    <dd class="w60 fleft tcenter">
                        <div id="aspect_weighting_slider_' . $key . '_' . $idx . '">
                        </div>
                    </dd>
                    <dd class="fclear">
                    </dd>';
            $j .= 'CreateSlider("' . $key . '_' . $idx . '", 0);' . "\n";
        } // end weightings loop


        $s .=   '       </dl>
                        <div class="fclear"></div>
                </fieldset>' . "\n";

        // add the submit
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Add</legend>
                    <div class="dpad tcenter">
                        <button type="submit">Add Skill</button>
                        <input type="hidden" name="addaspect" value="' . $key . '"/>
                    </div>
                </fieldset>';

        // end form
        $s .=   '</form>' . "\n";

        $tmp['html'] = $s;
        $tmp['java'] = $j;

        return $tmp;
    } // end function

    /**
     * Displays the Aspects for one Skill
     */

    function displayAspects($key) {
        global $core_page;

        $tmp = array('html' => '', 'java' => '');
        $s = $j = '';

        // firstly we need to work out how many columns
        // edit + title + skills + delete
        $cols = count($this->skilldata) + 3;

        $s .=   '<table class="w100 admintable">
                    <tr>
                        <th class="w10">Edit</th>
                        <th>Title</th>' . "\n";
        // add the skills
        foreach($this->skilldata AS $idx => $skill) {
            $s .=   '<th class="w5">' . substr($skill['name'], 0, 3) . '..</th>' . "\n";
        }

        $s .=   '       <th class="w10">Delete</th>
                    </tr>' . "\n";

        // ok now do the aspects
        foreach ($this->skilldata[$key]['aspects'] AS $idx => $asp) {
            $s .=   '<tr>
                        <td>
                            <button type="button" class="core_button button_edit" id="editaspect' . $key . '_' . $idx . '">E</button>
                        </td>
                        <td>
                            ' . $asp['title'] . (! empty($asp['comment']) ? '<br/>' . $asp['comment'] : '') . '
                        </td>' . "\n";
            // now do the weightings
            foreach($asp['weightings'] AS $widx => $wval) {
                $s .=   '   <td>
                                ' . $wval . '
                            </td>' . "\n";
            } // end weightings loop


            $s .=       '<td>
                            <button type="button" class="core_button button_delete"id="deleteaspect' . $key . '_' . $idx . '">D</button>
                        </td>
                    </tr>' . "\n";

            // add the edit and delete popups
            $j .=   '$("#editaspect' . $key . '_' . $idx . '").colorbox({width: "80%", height: "95%", iframe: true, overlayClose: false, href: "' . $core_page->base_url . '/mod/coresurvey/skills_admin/edit_aspect.php?r=' . $key . '&i=' . $idx .'"});' . "\n";
            $j .=   '$("#deleteaspect' . $key . '_' . $idx . '").colorbox({width: "50%", height: "40%", iframe: true, overlayClose: false, href: "' . $core_page->base_url . '/mod/coresurvey/skills_admin/delete_aspect.php?r=' . $key . '&i=' . $idx .'"});' . "\n";
        } // end aspects

        // end the table
        $s .=   '</table>' . "\n";

        $tmp['html'] = $s;
        $tmp['java'] = $j;

        return $tmp;
    } // end function

    /**
     * Displays a Kill Aspect form
     * Skill / Index of Aspect
     */

    function killAspectForm($r, $i) {
        $s = '<form action="" method="POST">
                <fieldset class="dpad adminform">
                    <legend>Delete Aspect: ' . (isset($this->skilldata[intval($r)]['aspects'][intval($i)]['title']) ? $this->skilldata[intval($r)]['aspects'][intval($i)]['title'] : 'Deleted') . '</legend>
                    <div class="dpad tcenter">
                        <button type="submit">Delete Aspect</button>
                        <input type="hidden" name="role" value="' . $r . '"/>
                        <input type="hidden" name="aspect" value="' . $i . '"/>
                        <input type="hidden" name="deleteaspect" value="1"/>
                    </div>
                </fieldset>
            </form>';

        return $s;
    } // end function

    /**
     * displays the edit aspect form
     */

    function editAspectForm($skill, $idx) {
        $tmp = array('html' => '', 'java' => '');

        $s = $j = '';

        $s .=   '<form action="" method="POST">' . "\n";

        // comments and title
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Skill / Competency Title and Comments</legend>
                    <label for="aspect_title">Title <span class="required">*</span></label>
                    <input type="text" class="w95" name="aspect_title" value="' . (isset($_POST['aspect_title']) ? coresurvey_unsan($_POST['aspect_title']) : $this->skilldata[$skill]['aspects'][$idx]['title']) .'"/>
                    <label for="aspect_comments">Admin Comment</label>
                    <textarea class="w95" name="aspect_comment"></textarea>
                </fieldset>' . "\n";

        // alignments
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Alignment Statements</legend>' . "\n";

        // loop through the alignments
        foreach ($this->alignment AS $adx =>$val) {
            $s .=   '<label for="aspect_alignment_' . $adx . '">' . $val . '<span class="required">*</span></label>
                    <textarea name="aspect_alignment_' . $adx . '" class="w95">' . (isset($_POST['aspect_alignment_' . $adx]) ? coresurvey_unsan($_POST['aspect_alignment_' . $adx]) : $this->skilldata[$skill]['aspects'][$idx]['alignment'][$adx]) . '</textarea>' . "\n";
        } // end loop

        $s .=   '</fieldset>';

        // ok add in the resources
	/*echo '<pre>';
	print_r($this->skilldata);
	exit;
	 * */


	$s .=	'<fieldset class="dpad adminform">
		    <legend>Edit Resources</legend>
		    <p>
			Reading
		    </p>
		    <textarea name="reading" id="reading" class="w95">' . (isset($this->skilldata[$skill]['aspects'][$idx]['resources']['reading']) ? $this->skilldata[$skill]['aspects'][$idx]['resources']['reading'] : '') . '</textarea>
		    <p>
			Macmillan eLearning
		    </p>
		    <textarea name="elearning" id="elearning" class="w95">' . (isset($this->skilldata[$skill]['aspects'][$idx]['resources']['elearning']) ? $this->skilldata[$skill]['aspects'][$idx]['resources']['elearning'] : '') . '</textarea>
		    <p>
			Macmillan face-to-face
		    </p>
		    <textarea name="face_to_face" id="face_to_face" class="w95">' . (isset($this->skilldata[$skill]['aspects'][$idx]['resources']['face_to_face']) ? $this->skilldata[$skill]['aspects'][$idx]['resources']['face_to_face'] : '') . '</textarea>
		    <p>
			Other sources
		    </p>
		    <textarea name="other_sources" id="other_sources" class="w95">' . (isset($this->skilldata[$skill]['aspects'][$idx]['resources']['other_sources']) ? $this->skilldata[$skill]['aspects'][$idx]['resources']['other_sources'] : '') . '</textarea>
		    <p>
			Web sites
		    </p>
		    <textarea name="websites" id="websites" class="w95">' . (isset($this->skilldata[$skill]['aspects'][$idx]['resources']['websites']) ? $this->skilldata[$skill]['aspects'][$idx]['resources']['websites'] : '') . '</textarea>
		</fieldset>' . "\n";

	// add ckeditor
	$j .=	'CKEDITOR.replace( "reading" );' . "\n";
	$j .=	'CKEDITOR.replace( "elearning" );' . "\n";
	$j .=	'CKEDITOR.replace( "face_to_face" );' . "\n";
	$j .=	'CKEDITOR.replace( "other_sources" );' . "\n";
	$j .=	'CKEDITOR.replace( "websites" );' . "\n";


	// weightings
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Skill / Competency Weightings</legend>
                        <dl class="w60 fcenter">' . "\n";


        /*
	// loop through the skills
        foreach($this->skilldata AS $rdx => $val) {
            $s .=   '<dt class="w60 fleft">
                        <label for="">' . $val['name'] . ' </label>
                    </dt>
                    <dd class="w40 fright">
                      ' . $this->CreateWeightingSelect('aspect_weighting_' . $rdx, $this->skilldata[$skill]['aspects'][$idx]['weightings'][$rdx]) . '
                    </dd>
                    <dt class="fclear"></dt>' . "\n";

        }
	 * */




        // version 2 with sliders

        foreach($this->skilldata AS $rdx => $val) {
            $s .=   '<dt class="w30 fleft">
                        <label for="">' . $val['name'] . '</label>
                        <input type="hidden" name="aspect_weighting_' . $rdx . '" id="aspect_weighting_' . $skill . '_' . $rdx . '" value="' . $this->skilldata[$skill]['aspects'][$idx]['weightings'][$rdx] . '"/>
                     </dt>
                     <dd class="w10 fleft tcenetr">
                        <div id="aspect_weighting_header_' . $skill . '_' . $rdx . '">
                            ' . $this->skilldata[$skill]['aspects'][$idx]['weightings'][$rdx] . '%
                        </div>
                    </dd>
                    <dd class="w60 fleft tcenter">
                        <div id="aspect_weighting_slider_' . $skill . '_' . $rdx . '">
                        </div>
                    </dd>
                    <dd class="fclear">
                    </dd>';
            $j .= 'CreateSlider("' . $skill . '_' . $rdx . '", ' . $this->skilldata[$skill]['aspects'][$idx]['weightings'][$rdx] . ');' . "\n";
        } // end weightings loop



        $s .=   '       </dl>
                        <div class="fclear"></div>
                </fieldset>' . "\n";


        // add the submit
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Edit Skill / Competency</legend>
                    <div class="dpad tcenter">
                        <button type="submit">Save Changes</button>
                        <input type="hidden" name="editaspect" value="' . $idx . '"/>
                        <input type="hidden" name="role" value="' . $skill . '"/>
                    </div>
                </fieldset>';

        // end form
        $s .=   '</form>' . "\n";

        $tmp['html'] = $s;
        $tmp['java'] = $j;

        return $tmp;
    } // end function

    /**
     * Report function returns number of roles
     */

    function reportNumberofRoles() {
        return count($this->skilldata);
    } // end function

    /**
     * retuens the number of aspects
     */

    function reportNumberofAspects() {
        $c = 0;

        foreach($this->skilldata AS $rkey => $rval) {
            $c = $c + count($rval['aspects']);
        }


        return $c;
    } // end function

    /**
     * loads in the survey by period
     */

    public function fetch_by_period($start, $end) {
	$this->surveys = coresurvey_get_skill_surveys_member_by_date($start, $end);
    }

    /**
     * count number of surveys
     */

    public function num_taken() {
	return count($this->surveys);
    }

    /**
     * surveys paused
     */

    public function get_paused() {
	if (empty($this->surveys)) {
	    return 0;
	}

	$n = 0;

	foreach($this->surveys as $s) {
	    if ($s->completed == 0) {
		$n++;
	    }
	}

	return $n;
    }

    /**
     * surveys paused
     */

    public function get_completed() {
	if (empty($this->surveys)) {
	    return 0;
	}

	$n = 0;

	foreach($this->surveys as $s) {
	    if ($s->completed == 1) {
		$n++;
	    }
	}

	return $n;
    }

    /**
     * get activities
     */

    public function get_activity_proportions() {
	if (empty($this->surveys)) {
	    return array();
	}

	$data = array();

	foreach($this->surveys as $s) {

	    // does the idx exist?
	    $result = $this->deCompress($s->results);

	    foreach($result as $res) {

		if (! isset($data[$res['idx']])) {
		    $data[$res['idx']] = array(
			'role'	    =>  $res['role'],
			'aspect'    => $res['aspect'],
			'idx'	    => $res['idx'],
			'answer'    => $res['answer'],
			'num'	    => 1,
			'type'	    => $s->type
		    );
		} else {
		    // add the answer and the num
		    $data[$res['idx']]['answer'] += $res['answer'];
		    $data[$res['idx']]['num']++;
		}
	    }

	}

	// need to do a complete fudge to sort the multidimensional array
	$tmp = $out = array();
	foreach($data as $key => $val) {
	    $tmp[$key] = $key;
	}

	ksort($tmp);

	foreach($tmp as $key => $val) {
	    $out[$key] = $data[$key];
	}

	return $out;

    } // end function

    /**
     * gets an aspect title based on a role / aspect combo
     */

    public function get_aspect_title($role = null, $aspect = null) {
	if (	is_null($role)
		OR is_null($aspect)
		OR ! isset($this->skilldata[$role]['aspects'][$aspect]))
	{
	    return "Could not find this Aspect!!";
	}

	return $this->skilldata[$role]['name'] . ' - ' . $this->skilldata[$role]['aspects'][$aspect]['title'];
    } // end function

    /**
     * gets the roles summary
     */

    public function get_skills_summary() {
	$data = array();

	foreach($this->skilldata as $key => $val) {
	    $data[$key] = array(
		'name'		=> $val['name'],
		'total'		=> 0,
		'num'		=> 0
	    );
	}

	return $data;
    }

} // end class

?>
