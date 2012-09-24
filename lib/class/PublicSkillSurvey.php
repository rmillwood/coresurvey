<?php
    /**
     * Skills Surevey class that displays public code
     */

     class PublicSkillSurvey extends Survey {
         public $type = 'Skill';
         public $id; // id of the survey from the database
         public $data = array(); // the survey in array form
         public $results = array(); // the list of questions,
         protected $questions_per_page = 5;
         public $clientresults = array(); // array of results, broken down into role, aspect, answer
         protected $survey_taken = false; // boolean for setting whether the survey has been taken or not...
	protected $graph_settings = array();

	protected $paused = false;

	public $matrix = array();

	private $selected_type = 0;

        public $member_survey; // a members object

	public $new_member_result_id;

        // the alignments
        protected $alignment = array(
                                0 => 'Not yet begun',
                                1 => 'Beginner',
                                2 => 'Still learning',
                                3 => 'Competent',
                                4 => 'Confident'
                              );
         /**
          * Constructor
          */

         function __construct() {
             // unset($_SESSION['skill_coresurvey']);
             // look for a session, if it doesn't exist then we need to load
             // in the latest survey
              // look for a get variable, if not foud then look for a session,
             // look for a session, if it doesn't exist then we need to load
             // in the latest survey

             if (isset($_GET['s'])) {
                 // remove any previous sessions
                 $this->killSession();
                 // load the members survey using the GET var
                 if ($this->loadMemberSurvey(intval($_GET['s']))) {
                     // it s all ok, so load in the survey
                     $this->loadSpecificSurvey($this->member_survey->survey_id);
		     $this->paused = true;
                     //$this->ShowDebugHalt($this->results);
                 } else {
                     // just load in the survey
                     $this->loadSurvey();
                 }
             } else {
                 /*if (! isset($_SESSION['skill_coresurvey']) ) {
                     // fetch the survey and populate the fields
                     $this->loadSurvey();
                 } else {
                     // we need to pull survey data in from the sessions
                     $this->loadSurveyfromSession();
                 }*/
		 $this->loadSurvey();
             }

	     // ok do we need to cull the survey??
	     if (isset($_GET['type'])) {
		 $this->cull_roles(intval($_GET['type']));
		 $this->selected_type = intval($_GET['type']);
	     }

             // has the survey been submitted?? and if it has is it a completed survey $_POST['rolesurvey'] == 1
             // or is it a paused survey $_POST['rolesurvey'] == 0
             if (isset($_POST['skillsurvey']) && $_POST['skillsurvey'] == 1) {
                 // we have some results

                 $this->resultsPopulate();
                 $this->survey_taken = true;

                 // we now have the results, save them
                 $this->storeResults();

                 // update the taken field in the survey
                 $this->updateSurveyTaken();
             } // end results

             // has the paused button been used? if so save the survey for future use
             if (isset($_POST['skillsurvey']) && $_POST['skillsurvey'] == 0) {

                 // check for whether this survey has been paused before, A little inefficient, as the survey should already have been loaded previously
                 // above using $_GET['s']. This just enforces it

                 if (isset($_POST['paused']) && intval($_POST['paused']) > 0 && $this->loadMemberSurvey(intval($_POST['paused']))) {
                     // this survey has been paused previously
                     $this->resultsPopulate();
                     $this->survey_taken = false;
                     $this->saveResults();

                 } else {
                     // this survey has NOT been paused before
                     $this->resultsPopulate();
                     $this->survey_taken = false;
                     $this->storeResults();

                 }


                 // now redirect back to the dashboard
                 // TODO: change this for the live version
                 global $CFG, $cm;
                 coresurvey_page_redirect($CFG->wwwroot . '/mod/coresurvey/view.php?id=' . $cm->id);
             }

             //coresurvey_debug($_POST);
         } // end constructor


	 /**
	  * Returns the survey type
	  */

	 public function get_type($role_id = null) {
	     if (is_null($role_id) OR ! isset($this->data[$role_id])) {
		 return "undefined";
	     } else {
		 return $this->data[$role_id]['name'];
	     }

	 } // end get_type


         /**
          * Loads a specific survey number
          */

         protected function loadSpecificSurvey($id = 0) {
            $model = coresurvey_get_specific_skill($id);

            if ($model) {
                $this->id = $model->id;
                $this->data = $this->deCompress($model->skilldata);
		        $this->matrix = $this->deCompress($model->matrix);

            }
         } // end function

         /**
          * Takes a survey and saves it in the database.
          */

         protected function storeResults() {
            global $USER, $CFG;
            // make sure that the libraries are loaded :-(
            require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Result.php');
            require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillResult.php');

	    /*
	     * If it's a paused survey then purge it.
	     */

	    if (isset($_POST['paused']) && $_POST['paused'] > 0) {

		$this->member_survey->kill();
	    }


            // check to see if there was a paused survey in effect
	    /*
            if (isset($_POST['paused']) && $_POST['paused'] > 0) {
                // there is a paused survey in effect
                $this->member_survey->setResults($this->results);
                $this->member_survey->setCompleted($this->survey_taken);
                $this->member_survey->setEndDate();
                $this->member_survey->save();
            } else {
                // no survey paused
                $r = new SkillResult();

                // set the data
                $r->setMemberID($USER->id);
                $r->setSurveyID($this->id);
                $r->setCompleted($this->survey_taken);
                $r->setResults($this->results);
		$r->setType($this->selected_type);
                $r->saveResults();

		$this->new_member_result_id = $r->id;
            }
	     */

	    $r = new SkillResult();

                // set the data
                $r->setMemberID($USER->id);
                $r->setSurveyID($this->id);
                $r->setCompleted($this->survey_taken);
                $r->setResults($this->results);
		$r->setType($this->selected_type);
                $r->saveResults();

		$this->new_member_result_id = $r->id;


         } // end function



         /**
          * populates the results variable using the POST data...
          */

         protected function resultsPopulate() {
             // don't do if it's empty..
             if (empty($_POST)) {
                 //return;
             }

             // loop through all of the POST data
             foreach($_POST AS $key => $val) {
                // a valid answer will have "answer_" at the start
                 if (strstr($key, "answer_")) {
                     $answer = str_replace("answer_", "", $key);
                     // ok we have a idx for the results array, loop through and if it exists, add the answer
                     foreach ($this->results AS $rkey => $rval) {
                         if ($rval['idx'] == $answer) {
                             $this->results[$rkey]['answer'] = intval($val);
                         }
                     } // end results loop
                 }
             } // end loop

             // now save the updated answers in the session
             $_SESSION['skill_coresurvey']['answers'] = $this->compress($this->results);

         } // end function

         /**
          * Loads the survey from the session
          */

         protected function loadSurveyfromSession() {
             $this->id = $_SESSION['skill_coresurvey']['id'];
             $this->data = $this->deCompress($_SESSION['skill_coresurvey']['survey']);
             $this->results = $this->deCompress($_SESSION['skill_coresurvey']['answers']);
         } // end function

         /**
          * Loads a survey and populates the fields
          */

         protected function loadSurvey() {
            $model = coresurvey_get_last_skill();

            $this->id = $model->id;
            $this->data = $this->deCompress($model->skilldata);
	    $this->matrix = $this->deCompress($model->matrix);

            $this->results = $this->createQuesionList();

            // ok lets load the two arrays into the session, this will minimise
            // db load....:-)
            $_SESSION['skill_coresurvey']['id'] = $this->id;
            $_SESSION['skill_coresurvey']['survey'] = $this->compress($this->data);
            $_SESSION['skill_coresurvey']['answers'] = $this->compress($this->results);

            //coresurvey_debug($_SESSION);
         } // end function


         /**
          * Shows the survey, html and javascript
          */

         function showSurvey() {
             $s = $j = '';

             // fudge so each question has it's own page
             $this->questions_per_page = 5;

             // work out how many pages we have.
             $questions = count($this->results);
             $pages = ceil($questions / $this->questions_per_page);
             $idx = 0; // initial array pointer
             //coresurvey_debug($this->results);

             // ok create the form
             $s .=  '<form action="" method="POST" id="surveyform">' . "\n";

              // add in the pause button
             $s .=  '<div class="dpad tcenter box_border">
                        You can Pause the survey and return to it on a later date <button type="submit">Pause Survey</button>
                     </div>';

             for($pg = 1; $pg <= $pages; $pg++) {
                // create the fieldset
                 $s .=  '<fieldset>
                            <legend>Question ' . $pg . '</legend>' . "\n";
                 // create start / end array pointer
                 $start = ($pg * $this->questions_per_page) - $this->questions_per_page;
                 $end = ($pg * $this->questions_per_page) - 1;

                 // loop through the questions....
                 for ($i = $start; $i <= $end; $i++) {
                     // make sure that this question actually exists
                     if (isset($this->results[$i])) {
                         //$s .= $this->results[$i]['idx'] . '<br/>';

                         // fetch the answer, this will be -1 if it's a previous
                         // survey, otherwise it will be the result
                         $answer = $this->results[$i]['answer'] == -1 ? 50 : $this->results[$i]['answer'];

                         // create the dl
                         $s .=  '<dl>' . "\n";

                         // create the question
                         $s .=  '<dt class="w30 fleft dpad">
                                    ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['title'] . '
                                </dt>' . "\n";

                         // create the slider
                         $s .=  '<dd class="w30 fleft dpad lpad tcenter">
                                    <div id="slider_header_' . $this->results[$i]['idx'] . '" class="tcenter bpad">neutral</div>
                                    <div id="slider_' . $this->results[$i]['idx'] . '" class="w95"></div>
                                    <input type="hidden" name="answer_' . $this->results[$i]['idx'] . '" id="answer_' . $this->results[$i]['idx'] . '" value="' . $answer . '"/>
                                </dd>' . "\n";

                         $j .= 'CreateSliderVertical("' . $this->results[$i]['idx'] . '", ' . $answer . ');' . "\n";

                         // create the statements, at the moment, these are hardcoded... yuck!!!
                         $s .=  '<dd class="w30 fright dpad">
                                    <div id="statement_container_' . $this->results[$i]['idx'] . '">
                                        <div id="statement_' . $this->results[$i]['idx'] . '_0" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][0] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_25" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][1] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_50" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][2] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_75" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][3] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_100" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][4] . '
                                        </div>
                                    </div>
                                </dd>';


                         // end the dl
                         $s .=  '</dl>
                                <div class="fclear"></div>' . "\n";
                     } // end if it actually exists
                 } // end loop
                 // end the fieldset
                 $s .=  '</fieldset>' . "\n";
             }// end page loop

             // is this a stored paused survey??
             $survey_id = isset($this->member_survey->id) ? $this->member_survey->id : 0;

             // now add the submit
             $s .=  '<fieldset>
                        <legend>Submit Answers</legend>
                        <div class="dpad tcenter">
                            <button type="submit" id="submitsurvey">Submit Answers</button>
                            <input type="hidden" name="skillsurvey" id="skillsurveyswitch" value="0"/>
                            <input type="hidden" name="paused" value="' . $survey_id . '"/>
                        </div>
                    </fieldset>';

             // add the submit click to change the rolesurvey key
             $j .=  '$("#submitsurvey").click(function() {
                        $("#skillsurveyswitch").attr("value", 1);
                        return true;
                    });';

             // end form
             $s .=  '</form>' . "\n";

             if (! empty($j)) {
                 $j =   '<script type="text/javascript">
                            $(document).ready(function() {
                                ' . $j . '
                            });
                        </script>' . "\n";

                 $s .= $j;
             }

             return $s;
         } // end function

         /**
          * Shows the survey, html and javascript
          */

         function showVerticalSurvey() {
             $s = $j = '';

	     /**
	      * Hack to reverse the alignment array so it displays correctly
	      */

	     $this->alignment = array_reverse($this->alignment);

             // fudge so each question has it's own page
             $this->questions_per_page = 5;

             // work out how many pages we have.
             $questions = count($this->results);
             $pages = ceil($questions / $this->questions_per_page);
             $idx = 0; // initial array pointer
             //coresurvey_debug($this->results);

             // ok create the form
             $s .=  '<form action="" method="POST" id="surveyform">' . "\n";

              // add in the pause button
             $s .=  '<div class="dpad tcenter box_border">
                        You can Pause the survey and return to it on a later date <button type="submit">Pause Survey</button>
                     </div>';

             for($pg = 1; $pg <= $pages; $pg++) {
                // create the fieldset
                 $s .=  '<fieldset>
                            <legend>Page ' . $pg . ' of ' . $pages . '</legend>' . "\n";
                 // create start / end array pointer
                 $start = ($pg * $this->questions_per_page) - $this->questions_per_page;
                 $end = ($pg * $this->questions_per_page) - 1;

                 // start the container div
                 $s .=  '<div> <!-- container start -->' . "\n";

                 $s .=  '<ul class="dpad w100 question_list">' . "\n";

                 // loop through the questions....
                 for ($i = $start; $i <= $end; $i++) {
                     // make sure that this question actually exists
                     if (isset($this->results[$i])) {
                        // fetch the answer, this will be -1 if it's a previous
                        // survey, otherwise it will be the result
                        $answer = $this->results[$i]['answer'] == -1 ? 50 : $this->results[$i]['answer'];

                        $s .=   '<li>
                                    <div class="vertical_pad">' . "\n";

                        // do the question title
                        $s .=   '<p class="vertical_question_title">
                                    ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['title'] . '
                                </p>';

                        // do the slider
                        $s .=   '<div class="tcenter vertical_slider_container">
                                    <div id="slider_' . $this->results[$i]['idx'] . '" class="w95"></div>
                                    <div id="slider_header_' . $this->results[$i]['idx'] . '" class="tcenter bpad">neutral</div>
                                    <input type="hidden" name="answer_' . $this->results[$i]['idx'] . '" id="answer_' . $this->results[$i]['idx'] . '" value="' . $answer . '"/>
                                </div>';

                        // now do the statements
                        $s .=   '<div id="statement_container_' . $this->results[$i]['idx'] . '" class="vertical_statements_container">
                                        <div id="statement_' . $this->results[$i]['idx'] . '_0" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][0] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_25" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][1] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_50" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][2] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_75" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][3] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_100" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][4] . '
                                        </div>
                                    </div>';
			// display differently if it is paused
			$j .= 'CreateSliderVertical("' . $this->results[$i]['idx'] . '", ' . $answer . ');' . "\n";


                        $s .=   '   </div>
                                </li>' . "\n";
                     } // end if question exists
                 } // end question loop

                 $s .=  '</ul>
                        <div class="fclear"></div>';

                 // end the container div
                 $s .=  '</div> <!-- container end -->' . "\n";


                 // end the fieldset
                 $s .=  '</fieldset>' . "\n";
             }// end page loop

             // is this a stored paused survey??
             $survey_id = isset($this->member_survey->id) ? $this->member_survey->id : 0;

             // now add the submit
             $s .=  '<fieldset>
                        <legend>Submit Answers</legend>
                        <div class="dpad tcenter">
                            <button type="submit" id="submitsurvey">Submit Answers</button>
                            <input type="hidden" name="skillsurvey" id="skillsurveyswitch" value="0"/>
                            <input type="hidden" name="paused" value="' . $survey_id . '"/>
                        </div>
                    </fieldset>';

             // add the submit click to change the rolesurvey key
             $j .=  '$("#submitsurvey").click(function() {
                        $("#skillsurveyswitch").attr("value", 1);
                        return true;
                    });';

             // end form
             $s .=  '</form>' . "\n";

             if (! empty($j)) {
                 $j =   '<script type="text/javascript">
                            $(document).ready(function() {
                                ' . $j . '
                            });
                        </script>' . "\n";

                 $s .= $j;
             }

             return $s;
         } // end function

	 /**
	  * Shows a survey vertically, but only by one type
	  */

	 public function showVerticalSurveybyCompetency($type) {
	     global $textr;
	    $s = $j = '';

	     /**
	      * Hack to reverse the alignment array so it displays correctly
	      */

	     //$this->alignment = array_reverse($this->alignment);

             // fudge so each question has it's own page
             $this->questions_per_page = 8;

             // work out how many pages we have.
             $questions = count($this->results);
             $pages = ceil($questions / $this->questions_per_page);
             $idx = 0; // initial array pointer
             //coresurvey_debug($this->results);

             // ok create the form
             $s .=  '<form action="" method="POST" id="surveyform">' . "\n";

              // add in the pause button
             $s .=  '<div class="dpad tcenter box_border">
                        ' . $textr->get_data(9) . ' <button type="submit">' . $textr->get_data(10) . '</button>
                     </div>';

             //for($pg = 1; $pg <= $pages; $pg++) {

                 // create start / end array pointer
                 //$start = ($pg * $this->questions_per_page) - $this->questions_per_page;
                 //$end = ($pg * $this->questions_per_page) - 1;
		 $start = 0;
		 $end = $questions;

                 // start the container div
                 $s .=  '<div> <!-- container start -->' . "\n";

                 $s .=  '<ul class="dpad w100 question_list">' . "\n";

		 // ok we need to put in some titles
		 $s .=	'<li class="title_key">
			     <div class="vertical_pad tright title_key">
				 <p class="vertical_question_title tright">What you can do</p>
				 <div class="vertical_slider_container tright">Ability level</div>
			     </div>
				 <div class="tright">What you might think</div>
			     </li>' . "\n";

                 // loop through the questions....
                 for ($i = $start; $i <= $end; $i++) {
                     // make sure that this question actually exists
                     if (isset($this->results[$i])) {
                        // fetch the answer, this will be -1 if it's a previous
                        // survey, otherwise it will be the result
                        $answer = $this->results[$i]['answer'] == -1 ? 50 : $this->results[$i]['answer'];

                        $s .=   '<li>
                                    <div class="vertical_pad">' . "\n";

                        // do the question title
                        $s .=   '<p class="vertical_question_title">
                                    ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['title'] . '
                                </p>';

                        // do the slider
                        $s .=   '<div class="tcenter vertical_slider_container">
                                    <div id="slider_' . $this->results[$i]['idx'] . '" class="w95 ui-slider ui-slider-vertical ui-widget ui-widget-content ui-corner-all"></div>

                                    <input type="hidden" name="answer_' . $this->results[$i]['idx'] . '" id="answer_' . $this->results[$i]['idx'] . '" value="' . $answer . '"/>
                                </div>   </div>
                                <div class="abilities-thought-bubbles"><div class="abilities-thought-bubble1"></div> <div class="abilities-thought-bubble2"></div></div>';

                        // now do the statements
                        $s .=   '<div id="statement_container_' . $this->results[$i]['idx'] . '" class="vertical_statements_container">
					<div id="slider_header_' . $this->results[$i]['idx'] . '" class="slider_header_title tcenter bpad">&nbsp;<span style="display: none;">' . $this->alignment[2] . '</span></div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_0" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][4] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_25" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][3] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_50" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][2] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_75" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][1] . '
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_100" class="align_statement">
                                            ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][0] . '
                                        </div>
					<div class="fclear"></div>
                                    </div>';
			// sho differently if paused
			if ($this->paused) {
			    $j .= 'CreateSliderVerticalPaused("' . $this->results[$i]['idx'] . '", ' . $answer . ');' . "\n";
			} else {
			    $j .= 'CreateSliderVertical("' . $this->results[$i]['idx'] . '", ' . $answer . ');' . "\n";
			}


                        $s .=   '</li>' . "\n";
                     } // end if question exists
                 } // end question loop

                 $s .=  '</ul>
                        <div class="fclear"></div>';

                 // end the container div
                 $s .=  '</div> <!-- container end -->' . "\n";

             //}// end page loop

             // is this a stored paused survey??
             $survey_id = isset($this->member_survey->id) ? $this->member_survey->id : 0;

             // now add the submit
             $s .=  '<div class="fclear"></div>
                        <div class="dpad tcenter">
                            <button type="submit" id="submitsurvey">Save answers and view guidance</button>
                            <input type="hidden" name="skillsurvey" id="skillsurveyswitch" value="0"/>
                            <input type="hidden" name="paused" value="' . $survey_id . '"/>
			    <input type="hidden" name="type" value="' . $type . '">
                        </div>';

             // add the submit click to change the rolesurvey key
             $j .=  '$("#submitsurvey").click(function() {
                        $("#skillsurveyswitch").attr("value", 1);
                        return true;
                    });';

             // end form
             $s .=  '</form>' . "\n";

             if (! empty($j)) {
                 $j =   '<script type="text/javascript">
                            $(document).ready(function() {
                                ' . $j . '
                            });
                        </script>' . "\n";

                 $s .= $j;
             }

             return $s;
	 } // end showVerticalSurveybyCompetency

	 /**
	  * Culls results that don't belong to a particular row
	  */

	 private function cull_roles($role_id = null) {
	     if (is_null($role_id)) {
		 return false;
	     }

	     $tmp = array();

	     foreach ($this->results as $key => $val) {
		 if ($val['role'] == $role_id) {
		     $tmp[] = $val;
		 }
	     }

	     $this->results = $tmp;
	 } // end cull_roles


         /**
          * Stores the survey in a session
          */

         public function storeSession() {
            $_SESSION['skill_coresurvey']['id'] = $this->id;
            $_SESSION['skill_coresurvey']['survey'] = $this->compress($this->data);
            $_SESSION['skill_coresurvey']['answers'] = $this->compress($this->results);
         }

         /**
         * for a given user get any submitted surveys
         */

        public static function fetchMemberSurveys() {
            global $USER, $CFG, $textr;

            $s = '';

            $saved = coresurvey_get_skills_surveys_member();



            //self::ShowDebugHalt($saved);

            $completed = array();
            $paused = array();

            // ok go and add them to the correct array, if they exist!

            if (!empty($saved)) {
                foreach($saved as $key => $val) {
                    $r = new SkillResult();
                    $r->inject($val);

                    if ($r->completed) {
                        $completed[] = $r;
                    } else {
                        $paused[] = $r;
                    }

                    unset($r);
                }
            } // end add them

	    // get the surveys
	    // load the last survey in...
	    $model = coresurvey_get_last_skill();

	    $data = self::deCompress($model->skilldata);

            // display them
            // do the paused ones first
            if (!empty($paused)) {

                $s .=   '<p>' . $textr->get_data(7,count($paused)) . '</p>';

                $s .=   '<ul class="survey_list" id="pausedSkills">';

                foreach($paused as $key => $val) {
                    $s .=   '<li class="dpad box_border">
                                <a href="' . $CFG->wwwroot . '/mod/coresurvey/competency_assessment.php?s=' . $val->id . '&id=' . $_GET['id'] . '&type=' . $val->selected_type . '">
                                    ' . date("H:i", $val->end_date) . ' on ' . date("jS M Y" , $val->end_date) . ' - ' . $data[$val->selected_type]['name'] . '
                                </a>
                            </li>' . "\n";
                }


                $s .=   '</ul>';
            }



            // do the completed
            if (!empty($completed)) {

                $s .=   '<p>' . $textr->get_data(8,count($completed)) . '</p>';
                $s .=   '<ul class="survey_list" id="completedSkills">';

                foreach($completed as $key => $val) {
                    $s .=   '<li class="dpad box_border">
                                <a href="' . $CFG->wwwroot . '/mod/coresurvey/competency_results.php?s=' . $val->id . '&id=' . $_GET['id'] . '&type=' . $val->selected_type . '">
                                    ' . date("H:i", $val->end_date) . ' on ' . date("jS M Y" , $val->end_date) . ' - ' . $data[$val->selected_type]['name'] . '
                                </a>
                            </li>' . "\n";
                }

                $s .=   '</ul>';
            } // end completed
            //self::ShowDebug($completed);
            //self::ShowDebugHalt($paused);

            return array(
		'html'		=> $s,
		'count'		=> count($saved)
	    );

        } // end function

	/**
	 * Static function that grabs all of the assessment types
	 */

	public static function fetchallcompetencytypes() {
	    global $CFG, $textr;
	    $s ='';

	    // load the last survey in...
	    $model = coresurvey_get_last_skill();

        $data = self::deCompress($model->skilldata);
	    if (! is_array($data) OR empty($data)) {
		return "There was a problem with this assessment";
	    }

	    $s .= '<form action="' . $CFG->wwwroot . '/mod/coresurvey/competency_assessment.php" method="GET">
		    <p>
		    <button type="submit">' . $textr->get_data(3) . '</button>
		    <select name="type">' . "\n";

	    foreach ($data as $key => $val) {
		$s .=	'<option value="' . $key . '">' . $val['name'] . ' abilities</option>' . "\n";
	    }


	    // finish the form
	    $s .=  '	</select>
			</p>
			<input type="hidden" name="id" value="' . (isset($_GET['id']) ? $_GET['id'] : '0') . '">
		    </form>' . "\n";



	    return $s;
	} // end fetchallcompetencytypes()

	/**
	 * Tabulate the results
	 */

	public function tabulate($name) {
	    global $CFG, $OUTPUT, $USER, $textr;
	    // start the table
	    $s = '<table class="skills_table">';

	    // header
	    $s .=   '<thead>
			<tr valign="top">
			    <td class="w25 tcenter">
			    	<img src="' . $CFG->wwwroot . '/mod/coresurvey/images/macmillan_logo_small.jpg' . '">
			    </td>
			    <td class="w25 tleft">
					' . $OUTPUT->user_picture($USER, array()) . '
					' . $textr->get_data(15, null, $this->member_survey->end_date) . '
			    </td>
			    <td class="w50 tleft">
				' . $this->matrix['instructions']['learning_opportunities'] . '
			    </td>
		        </tr>
			<tr class="btop bbottom">
			    <th class="tcenter ybox">
				' . $textr->get_data(16) . '
			    </th>
			    <th class="tleft ybox">
				' . $textr->get_data(17) . '
			    </th>
			    <th class="tleft ybox">
				' . $textr->get_data(18) . '
			    </th>
			</tr>
		    </thead>';

	    // body
	    $s .=   '<tbody>';

	    // You have not begun to
	    $dta = $this->fetchData(0);
	    if (empty($dta)) {
	    } else {
		$i = 0;
		foreach ($dta as $val) {
		    $s .= '<tr>
			    <th class="w25 tright ' . ($i == 0 ? 'btop' : 'nobtop') .'">
				' . ($i == 0 ? 'You have not begun to:' : '') . '
			    </th>
			    <td class="w25 btop">
				' . $val['you'] . '
			    </td>
			    <td class="w50 tleft btop">
				' . $val['resources'] . '
			    </td>
			</tr>';
		    $i++;
		}
	    }

	    // Beginning to
	    $dta = $this->fetchData(1);
	    if (empty($dta)) {
	    } else {
		$i = 0;
		foreach ($dta as $val) {
		    $s .= '<tr>
			    <th class="w25 tright ' . ($i == 0 ? 'btop' : 'nobtop') .'">
				' . ($i == 0 ? 'You are beginning to:' : '&nbsp;') . '
			    </th>
			    <td class="w25 btop">
				' . $val['you'] . '
			    </td>
			    <td class="w50 tleft btop">
				' . $val['resources'] . '
			    </td>
			</tr>';
		    $i++;
		}
	    }

	    // Still learning to
	    $dta = $this->fetchData(2);
	    if (empty($dta)) {
	    } else {
		$i = 0;
		foreach ($dta as $val) {
		    $s .= '<tr>
			    <th class="w25 tright ' . ($i == 0 ? 'btop' : 'nobtop') .'">
				' . ($i == 0 ? 'You are still learning to:' : '&nbsp;') . '
			    </th>
			    <td class="w25 btop">
				' . $val['you'] . '
			    </td>
			    <td class="w50 tleft btop">
				' . $val['resources'] . '
			    </td>
			</tr>';
		    $i++;
		}
	    }

	    // competent
	    $dta = $this->fetchData(3);
	    if (empty($dta)) {
	    } else {
		$i = 0;
		foreach ($dta as $val) {
		    $s .= '<tr>
			    <th class="w25 tright ' . ($i == 0 ? 'btop' : 'nobtop') .'">
				' . ($i == 0 ? 'You are competent to:' : '&nbsp;') . '
			    </th>
			    <td class="w25 btop">
				' . $val['you'] . '
			    </td>
			    <td class="w50 tleft ' . ($i == 0 ? 'btop' : 'nobtop') .'">
				' . ($i == 0 ? 'You will become ever more confident through experience.' : '&nbsp;') . '
			    </td>
			</tr>';
			$i++;
		}
	    }

		// confident
	    $dta = $this->fetchData(4);
	    if (empty($dta)) {
	    } else {
		$i = 0;
		foreach ($dta as $val) {
		    // display different for the first row
		    if ($i == 0) {
		    $s .= '<tr>
			    <th class="w25 tright btop">
				    ' . ($i == 0 ? 'You are confident to:' : '&nbsp;') . '
			    </th>
			    <td class="w25 btop">
				    ' . $val['you'] . '
			    </td>
			    <td class="w50 tleft btop">
				    <b>Reading</b><br/>maintain a watching brief on the <a href="http://www.macmillan.org.uk">Macmillan web site</a> for new developments.
			    </td>
			   </tr>';
		    } else {
		    $s .= '<tr>
			    <td class="w25 tright nobtop">
			            &nbsp;
				</td>
				<td class="w25 btop">
				    ' . $val['you'] . '
				</td>
				<td class="w50 tleft nobtop">
				    &nbsp;
				</td>
			    </tr>';
		    }
		    $i++;
		}
	    }

	    // end body
	    $s .=   '</tbody>';



	    // end the table
	    $s .= '</table>';

	    return $s;
	} // end tabulate

	/**
	 * Fetches data for a specific answer
	 */

	private function fetchData($answer) {
	    $ret = array();

	    // refactor the answer...
	    $percent = $this->convert_answer($answer);

	    // ok now we need to get the idx of the answers that match...
	    foreach ($this->member_survey->results as $key => $val) {
		if ($percent == $val['answer']) {

		    $idx = explode('_', $val['idx']);
		    //print_r($idx);
		    $role = $idx[0];
		    $aspect = $idx[1];

		    //print_r($this->data[$role]);
		    $ret[$val['idx']] = array(
			'you'	    => $this->data[$role]['aspects'][$aspect]['title'],
			'resources' => $this->factor_resources(isset($this->data[$role]['aspects'][$aspect]['resources']) ? $this->data[$role]['aspects'][$aspect]['resources'] : '')
		    );
		}
	    }

	    /*echo '<pre>';
	    print_r($ret);
	    //print_r($this->data);
	    exit;
	     */

	    return $ret;
	} // end fetchData

	/**
	 * factors resources
	 */

	public function factor_resources($arr) {
	    $s = '';

	    if (! empty($arr['reading']) && strlen($arr['reading']) > 10) {
		$s .=	'<b>Reading</b>' . $arr['reading'];

	    }

	    if (! empty($arr['elearning']) && strlen($arr['elearning']) > 10) {
		$s .=	'<b>eLearning</b>' . $arr['elearning'];
	    }

	    if (! empty($arr['face_to_face']) && strlen($arr['face_to_face']) > 10) {
		$s .=	'<b>Face to face</b>' . $arr['face_to_face'];
	    }

	    if (! empty($arr['other_sources']) && strlen($arr['other_sources']) > 10) {
		$s .=	'<b>Other Sources</b>' . $arr['other_sources'];
	    }

	    if (! empty($arr['websites']) && strlen($arr['websites']) > 10) {
		$s .=	'<b>Websites</b>' . $arr['websites'];
	    }

	    return $s;
	} // end factor_resources

	/**
	 * converts an answer to a int number as it is stored in the db
	 *
	 */

	private function convert_answer($answer) {
	    $steps = round(100 / (count($this->alignment) - 1));

	    return $answer * $steps;

	} // end convert answer
     } // end class
?>
