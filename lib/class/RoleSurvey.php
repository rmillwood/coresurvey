<?php
/*
 * Role Survey class
 * All db operations are abstracted into Moodle lib.php format for compliance with
 * Moodle standards
 */

class RoleSurvey extends Survey {
    public $id; // id in database
    public $createdate; // unix time for the modified date
    public $roledata = array(); // the actual data string

    public $versionnumber; // the version of the survey, is incremented
    public $taken; // how many times the survey has been taken.

    // the alignments
    protected $alignment = array(
                                0 => 'strongly aligned',
                                1 => 'aligned',
                                2 => 'neutral',
                                3 => 'misaligned',
                                4 => 'strongly misaligned'
                              );

    public $surveys = array();

    /**
     * constructor
     *  Use $fetch to get the last survey by version number
     */

    function __construct($fetch = true) {
        global $core_page;

        if ($fetch) {
            $model = coresurvey_get_last_role();
            //coresurvey_debug($model);

            $this->id = $model->id;
            $this->createdate = $model->createdate;
            $this->roledata = $this->deCompress($model->roledata);
	    $this->matrix   = $this->deCompress($model->matrix);
            $this->versionnumber = $model->versionnumber;
            $this->taken = intval($model->taken);

         } // end if we need to fetch a survey

         // are there any operations being performed on the survey?
         if (isset($_POST['addrole']) && $_POST['addrole'] == 1) {
             if ($this->addRole()) {
                 // save the new survey
                 if ($this->saveSurvey()) {
                    // redirect the page
                    coresurvey_page_redirect($core_page->base_url . '/mod/coresurvey/roles_admin/roles.php');
                 } else {
                     // there was a problem
                     global $error;
                     $error->AddMessage('Your Survey could not be saved due to a glitch in the system');
                 }
             } // end if successfull
         } // end add new role

         // is a role being deleted????
         if (isset($_POST['deleterole']) && isset($this->roledata[$_POST['deleterole']])) {
             // ok we have a legitimate role deletion
             unset($this->roledata[intval($_POST['deleterole'])]);
             // remove the role from the weightings
             $this->removeRolefromWeightings(intval($_POST['deleterole']));

             if ($this->saveSurvey()) {
                 global $status, $core_page;
                 $status->changeTitle('Success');
                 $status->addMessage('Role was successfully removed from the Survey');
                 $core_page->addJavascript('parent.$.fn.colorbox.close();');
                 $core_page->addJavascript('parent.LoadRoles();');
             } else {
                 global $error;
                 $error->changeTitle('Oops, something went wrong :-(');
                 $error->addMessage('Your Role could not be removed due to a glitch in the system!');
             }
         }

         // is a role being edited??
         if (isset($_POST['editrole']) && isset($this->roledata[intval($_POST['editrole'])])) {
             $key = intval($_POST['editrole']);
             // ok we have a legitimate role edit
             if ($this->EditRole($key)) {
                // ok the edit has validated, lets save it
                 if ($this->saveSurvey()) {
                    // redirect the page
                    coresurvey_page_redirect($core_page->base_url . '/mod/coresurvey/roles_admin/roles.php');
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
         if (isset($_POST['addaspect']) && isset($this->roledata[intval($_POST['addaspect'])])) {
              $key = intval($_POST['addaspect']);
              // ok we have a legitimate add aspect
              if ($this->addAspect($key)) {
                  // it valiadated and was added to the roledata variable
                  if ($this->saveSurvey()) {
                      coresurvey_page_redirect($core_page->base_url . '/mod/coresurvey/roles_admin/roles.php#' . $this->clean($this->roledata[$key]['name']));
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
                                            parent.LoadRoles();
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
             $role = intval($_POST['role']);
             $aspect = intval($_POST['editaspect']);
             if ($this->editAspect($role, $aspect)) {
                 if ($this->saveSurvey()) {
                    $core_page->addHead('<script type="text/javascript">
                                            parent.$.fn.colorbox.close();
                                            parent.LoadRoles();
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

	 // threshold stuff
	 if (isset($_POST['edit_thresh_hold'])) {
	     // are they acceptable vals
	     $this->matrix['match']	    = intval($_POST['match_thresh_hold']);
	     $this->matrix['separate']	    = intval($_POST['separate_thresh_hold']);

	     if ($this->saveSurvey()) {
		 // success
		 // all ok!!
	     } else {
		 // failure
		 global $error;
		 $error->AddMessage('Your Threshhold values could not be saved');
	     }
	 }

	 // matrix
	 if (isset($_POST['edit_matrix'])) {
	     // populate the matrix
	     foreach($_POST as $tag => $value) {
		 $this->matrix['tags'][$tag] = $value;
	     }

	     if ($this->saveSurvey()) {
		 // success
	     } else {
		 // failure
		 global $error;
		 $error->AddMessage("The Matrix could not be saved");
	     }
	 }

	 // instructions
	 if (isset($_POST['edit_instructions'])) {
	     // populate the matrix
	     $this->matrix['instructions']['summary']	    = $_POST['summary'];
	     $this->matrix['instructions']['role_summary']  = $_POST['role_summary'];
	     $this->matrix['instructions']['warning']	    = $_POST['warning'];
	     $this->matrix['instructions']['instructions']  = $_POST['instructions'];

	     if ($this->saveSurvey()) {
		 // succss
	     } else {
		 global $error;
		 $error->AddMessage("Your Instructions could not be saved");
	     }
	 }

    } // end constructor

    /**
     * kills the aspect
     */

    protected function killAspect($role, $aspect) {
        // only do if it exists
        if (isset($this->roledata[$role]['aspects'][$aspect])) {
            unset($this->roledata[$role]['aspects'][$aspect]);
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
        // we are going to use the roles for this
        foreach ($this->roledata AS $idx => $role) {
            $tmp['weightings'][$idx] = isset($_POST['aspect_weighting_' . $key . '_' . $idx]) ? intval($_POST['aspect_weighting_' . $key . '_' . $idx]) : 0;
        } // end weightings

        // finally if it works, add it
        if ($proceed) {
            $this->roledata[$key]['aspects'][] = $tmp;
        }


        return $proceed;
    } // end function

    /**
     * edits an aspect
     */

    protected function editAspect($role, $aspect) {
        global $error;
        $proceed = true;
        $tmp = array();

        // validate title
        if (isset($_POST['aspect_title']) && ! empty($_POST['aspect_title'])) {
            $tmp['title'] = coresurvey_unsan($_POST['aspect_title']);
        } else {
            $proceed = false;
            $error->addMessage('You must enter a title for this Aspect');
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
        // we are going to use the roles for this
        foreach ($this->roledata AS $idx => $val) {
            $tmp['weightings'][$idx] = isset($_POST['aspect_weighting_' . $idx]) ? intval($_POST['aspect_weighting_' . $idx]) : 0;
        } // end weightings

        // finally if it works, add it
        if ($proceed) {
            $this->roledata[$role]['aspects'][$aspect] = $tmp;
        }
        //coresurvey_debug($_POST);
        //coresurvey_debug($tmp);
        //exit;

        return $proceed;

    } // end function

    /**
     * Edit roles, uses $key to identify the role...
     */

    protected function EditRole($key) {
        global $error;

        $proceed = true;

        // populate from the posted data
        if (isset($_POST['name_' . $key]) && ! empty($_POST['name_' . $key])) {
            //$this->roledata[$key]['name'] = coresurvey_unsan($_POST['name_' . $key]);
            $this->roledata[$key]['name'] = $_POST['name_' . $key];
        } else {
            $proceed = false;
            $error->addMessage('You must enter a name for this Role');
        }

        if (isset($_POST['description_' . $key]) && ! empty($_POST['description_' . $key])) {
            //$this->roledata[$key]['description'] = coresurvey_unsan($_POST['description_' . $key]);
            $this->roledata[$key]['description'] = $_POST['description_' . $key];
        } else {
            $proceed = false;
            $error->addMessage('You must enter a description for this Role');
        }

		if (isset($_POST['color_' . $key]) && ! empty($_POST['color_' . $key])) {
			//$this->roledata[$key]['color'] = coresurvey_unsan($_POST['color_' . $key]);
			$this->roledata[$key]['color'] = $_POST['color_' . $key];
		} else {
			$proceed = false;
			$error->AddMessage('You must enter a color for this Role');
		}

        $this->roledata[$key]['comment'] = isset($_POST['comment_' . $key]) ? $_POST['comment_' . $key] : '';

        return $proceed;
    } // end function

    /**
     * Saves a survey, by incrementing version number, changing date to today
     * and compressing data
     */

    protected function saveSurvey() {
        $this->versionnumber = $this->versionnumber + 1;
        $this->createdate = time();
        $this->roledata = $this->compress($this->roledata);
	$this->matrix = $this->compress($this->matrix);

        $success = coresurvey_save_role_survey($this);

        // ok final step is to decompress the roledata, just in case any other
        // functions are using the data

        $this->roledata = $this->deCompress($this->roledata);
	$this->matrix	= $this->deCompress($this->matrix);
        return $success;
    } // end function

    /**
     * Adds a new Role
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
            $this->roledata[] = $tmp;
            // now we need to add this role to the weightings for all aspects.
            $this->addRoletoWeightings($tmp['name']);


        } else {
            $error->ChangeTitle('Oops, you missed something');
            $core_page->addJavascript('ShowEdits("addrole");');
        }

        return $proceed;
    } // end function

    /**
     * Loops through the entire dataset and adds the new role to each
     * aspects weightings
     */

    protected function addRoletoWeightings($name) {
        $idx = -1;
        // first of all find the index of the new role
        foreach ($this->roledata AS $key => $val) {
            if ($val['name'] == $name) {
                $idx = $key;
            }
        } // end loop

        // check for error, this should not error out, but this checks just in case!
        if ($idx == -1) return;

        // we now have an index, loop though all of the roles, and aspects
        foreach($this->roledata AS $key => $role) {
            // loop though all of the aspects
            foreach ($role['aspects'] AS $akey => $aspect) {
                // add the new weighting
                $this->roledata[$key]['aspects'][$akey]['weightings'][$idx] = 0;
            } // end aspect loop
        } // end role loop
    } // end function

    /**
     * Loops through the entire dataset and removes the role from the weightings
     */

    protected function removeRolefromWeightings($idx) {
        // we now have an index, loop though all of the roles, and aspects
        foreach($this->roledata AS $key => $role) {
            // loop though all of the aspects
            foreach ($role['aspects'] AS $akey => $aspect) {
                // add the new weighting
                unset($this->roledata[$key]['aspects'][$akey]['weightings'][$idx]);
            } // end aspect loop
        } // end role loop
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
     * creates html needed to display the roles, gets the page object injected
     */

    function displayAdmin($page) {
        // if there are no roles then don't display them!!

        if (empty($this->roledata)) {
            global $error;
            $error->AddMessage('There are no Roles defined! Your survey can not be taken!');
            return '<p class="dpad">There are no Roles defined for this survey, you should correct this immediately!</p>';
        }

        // ok it appears that there are some roles....
    } // end function

    /**
     * Displays the Roles in Tabbed fashion,
     * No also need to shoehorn in the threshholds
     */

    function displayRolesAdmin() {
        global $core_page;
        $s = $j = '';

        // if it's empty then return
        //if (empty($this->roledata)) return;

        //coresurvey_debug($this->roledata);

        // first of all do the tabs
        $s = '<ul class="regtabs">' . "\n";

	// we always have the instructions
	$s .=	'<li>
		    <a href="#instructions">
			Instructions
		    </a>
		</li>';

	if (! empty($this->roledata)) {
	    // loop through all of the tabs
	    foreach($this->roledata AS $key => $info) {
		$s .=   '<li><a href="#' . $this->clean($info['name']) . '">' . $info['name'] . '</a></li>';
	    }
	}

	// threshhold tabs
	$s .=	'<li>
		    <a href="#thresholds">
			Results Matrix
		    </a>
		</li>' . "\n";

        $s .= '</ul>' . "\n";

        // now create the panes
        $s .=   '<div class="regpanes">' . "\n";

	// instructions tabs
	$s .=	'<div class="tabbkgd">
		    <form action="" method="POST">
			<fieldset class="dpad adminform">
			    <legend>
				Survey Instructions
			    </legend>
			    <p>
				Instructions to appear at the top of the Role Survey
			    </p>
			    <textarea name="role_summary" id="role_summary">' . (isset($this->matrix['instructions']['role_summary']) ? $this->matrix['instructions']['role_summary'] : '') . '</textarea>
			    <p>
				Summary to appear on Role Analysis sumamry
			    </p>
			    <textarea name="summary" id="summary">' . (isset($this->matrix['instructions']['summary']) ? $this->matrix['instructions']['summary'] : '') . '</textarea>

			    <input type="hidden" name="warning" id="warning" value="' . (isset($this->matrix['instructions']['warning']) ? $this->matrix['instructions']['warning'] : '') . '">
			    <p>
				Instructions
			    </p>
			    <textarea name="instructions" id="instructions" class="w95">' . (isset($this->matrix['instructions']['instructions']) ? $this->matrix['instructions']['instructions'] : '') . '</textarea>
			    <div class="dpad tcenter">
				<button type="submit">Submit</button>
				<input type="hidden" value="0" name="edit_instructions">
			    </div>
			</fieldset>
		    </form>
		</div>';

	$j .= 'CKEDITOR.replace( "summary" );' . "\n";
	//$j .= 'CKEDITOR.replace( "warning" );' . "\n";
	$j .= 'CKEDITOR.replace( "instructions" );' . "\n";

	$j .= 'CKEDITOR.replace( "role_summary" );' . "\n";

	if (! empty($this->roledata)) {

	    foreach($this->roledata AS $key => $info)  {
		$s .=   '<div class="tabbkgd">' . "\n";

		// add in the edit....
		$tmp = $this->DisplayRoleEdit($key);
		$s .= $tmp['html'];
		$j .= $tmp['java'];

		// do the description of the Role
		$s .=   '<p class="dpad"><span class="colorblock" style="background: ' . $this->roledata[$key]['color'] . '"><b>Color: ' . $this->roledata[$key]['color'] . '</b></span><br/><b>Description: </b>' . $info['description'] . '<br/>
			<b>Comment:</b> ' . $info['comment'] . '</p>';

		// add the add aspect
		$tmp = $this->addAspectForm($key);
		$s .=   $tmp['html'];
		$j .=   $tmp['java'];

		// display the aspects for this Role
		if (! empty($info['aspects'])) {
		    $tmp =   $this->displayAspects($key);
		    $s .=   $tmp['html'];
		    $j .=   $tmp['java'];
		} else {
		    $s .=   '<p class="dpad">There are no Aspects in this Role!</p>';
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
		$j .= '$("#deleterolebutton_' . $key . '").colorbox({width: "80%", height: "80%", iframe: true, Overlayclose: false, href: "' . $core_page->base_url . '/mod/coresurvey/roles_admin/delete-role.php?r=' . $key . '"});';

		$s .= '</div>' . "\n";
	    } // end loop
	} // end if there are roles

	// add the results threshholds
	$s .=   '<div class="tabbkgd">' . "\n";

	// add the form
	$s .=	'<form action="" method="POST">
		    <fieldset class="adminform">
			<legend>
			    Thresh Hold Percentages
			</legend>
			<label>Match Threshhold</label>
			<p>
			    The percentage at which a Result Matches a Role
			</p>
			<input type="text" name="match_thresh_hold" id="match_thresh_hold" class="w5" value="' . $this->get_match_thresh_hold() . '">%
			<label>Separated Threshhold</label>
			<p>
			    The percentage at which a Role must be different to count as being separated
			</p>
			<input type="text" name="separate_thresh_hold" id="separate_thresh_hold" class="w5" value="' . $this->get_separate_thresh_hold() . '">%
			<div class="dpad tcenter">
			    <button type="submit">Submit</button>
			    <input type="hidden" name="edit_thresh_hold" value="0">
			</div>
		    </fieldset>
		</form>';

	// ok add in the tags
	$s .=	'<form class="dpad" action="" method="POST">
		    <fieldset class="adminform">
			<legend>Matrix Tags</legend>
			<p>
			    Add your messages into the correct boxes. Use "%1", "%2" etc as a token to be replaced by the Role name / Role name + URL at runtime
			</p>
			</hr>';

	// we always need to have a M0 or no Roles match
	$s .=	'<p><b>No Role matches</b></p>
		<dl class="fclear bpad">
		    <dd class="fleft tleft w45">
			<h2>Advice Offered</h2>
			<textarea name="M0_0_offered" id="M0_0_offered" class="w90">' . (isset($this->matrix['tags']['M0_0_offered']) ? $this->matrix['tags']['M0_0_offered'] : '') . '</textarea>
		    </dd>
		    <dd class="fright tleft w45">
			<h2>Further Advice</h2>
			<textarea name="M0_0_further" id="M0_0_further" class="w90">' . (isset($this->matrix['tags']['M0_0_further']) ? $this->matrix['tags']['M0_0_further'] : '') . '</textarea>
		    </dd>
		</dl>
		<div class="fclear"></div>';

	$s .=	'<p><b>No Role matches but separated</b></p>
		<dl class="fclear bpad">
		    <dd class="fleft tleft w45">
			<h2>Advice Offered</h2>
			<textarea name="M0_1_offered" id="M0_1_offered" class="w90">' . (isset($this->matrix['tags']['M0_1_offered']) ? $this->matrix['tags']['M0_1_offered'] : '') . '</textarea>
		    </dd>
		    <dd class="fright tleft w45">
			<h2>Further Advice</h2>
			<textarea name="M0_1_further" id="M0_1_further" class="w90">' . (isset($this->matrix['tags']['M0_1_further']) ? $this->matrix['tags']['M0_1_further'] : '') . '</textarea>
		    </dd>
		</dl>
		<div class="fclear"></div>';

	// only do if there is more than one role
	if (count($this->roledata) > 0) {
	    for ($n=1; $n <= count($this->roledata); $n++) {
		// Matches n0n separated
		$s .=	'<p><b>' . $n . ' Role match</b></p>
			<dl class="fclear bpad">
			    <dd class="fleft tleft w45">
				<h2>Advice Offered</h2>
				<textarea name="M' . $n . '_0_offered" id="M' . $n . '_0_offered" class="w90">' . (isset($this->matrix['tags']['M' . $n . '_0_offered']) ? $this->matrix['tags']['M' . $n . '_0_offered'] : '') . '</textarea>
			    </dd>
			    <dd class="fright tleft w45">
				<h2>Further Advice</h2>
				<textarea name="M' . $n . '_0_further" id="M' . $n . '_0_further" class="w90">' . (isset($this->matrix['tags']['M' . $n . '_0_further']) ? $this->matrix['tags']['M' . $n . '_0_further'] : '') . '</textarea>
			    </dd>
			</dl>
			<div class="fclear"></div>';
		// Matches but separated
		$s .=	'<p><b>' . $n . ' Role match but Separated</b></p>
			<dl class="fclear bpad">
			    <dd class="fleft tleft w45">
				<h2>Advice Offered</h2>
				<textarea name="M' . $n . '_1_offered" id="M' . $n . '_1_offered" class="w90">' . (isset($this->matrix['tags']['M' . $n . '_1_offered']) ? $this->matrix['tags']['M' . $n . '_1_offered'] : '') . '</textarea>
			    </dd>
			    <dd class="fright tleft w45">
				<h2>Further Advice</h2>
				<textarea name="M' . $n . '_1_further" id="M' . $n . '_1_further" class="w90">' . (isset($this->matrix['tags']['M' . $n . '_1_further']) ? $this->matrix['tags']['M' . $n . '_1_further'] : '') . '</textarea>
			    </dd>
			</dl>
			<div class="fclear"></div>';
	    } // end loop
	} // end if roles exist

	/*echo '<pre>';
	print_r($this->roledata);
	exit;*/


	// end tag form
	$s .=	'	<div class="dpad tcenter">
			    <button type="submit">Submit</button>
			    <input type="hidden" value="0" name="edit_matrix">
			</div>
		    </fieldset>
		</form>' . "\n";




	// end the matrix tab pane
	$s .=	'</div>';

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
        if (! isset($this->roledata[$key])) {
            return '<p class="dpad">Sorry, this role does not exist!!</p>';
        }

        $s = '';

        // create the form
        $s .=   '<form action="" method="POST">
                    <fieldset class="adminform">
                        <legend>Remove Role: ' . (isset($this->roledata[$key]['name']) ? $this->roledata[$key]['name'] : 'Removed') . '</legend>
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
		$color = isset($this->roledata[$key]['color']) ? $this->roledata[$key]['color'] : "#ffffff";

        // add in the buttons
        $s .=   '<div class="core_edit_buttons tright bpad">
                    <button type="button" class="core_button button_edit fright" id="editrole_' . $key . '_button_add">Edit Role: ' . $this->roledata[$key]['name'] . '</button>
                    <button type="button" class="core_button core_button_close button_close" id="editrole_' . $key . '_button_close">Close Panel</button>
                    <div class="fclear"></div>
                </div>
                <div class="core_edit_container dpad" id="editrole_' . $key . '_container">
                    <form action="" method="POST">
                    <fieldset class="adminform">
                        <legend>Edit Role: ' . $this->roledata[$key]['name'] . '</legend>
                        <p>Complete the Form to edit this Role.</p>
                        <label for="name_' . $key . '">Role Name <span class="required">*</span></label>
                        <input type="text" class="w95" name="name_' . $key . '" value="' . (isset($_POST['name_' . $key]) ? coresurvey_unsan($_POST['name_' . $key]) : $this->roledata[$key]['name']) . '"/>
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
                        <textarea name="description_'. $key . '" class="w95">' . (isset($_POST['description_' . $key]) ? coresurvey_unsan($_POST['description_' . $key]) : $this->roledata[$key]['description']) . '</textarea>
                        <label for="comment_' . $key . '">Role Comments</label>
                        <textarea name="comment_' . $key . '" class="w95">' . (isset($_POST['comment_' . $key]) ? coresurvey_unsan($_POST['comment_' . $key]) : $this->roledata[$key]['comment']) . '</textarea>
                        <div class="dpad tcenter">
                            <button type="submit">Edit Role</button>
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
                    <button type="button" class="core_button button_add" id="addaspect_' . $key . '_button_add">Add a New Aspect</button>
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
                    <legend>Aspect Title and Comments</legend>
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
                    <legend>Role Weightings</legend>
                        <dl class="w60 fcenter">' . "\n";

        /*
        // loop through the roles
        foreach($this->roledata AS $idx => $val) {
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
        foreach($this->roledata AS $idx => $val) {
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
                        <button type="submit">Add Aspect</button>
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
     * Displays the Aspects for one Role
     */

    function displayAspects($key) {
        global $core_page;

        $tmp = array('html' => '', 'java' => '');
        $s = $j = '';

        // firstly we need to work out how many columns
        // edit + title + roles + delete
        $cols = count($this->roledata) + 3;

        $s .=   '<table class="w100 admintable">
                    <tr>
                        <th class="w10">Edit</th>
                        <th>Title</th>' . "\n";
        // add the roles
        foreach($this->roledata AS $idx => $role) {
            $s .=   '<th class="w5">' . substr($role['name'], 0, 3) . '..</th>' . "\n";
        }

        $s .=   '       <th class="w10">Delete</th>
                    </tr>' . "\n";

        // ok now do the aspects
        foreach ($this->roledata[$key]['aspects'] AS $idx => $asp) {
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
            $j .=   '$("#editaspect' . $key . '_' . $idx . '").colorbox({width: "80%", height: "95%", iframe: true, overlayClose: false, href: "' . $core_page->base_url . '/mod/coresurvey/roles_admin/edit_aspect.php?r=' . $key . '&i=' . $idx .'"});' . "\n";
            $j .=   '$("#deleteaspect' . $key . '_' . $idx . '").colorbox({width: "50%", height: "40%", iframe: true, overlayClose: false, href: "' . $core_page->base_url . '/mod/coresurvey/roles_admin/delete_aspect.php?r=' . $key . '&i=' . $idx .'"});' . "\n";
        } // end aspects

        // end the table
        $s .=   '</table>' . "\n";

        $tmp['html'] = $s;
        $tmp['java'] = $j;

        return $tmp;
    } // end function

    /**
     * Displays a Kill Aspect form
     * Role / Index of Aspect
     */

    function killAspectForm($r, $i) {
        $s = '<form action="" method="POST">
                <fieldset class="dpad adminform">
                    <legend>Delete Aspect: ' . (isset($this->roledata[intval($r)]['aspects'][intval($i)]['title']) ? $this->roledata[intval($r)]['aspects'][intval($i)]['title'] : 'Deleted') . '</legend>
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

    function editAspectForm($role, $idx) {
        $tmp = array('html' => '', 'java' => '');
        $s = $j = '';

        $s .=   '<form action="" method="POST">' . "\n";

        // comments and title
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Aspect Title and Comments</legend>
                    <label for="aspect_title">Title <span class="required">*</span></label>
                    <input type="text" class="w95" name="aspect_title" value="' . (isset($_POST['aspect_title']) ? coresurvey_unsan($_POST['aspect_title']) : $this->roledata[$role]['aspects'][$idx]['title']) .'"/>
                    <label for="aspect_comments">Admin Comment</label>
                    <textarea class="w95" name="aspect_comment"></textarea>
                </fieldset>' . "\n";

        // alignments
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Alignment Statements</legend>' . "\n";

        // loop through the alignments
        foreach ($this->alignment AS $adx =>$val) {
            $s .=   '<label for="aspect_alignment_' . $adx . '">' . $val . '<span class="required">*</span></label>
                    <textarea name="aspect_alignment_' . $adx . '" class="w95">' . (isset($_POST['aspect_alignment_' . $adx]) ? coresurvey_unsan($_POST['aspect_alignment_' . $adx]) : $this->roledata[$role]['aspects'][$idx]['alignment'][$adx]) . '</textarea>' . "\n";
        } // end loop

        $s .=   '</fieldset>';

        // weightings
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Role Weightings</legend>
                        <dl class="w60 fcenter">' . "\n";

        /*
        // loop through the roles
        foreach($this->roledata AS $rdx => $val) {
            $s .=   '<dt class="w60 fleft">
                        <label for="">' . $val['name'] . ' </label>
                    </dt>
                    <dd class="w40 fright">
                      ' . $this->CreateWeightingSelect('aspect_weighting_' . $rdx, $this->roledata[$role]['aspects'][$idx]['weightings'][$rdx]) . '
                    </dd>
                    <dt class="fclear"></dt>' . "\n";

        }
         */

        // version 2 with sliders
        foreach($this->roledata AS $rdx => $val) {
            $s .=   '<dt class="w30 fleft">
                        <label for="">' . $val['name'] . '</label>
                        <input type="hidden" name="aspect_weighting_' . $rdx . '" id="aspect_weighting_' . $role . '_' . $rdx . '" value="' . $this->roledata[$role]['aspects'][$idx]['weightings'][$rdx] . '"/>
                     </dt>
                     <dd class="w10 fleft tcenetr">
                        <div id="aspect_weighting_header_' . $role . '_' . $rdx . '">
                            ' . $this->roledata[$role]['aspects'][$idx]['weightings'][$rdx] . '%
                        </div>
                    </dd>
                    <dd class="w60 fleft tcenter">
                        <div id="aspect_weighting_slider_' . $role . '_' . $rdx . '">
                        </div>
                    </dd>
                    <dd class="fclear">
                    </dd>';
            $j .= 'CreateSlider("' . $role . '_' . $rdx . '", ' . $this->roledata[$role]['aspects'][$idx]['weightings'][$rdx] . ');' . "\n";
        } // end weightings loop


        $s .=   '       </dl>
                        <div class="fclear"></div>
                </fieldset>' . "\n";

        // add the submit
        $s .=   '<fieldset class="dpad adminform">
                    <legend>Edit Aspect</legend>
                    <div class="dpad tcenter">
                        <button type="submit">Save Changes</button>
                        <input type="hidden" name="editaspect" value="' . $idx . '"/>
                        <input type="hidden" name="role" value="' . $role . '"/>
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
        return count($this->roledata);
    } // end function

    /**
     * retuens the number of aspects
     */

    function reportNumberofAspects() {
        $c = 0;

        foreach($this->roledata AS $rkey => $rval) {
            $c = $c + count($rval['aspects']);
        }


        return $c;
    } // end function

    /**
     * loads in the survey by period
     */

    public function fetch_by_period($start, $end) {
	$this->surveys = coresurvey_get_role_surveys_member_by_date($start, $end);
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
			'num'	    => 1
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
		OR ! isset($this->roledata[$role]['aspects'][$aspect]))
	{
	    return "Could not find this Aspect!!";
	}

	return $this->roledata[$role]['name'] . ' - ' . $this->roledata[$role]['aspects'][$aspect]['title'];
    } // end function

    /**
     * gets the roles summary
     */

    public function get_roles_summary() {
	$data = array();

	foreach($this->roledata as $key => $val) {
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
