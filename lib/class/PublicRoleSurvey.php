<?php
    /**
     * Role Survey class that displays public code
     */

     class PublicRoleSurvey extends Survey {
        protected $type = 'Role';
        public $id; // id of the survey from the database
        public $data = array(); // the survey in array form
        public $results = array(); // the list of questions,
        protected $questions_per_page = 10;
        public $clientresults = array(); // array of results, broken down into role, aspect, answer
        protected $survey_taken = false; // boolean for setting whether the survey has been taken or not...
	protected $graph_settings = array();
	protected $analysis;

	protected $fudge = 20;

	public $new_member_result_id;


        public $member_survey; // a members object

         // the alignments
	/*
        protected $alignment = array(
                                0 => 'I really like to',
                                1 => 'I feel ready to',
                                2 => 'I have no preference to',
                                3 => 'I feel unready to',
                                4 => 'I really don\'t like to'
                              );
	 */

	protected $alignment = array(
		0 => 'I really don\'t like to',
		1 => 'I feel unready to',
		2 => 'I have no preference to',
		3 => 'I feel ready to',
		4 => 'I really like to'
	);
	protected $alignmentAdvice = array(
		0 => 'you really don\'t like to',
		1 => 'you feel unready to',
		2 => 'you have no preference to',
		3 => 'you feel ready to',
		4 => 'you really like to'
	);


         /**
          * Constructor
          */

         function __construct($public = false) {
             // if Public then change the directory to public instead of simulate
             if ($public) {
                 $this->range = 'public';
             }
             // unset($_SESSION['role_coresurvey']);
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
                     //$this->ShowDebugHalt($this->results);
                 } else {
                     // just load in the survey
                     $this->loadSurvey();
                 }
             } else {
		 /*
                 if (! isset($_SESSION['role_coresurvey']) ) {
                     // fetch the survey and populate the fields
                     $this->loadSurvey();
                 } else {
                     // we need to pull survey data in from the sessions
                     $this->loadSurveyfromSession();
                 }
		  */
		 $this->loadSurvey();
             }

             // has the survey been submitted?? and if it has is it a completed survey $_POST['rolesurvey'] == 1
             // or is it a paused survey $_POST['rolesurvey'] == 0
             if (isset($_POST['rolesurvey']) && $_POST['rolesurvey'] == 1) {
                 // we have some results

                 $this->resultsPopulate();
                 $this->survey_taken = true;

                 // we now have the results, save them
                 $this->storeResults();

                 // update the taken field in the survey
                 $this->updateSurveyTaken();
             } // end results

             // has the paused button been used? if so save the survey for future use
             if (isset($_POST['rolesurvey']) && $_POST['rolesurvey'] == 0) {

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

                 // TODO: change this for the live version
                 global $CFG;
                 coresurvey_page_redirect($CFG->wwwroot . '/mod/coresurvey/view.php?id=' . $_GET['id']);
             }

             //coresurvey_debug($_POST);
         } // end constructor

         /**
          * Takes a survey and saves it in the database.
          */

         protected function storeResults() {
            global $USER, $CFG;
            // make sure that the libraries are loaded :-(
            require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Result.php');
            require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleResult.php');



            // check to see if there was a paused survey in effect
            if (isset($_POST['paused']) && $_POST['paused'] > 0) {
                // there is a paused survey in effect
                $this->member_survey->setResults($this->results);
                $this->member_survey->setCompleted($this->survey_taken);
                $this->member_survey->setEndDate();
                $this->member_survey->save();
            } else {
                // no survey paused
                $r = new RoleResult();

                // set the data
                $r->setMemberID($USER->id);
                $r->setSurveyID($this->id);
                $r->setCompleted($this->survey_taken);
                $r->setResults($this->results);
                $r->saveResults();

		$this->new_member_result_id = $r->id;
            }


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
             $_SESSION['role_coresurvey']['answers'] = $this->compress($this->results);

         } // end function

         /**
          * Loads the survey from the session
          */

         protected function loadSurveyfromSession() {
             $this->id = $_SESSION['role_coresurvey']['id'];
             $this->data = $this->deCompress($_SESSION['role_coresurvey']['survey']);
             $this->results = $this->deCompress($_SESSION['role_coresurvey']['answers']);
         } // end function

         /**
          * Loads a survey and populates the fields
          */

         protected function loadSurvey() {
            $model = coresurvey_get_last_role();

            $this->id = $model->id;
            $this->data = $this->deCompress($model->roledata);
	    $this->matrix = $this->deCompress($model->matrix);

            $this->results = $this->createQuesionList();

            // ok lets load the two arrays into the session, this will minimise
            // db load....:-)
            $this->storeSession();

            //coresurvey_debug($_SESSION);
         } // end function

         /**
          * Stores the survey in a session
          */

         public function storeSession() {
            $_SESSION['role_coresurvey']['id'] = $this->id;
            $_SESSION['role_coresurvey']['survey'] = $this->compress($this->data);
            $_SESSION['role_coresurvey']['answers'] = $this->compress($this->results);
         }

         /**
          * Loads a specific survey number
          */

         protected function loadSpecificSurvey($id = 0) {
            $model = coresurvey_get_specific_role($id);

            if ($model) {
                $this->id = $model->id;
                $this->data = $this->deCompress($model->roledata);
		$this->matrix = $this->deCompress($model->matrix);
            }
         } // end function

         /**
          * Shows the survey, html and javascript
          */

         function showSurvey() {
	     global $textr;

             $s = $j = '';

             // work out how many pages we have.
             $questions = count($this->results);
             $pages = ceil($questions / $this->questions_per_page);
             $idx = 0; // initial array pointer
             //coresurvey_debug($this->results);

             // ok create the form
             $s .=  '<form action="" method="POST" id="surveyform">' . "\n";

             // add in the pause button
             $s .=  '<div class="dpad tcenter box_border">
                        ' . $textr->get_data(13) . ' <button type="submit">' . $textr->get_data(14) . '</button>
                     </div>';
	     $s .=  '<fieldset>';
	     // headings
	     $s.=   '<div class="attitudeandactivityandyoumightthink">
			<div class="attitudeandactivitytitle">
			<div class="attitude">
			    <b>' . get_string('coresurvey_title_alignment', 'coresurvey') . '</b>
			</div>
			<div class="activity">
			    <b>' . get_string('coresurvey_title_activities', 'coresurvey') . '</b>
			</div>
			</div>
			<div class="youmightthink">
			    <b>' . get_string('coresurvey_title_statement', 'coresurvey') . '</b>
			</div>
		    </div>
		    <div class="fclear"></div>';
             for($pg = 1; $pg <= $pages; $pg++) {
                // create the fieldset
                 //$s .=  '<fieldset>';
		 // removed this at the moment
		 /*
                            <legend>Questions ' . $pg . ' of ' . $pages . '</legend>' . "\n";
		  */
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

                         // create the bar
                         $s .=  '<div class="attitudeandactivityandyoumightthink">' . "\n";

                         $s .= '<div class="attitudeandactivity">';

			 // create the slider
                         $s .=  '<div class="attitude">
				    <div id="slider_header_' . $this->results[$i]['idx'] . '" class="tright bpad rpad" style="">&nbsp;<span style="display:none;">' . $this->alignment[2] . '</span></div>
                                    <div id="slider_' . $this->results[$i]['idx'] . '" class="w95 ui-slider ui-slider-horizontal ui-widget ui-widget-content ui-corner-all"></div>
                                    <input type="hidden" name="answer_' . $this->results[$i]['idx'] . '" id="answer_' . $this->results[$i]['idx'] . '" value="' . $answer . '"/>
                                </div>' . "\n";

                         $j .= 'CreateSliderTest("' . $this->results[$i]['idx'] . '", ' . $answer . ');' . "\n";

                         // create the question
                         $s .=  '<div class="activity">
                                    ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['title'] . '
                                </div>' . "\n";


                         $s .= '</div><div class="youmightthink"><div class="thought-bubbles"><div class="thought-bubble1"></div> <div class="thought-bubble2"></div></div>';
                         // create the statements, at the moment, these are hardcoded... yuck!!!
                         $s .=  '<div class="thought">
                                    <div id="statement_container_' . $this->results[$i]['idx'] . '">
                                        <div id="statement_' . $this->results[$i]['idx'] . '_100" class="align_statement">
                                            "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][0] . '"
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_75" class="align_statement">
                                            "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][1] . '"
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_50" class="align_statement">
                                            "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][2] . '"
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_25" class="align_statement">
                                            "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][3] . '"
                                        </div>
                                        <div id="statement_' . $this->results[$i]['idx'] . '_0" class="align_statement">
                                            "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][4] . '"
                                        </div>
                                    </div>
                                </div>';

                         $s .= '</div>';

                         // end the dl
                         $s .=  '</div>
                                <div class="fclear"></div>' . "\n";
                     } // end if it actually exists
                 } // end loop
                 // end the fieldset
                 //$s .=  '</fieldset>' . "\n";
             }// end page loop
	     $s .=  '</fieldset>' . "\n";

             // is this a stored paused survey??
             $survey_id = isset($this->member_survey->id) ? $this->member_survey->id : 0;

             // now add the submit
             $s .=  '<fieldset>
                        <div class="dpad tcenter">
                        <legend>Complete review</legend>
                            <button type="submit" id="submitsurvey">Save answers and view advice</button>
                            <input type="hidden" name="rolesurvey" id="rolesurveyswitch" value="0"/>
                            <input type="hidden" name="paused" value="' . $survey_id . '"/>
                        </div>
                    </fieldset>';

             // add the submit click to change the rolesurvey key
             $j .=  '$("#submitsurvey").click(function() {
                        $("#rolesurveyswitch").attr("value", 1);
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
         * for a given user get any submitted surveys
         */

        public static function fetchMemberSurveys() {
            global $USER, $CFG, $textr;

            $s = '';

            $saved = coresurvey_get_role_surveys_member();

            //self::ShowDebugHalt($saved);

            $completed = array();
            $paused = array();

            // ok go and add them to the correct array, if they exist!

            if (!empty($saved)) {
                foreach($saved as $key => $val) {
                    $r = new RoleResult();
                    $r->inject($val);

                    if ($r->completed) {
                        $completed[] = $r;
                    } else {
                        $paused[] = $r;
                    }

                    unset($r);
                }
            } // end add them

            // display them
            // do the paused ones first
            if (!empty($paused)) {
                $s .=   '<p>' . $textr->get_data(5,count($paused)) . '</p>';

                $s .=   '<ul class="survey_list" id="pausedRoles">';

                foreach($paused as $key => $val) {
                    $s .=   '<li class="dpad box_border">
                                <a href="' . $CFG->wwwroot . '/mod/coresurvey/roles.php?s=' . $val->id . '&id=' . $_GET['id'] . '">
                                    ' . date("H:i", $val->end_date) . ' on ' . date("jS M Y" , $val->end_date) . '
                                </a>
                            </li>' . "\n";
                }


                $s .=   '</ul>';
            }

            // do the completed
            if (!empty($completed)) {
                $s .=   '<p>' . $textr->get_data(6,count($completed)) . '</p>';
                $s .=   '<ul class="survey_list" id="completedRoles">';

                foreach($completed as $key => $val) {
                    $s .=   '<li class="dpad box_border">
                                <a href="' . $CFG->wwwroot . '/mod/coresurvey/role_results.php?s=' . $val->id . '&id=' . $_GET['id'] . '">
                                    ' . date("H:i", $val->end_date) . ' on ' . date("jS M Y", $val->end_date) . '
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
	 * Returns the javascript data for Raphael
	 */

	public function raphael() {
	    $width	= 900;
	    $height	= 500;
	    $center_x	= round($width / 2);
	    $center_y	= round($height / 2);
	    $circle_radius = 180;
	    $num_results = $this->count_results();
	    $fudge = 20;

	    $tooltiprx = 650;
	    $tooltipry = 250;

	    $tooltiplx = 100;
	    $tooltiply = 250;

	    $scale = 1.5;

	    /*echo '<pre>';
	    print_r($this->data);
	    exit;*/


	    $arc_inc = 360 / $num_results;

	    $current_degree = 90;

	    $js = array();

	    // create the paper
	    $js[]   = 'var paper = Raphael("rose_chart", ' . $width . ', ' . $height . ');';

	    // create the background circles
	    $js[]   = 'paper.circle(' . $center_x . ',' . $center_y . ',' . $circle_radius . ').attr({fill: "#f6f6f6", stroke: "#ededed"});';

	    // we need to create the alignment circles
	    $num_aligns = count($this->alignment);
	    $aligns = $this->alignment;

	    // remove the first and the last
	    //unset($aligns[0]);
	    //unset($aligns[($num_aligns - 1)]);
	    $js[] = 'paper.circle(' . $center_x . ',' . $center_y . ',' . (.2 * $circle_radius) . ').attr({"stroke": "#ddd", "stroke-width": 3});';
	    $js[] = 'paper.circle(' . $center_x . ',' . $center_y . ',' . (.4 * $circle_radius) . ').attr({"stroke": "#ddd", "stroke-width": 3});';
	    $js[] = 'paper.circle(' . $center_x . ',' . $center_y . ',' . (.6 * $circle_radius) . ').attr({"stroke": "#ddd", "stroke-width": 3});';
	    $js[] = 'paper.circle(' . $center_x . ',' . $center_y . ',' . (.8 * $circle_radius) . ').attr({"stroke": "#ddd", "stroke-width": 3});';
	    $js[] = 'paper.circle(' . $center_x . ',' . $center_y . ',' . $circle_radius . ').attr({"stroke": "#fff", "stroke-width": 3});';

	    $align_percent = 20;

	    $align_percent_counter = 0;

	    foreach ($aligns as $key => $val) {
		$js[] = 'paper.circle(' . $center_x . ',' . $center_y . ',' . (($align_percent_counter / 100) * $circle_radius) . ').attr({"stroke": "#fff", "stroke-width": 3});';

		$align_percent_counter = $align_percent_counter + $align_percent;

	    }

	    // do some line testing
	    //$js[]   = 'paper.path("' . $this->sector($center_x, $center_y, 50, 0, 90) . '");';
	    //$js[]   = 'paper.path("' . $this->sector($center_x, $center_y, 50, 90, 135) . '");';

	    // create the lines
	    $start_degree = $current_degree;
	    $rad = pi() / 180;

	    for($i = 1; $i <= $num_results; $i++) {


		$endx = $center_x + ($circle_radius * cos(-$start_degree * $rad));
		$endy = $center_y + ($circle_radius * sin(-$start_degree * $rad));

		$start_degree = $start_degree + $arc_inc;

		$js[]	= 'paper.path("M' . $center_x . ' ' . $center_y . ' L' . $endx . ' ' . $endy . '").attr({"stroke": "#fff", "stroke-width": 3});';
	    }


	    // create the Roles labels
	    $start_degree = $current_degree;
	    $roles_arc = 360 / count($this->data);
	    $roles_radius = $circle_radius + 50;

	    /*echo '<pre>';
	    print_r($this);
	    exit;*/

	    $start_degree = $current_degree;
	    // ok cycle through all of the results
	    foreach($this->data as $rkey => $rval) {
		// cycle through the results
		foreach($rval['aspects'] as $akey => $aval) {
		    // radius
		    //echo $this->fetchClientAnswer($rkey, $akey) . '<br/>';
		    //$radius = ($this->fetchClientAnswer($rkey, $akey) + $fudge);

		    // add in the fudge % to scale display results
		    //$invert = (100 - ($this->fetchClientAnswerReMap($rkey, $akey))) + 20;
		    $invert = $this->fetchClientAnswerReMap($rkey, $akey) ;

		    $radius = ($invert / 100) * $circle_radius;

		    $end_degree = $start_degree + $arc_inc;

		    // tooltip position
		    if ($start_degree >= 90 && $start_degree <= 270) {
			$toolx = $tooltiprx;
			$tooly = $tooltipry;
		    } else {
			$toolx = $tooltiplx;
			$tooly = $tooltiply;
		    }

		    // do the path
		    $js[]   = 'var s = paper.path("' . $this->sector($center_x, $center_y, $radius, $start_degree, $end_degree) . '").attr({fill: "' . $rval['color'] . '", stroke: "#fff", "stroke-width": 3});';

		    // create the textbox
		    $js[]   =	'var l = (s.getTotalLength() / 2);';
		    $js[]   =	'var point = s.getPointAtLength(l);';

		    // create the text for the text box
		    $def_align_number = $this->fetchClientAlignmentNumber($this->fetchClientAnswer($rkey, $akey));
		    // invert it
		    switch($def_align_number) {
			case 0:
			    $align_number = 4;
			    break;
			case 1:
			    $align_number = 3;
			    break;
			case 2:
			    $align_number = 2;
			case 3:
			    $align_number = 1;
			    break;
			case 4:
			    $align_number = 0;
		    }

		    $text = 'Your selection:\n\n' . $this->create_text_box('"' . $aval['alignment'][$align_number] . '"') . '\n\n' . $this->create_text_box('suggests ' . $this->alignmentAdvice[$def_align_number]) . '\n' . $this->create_text_box($aval['title']);
		    /*echo '<pre>';
		    print_r($aval);
		    exit;*/
		    //$js[]   =	'var t_' . $rkey . '_' . $akey . ' = paper.text(point.x + 120, point.y + 20, "' .  $this->create_text_box($rval['name'] . ' ' . $aval['title']) . '\n' . $this->create_text_box($aval['alignment'][$this->fetchClientAlignmentNumber($this->fetchClientAnswer($rkey, $akey))]) . '").hide();';
		    $js[]   =	'var t_' . $rkey . '_' . $akey . ' = paper.text(' . $tooltiprx . ', ' . $tooltipry . ', "' . $text . '").attr({"font-size": 14, "text-align": "left", "text-anchor": "start"}).hide();';
		    $js[]   =	'var t_' . $rkey . '_' . $akey . '_box = paper.rect(t_' . $rkey . '_' . $akey . '.getBBox().x - 5, t_' . $rkey . '_' . $akey . '.getBBox().y -5, t_' . $rkey . '_' . $akey . '.getBBox().width + 10, t_' . $rkey . '_' . $akey . '.getBBox().height + 10, 5).attr({fill: "#fff"}).hide();';
		    // get the bounding box

		    // create the set

		    $js[]   =	'var st_' . $rkey . '_' . $akey . ' = paper.set();';
		    $js[]   =	'st_' . $rkey . '_' . $akey . '.push(
				    t_' . $rkey . '_' . $akey . '_box,
				    t_' . $rkey . '_' . $akey . '
				);';


		    //$js[]   =	'$(s.node).qtip({content: "gidday"});';

		    // add the svg tooltip
		    //$js[]   =	's.tooltip(t_' . $rkey . '_' . $akey . ');';


		    // do the mouse over event using jquery
		    /*$js[]   =	'$(s.node).hover(function(event) {
				   $(this).attr({opacity: 0.25}).css("cursor", "pointer");
				   st_' . $rkey . '_' . $akey . '.toFront().show();

				}, function(event) {
				   $(this).attr({opacity: 1});
				   st_' . $rkey . '_' . $akey . '.hide();
				});';*/

		    // use raph hover
		    $js[]   = 's.hover(function (event) {
				    this.attr({opacity: 0.25, "cursor": "pointer"});
				    st_' . $rkey . '_' . $akey . '.toFront().show();
				}, function (event) {
				    this.attr({opacity: 1});
				    st_' . $rkey . '_' . $akey . '.hide();
				});';


		    // increment the start degree
		    $start_degree = $end_degree;
		}
	    }

	    // finally add a center circle
	    $js[]   = 'paper.circle(' . $center_x . ', ' . $center_y . ', 30).attr({fill: "#fff", stroke: "#fff"});';

	    // now do the advice offered
	    $tag = $this->analysis['tag'] . '_offered';

	    /*echo '<pre>';
	    print_r($this->analysis);
	    print_r($this->matrix);
	    exit;*/

	    if (isset($this->matrix['tags'][$tag])) {
		$ao = $this->create_text_box($this->insert_role_name($this->matrix['tags'][$tag]));

		$js[]	= 'var ao = paper.text(' . round(.05 * $width) . ', ' . round(.15 * $height) . ',"' . $ao . '").attr({"font-size": 14, "text-align": "left", "text-anchor": "start"});';
		// do bounding box
		$js[]   =   'var ao_box = paper.rect(ao.getBBox().x - 5, ao.getBBox().y -5, ao.getBBox().width + 10, ao.getBBox().height + 10, 5).attr({fill: "#F6F6F6", stroke: "#ededed"});';
		$js[]   =   'ao_box.insertBefore(ao);';
	    }

	    // now do the further advice
	    $tag = $this->analysis['tag'] . '_further';

	    if (isset($this->matrix['tags'][$tag])) {
		$af = $this->create_text_box($this->insert_role_name($this->matrix['tags'][$tag]));

		$js[]	= 'var af = paper.text(' . round(.70 * $width) . ', ' . round(.80 * $height) . ',"' . $af . '").attr({"font-size": 14, "text-align": "left", "text-anchor": "start"});';
		// do bounding box
		$js[]   =   'var af_box = paper.rect(af.getBBox().x - 5, af.getBBox().y -5, af.getBBox().width + 10, af.getBBox().height + 10, 5).attr({fill: "#F6F6F6", stroke: "#ededed"});';
		$js[]   =   'af_box.insertBefore(af);';
	    }

	    // roles labels
	    $start_degree = $current_degree;

	    foreach($this->data as $rkey => $rval) {
		$title_degree = $start_degree + ($roles_arc / 2);
		$endx = $center_x + ($roles_radius * cos(-$title_degree * $rad)) - 30;
		$endy = $center_y + ($roles_radius * sin(-$title_degree * $rad));

		$js[]	= 'paper.text(' . $endx . ', ' . $endy . ',"' . $rval['name'] . '").attr({"font-size": 16, "font-weight": "bold", "text-anchor": "start"}).toFront();';

		$start_degree = $start_degree + $roles_arc;
	    }


	    return implode(' ', $js);
	} // end raphael

	/**
	 * Inserts a role name
	 */

	public function insert_role_name($text) {
	    // make sure it is not empty
	    if (empty($this->analysis['match'])) {
		return $text;
	    }

	    $n = 1;

	    foreach($this->analysis['match'] as $value) {
		$role_name = $this->data[$value]['name'];
		$text = str_replace('%' . $n, $role_name, $text);
		$n++;
	    }

	    return $text;
	} // end insert_role_name


	/**
	 * Create a nice text box
	 */

	private function create_text_box($text) {
	    //$text = $this->sanitise($text);

	    $text = html_entity_decode($text, ENT_QUOTES);

	    // now split it
	    return wordwrap($text, 35, '\n');

	} // end create text box

	/**
	 * Create a raphael paht for a sector
	 */

	private function sector($cx, $cy, $r, $startAngle, $endAngle, $fill = null) {
	    $rad = pi() / 180;

	    $x1 = $cx + ($r * cos(-$startAngle * $rad));
	    $x2 = $cx + ($r * cos(-$endAngle * $rad));
	    $y1 = $cy + ($r * sin(-$startAngle * $rad));
            $y2 = $cy + ($r * sin(-$endAngle * $rad));

	    //return 'M' . $cx . ' ' . $cy . ' L' . $x1 . ' ' . $y1 . ' A,' . $r . ' ' . $r . ' 0 ' . abs($endAngle - $startAngle) . ' 1 ' . $x2 . ' ' . $y2 . ' z';

	    return 'M' . $cx . ' ' . $cy . ' L' . $x1 . ' ' . $y1 . ' L' . $x2 . ' ' . $y2 . ' z';
	}

	/**
	 * Rotate x
	 */

	public function rotate_x($x, $y, $degree) {
	    $degree = deg2rad($degree);

	    return (($x * cos($degree)) - ($y * sin($degree)));
	}

	/**
	 * Rotate Y
	 */

	public function rotate_y($x, $y, $degree) {
	    $degree = deg2rad($degree);

	    return (($x * sin($degree)) - ($y * cos($degree)));
	}

	// return count of the number of results

	private function count_results() {
	    $count = 0;

	    foreach($this->data as $roles) {
		$count = $count + count($roles['aspects']);
	    }

	    return $count;
	} // end count_results

     } // end class
?>
