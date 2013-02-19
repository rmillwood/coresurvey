<?php
/*
 * Base Survey class, Roles and Skills extend this....
 */

class Survey extends DBhandler {
    public $range = 'simulate';

    public $matrix = array();
    /**
     * creates a select for the aspect weightings
     * takes the select name as the argument
     */

    function CreateWeightingSelect($str, $val = -1) {
        $s = '<select name="' . $str . '">' . "\n";

        // loop through
        for ($i=0; $i <= 100; $i++) {
            // if this one exists and is equal to this $i, we have a match
            if (isset($_POST[$str]) && $_POST[$str] == $i) {
                // this is selected
                $s .=   '<option value="' . $i . '" selected="selected">' . $i . '</option>' . "\n";
            } else {
                // just display as normal but check for the val
                if ($i == $val) {
                    $s .=   '<option value="' . $i . '" selected="selected">' . $i . '</option>' . "\n";
                } else {
                    $s .=   '<option value="' . $i . '">' . $i . '</option>' . "\n";
                }
            }
        } // end loop

        $s .=   '</select>' . "\n";

        return $s;
    }  // end function

    /**
     * completely sanitises a variable for xml output
     */

    function sanitise($str) {
        $url = str_replace("'", '', $str);
        $url = str_replace('%20', ' ', $url);
        $url = preg_replace('~[^\\pL0-9_]+~u', ' ', $url); // substitutes anything but letters, numbers and '_' with separator
        $url = trim($url, "-");
        $url = iconv("utf-8", "us-ascii//TRANSLIT", $url); // you may opt for your own custom character map for encoding.
        //$url = strtolower($url);
        $url = preg_replace('~[^-a-zA-Z0-9_]+~', ' ', $url); // keep only letters, numbers, '_' and separator
        return $url;
    } // end function

    /**
     * Creates the recommended role
     */

    function get_primary_roles() {
	// create a temporary array structure
        $arr = array();
	$tot_results = 0;
	$final = array(
	    'match' => array()
	);

        // loop through all of the roles and color

        foreach ($this->data AS $k => $v) {
            $arr[$k]['title']	    = $v['name'];
	    $arr[$k]['key']	    = $k;
            $arr[$k]['value']	    = 0;
	    $arr[$k]['total']	    = 0;
        } // ok finished setting up the array

	// loop through all of the results
        foreach ($this->results AS $rkey => $rval) {
            // ok for each result we need to get the weigting
            $w = $this->data[$rval['role']]['aspects'][$rval['aspect']]['weightings'];

            // loop through each weighting
            foreach ($w AS $wkey => $wval) {
                // now add to the temporary results array, but only if the weighting is greater
                // than zero!!!
                // adds the weigthing plus the answer....
                if ($wval > 0) {
                    //$arr[$wkey]['value'] = $arr[$wkey]['value'] + $wval + $rval['answer'];
                    $arr[$wkey]['value'] = $arr[$wkey]['value'] + (($wval / 100) * $rval['answer']);
                }
		$arr[$wkey]['total'] = $arr[$wkey]['total'] + $wval;

            }
        } // end results loop

	// ok now we have the results, sort the array based on the value, so that the highest
        // role appears first

        $tmp = array();

        foreach($arr AS $k => $v) {
            // does the key already exist
            if (!isset($tmp[$v['value']])) {
                // it does not exist and can be added
                $tmp[$v['value']] = $v;

            } else {
                // we need to create a new key
                $key = $v['value'];
                $done = false;
                // keep trying until the key is ok
                do {
                    $key++;
                    if (! isset($tmp[$key])) {
                        // ok sorted, add the value
                        $done = true;
                        $tmp[$key] = $v;
                    }
                } while (! $done);
            }
            $tot_results += $v['value'];
        }


	$top = 0;

        // now find out the total results and add as a percentage
        foreach ($tmp as $key => $val) {
	    // do the percentage as a total vs the other roles
            //$tmp[$key]['percentage'] = round(($val['value'] / $tot_results) * 100, 2);

	    // do the percentage using only yhis role
	    //$tmp[$key]['percentage']	= round(($val['value'] / $val['total']) * 100, 2);
	    $percent	= round(($val['value'] / $val['total']) * 100, 2);
	    $tmp[$key]['percentage'] = $percent;

	    // is the percentage over the match threshhold??
	    if ($percent >= $this->get_match_thresh_hold()) {
		$final['match'][] = $val['key'];
	    }

	    if ($percent > $top) {
		$top = $percent;
	    }
        }

	// we now have the top percent, are the results separated??
	$sep_mark = $top - $this->get_separate_thresh_hold();

	$final['separated'] = 0;

	foreach($tmp as $key => $val) {
	    if ($val['percentage'] < $sep_mark) {
		$final['separated'] = 1;
	    }
	}



        // now sort it in descending order
        $arr = $tmp;
        krsort($arr);

	$final['results'] = $arr;

	// set the tag that will match the results....
	$final['tag'] = 'M' . count($final['match']) . '_' . $final['separated'];

	$this->analysis = $final;

	/*
	echo '<pre>';
	print_r($this->analysis);
	exit;*/


	// ok now we loop throught the results and find out the ones that match

    } // end get_primary_roles

    /**
     * creates a summary of the analysis for embedding inside the page
     */

    public function analysis_summary() {
	$s = '';
	$s = '<!-- Survey Data Summary' ."\n";
	//$s .= '<pre>';

	// do the match
	if (empty($this->analysis['match'])) {
	    $s .=   'None of the roles match' ."\n";
	} else {
	    $s .= 'Roles that have scored higher than the match percentage: ' . $this->get_match_thresh_hold() . '%' . "\n";
	    foreach ($this->analysis['match'] as $match) {
		$s .= $this->data[$match]['name'] . "\n";
	    }
	}

	// separated
	$s .= 'Is there a Separation? ' . ($this->analysis['separated'] == 1 ? 'Yes' : 'No') . "\n";

	// tag
	$s .=	'Tag used: ' . $this->analysis['tag'] . "\n";

	// vars
	$s .= 'Match Target percentage is: ' . $this->get_match_thresh_hold() . "\n";
	$s .= 'Separation percentage is: ' . $this->get_separate_thresh_hold() . "\n";

	// now the roles
	foreach($this->analysis['results'] as $key => $data) {
	    $s .= 'Role: ' . $data['title'] . ' scored ' . $data['value'] . ' out of ' . $data['total'] . '. Percentage: ' . $data['percentage'] . '%' . "\n";
	}

	$s  .=	'End of summary -->' . "\n";
	//$s .= '</pre>';

	return $s;
    } // end analysis_summary()

    /**
     * creates the xml required to create the recommended role xml pie
     * chart
     */

    function fetchRecommendedRole() {
        $xml = '';
        $s = '';

        $tot_results = 0;

        // create a temporary array structure
        $arr = array();

        // loop through all of the roles and color
        foreach ($this->data AS $k => $v) {
            $arr[$k]['title'] = $v['name'];
            $arr[$k]['color'] = $v['color'];
            $arr[$k]['value'] = 0;
        } // ok finished setting up the array

        //$this->ShowDebugHalt($this->data);


        // loop through all of the results
        foreach ($this->results AS $rkey => $rval) {
            // ok for each result we need to get the weigting
            $w = $this->data[$rval['role']]['aspects'][$rval['aspect']]['weightings'];

            // loop through each weighting
            foreach ($w AS $wkey => $wval) {
                // now add to the temporary results array, but only if the weighting is greater
                // than zero!!!
                // adds the weigthing plus the answer....
                if ($wval > 0) {
                    $arr[$wkey]['value'] = $arr[$wkey]['value'] + $wval + $rval['answer'];

                }

            }
        } // end results loop

        // ok now we have the results, sort the array based on the value, so that the highest
        // role appears first

        $tmp = array();

        foreach($arr AS $k => $v) {
            // does the key already exist
            if (!isset($tmp[$v['value']])) {
                // it does not exist and can be added
                $tmp[$v['value']] = $v;
            } else {
                // we need to create a new key
                $key = $v['value'];
                $done = false;
                // keep trying until the key is ok
                do {
                    $key++;
                    if (! isset($tmp[$key])) {
                        // ok sorted, add the value
                        $done = true;
                        $tmp[$key] = $v;
                    }
                } while (! $done);
            }
            $tot_results += $v['value'];
        }




        // now find out the total results and add as a percentage
        foreach ($tmp as $key => $val) {
            $tmp[$key]['percentage'] = round(($val['value'] / $tot_results) * 100, 2);
            $tmp[$key]['pullout'] = 0;
        }

        // now sort it in descending order
        $arr = $tmp;
        krsort($arr);

        $arr_keys = array();
        foreach($arr as $key => $val) {
            $arr_keys[] = $key;
        }

        //$this->ShowDebugHalt($arr);

        // do the pullouts
        // is there more than two?? There should never be only two
        if (count($arr) <= 2) {
            $arr[$arr_keys[0]]['pullout'] = 1;
        } else {
            // compare the two
            if (($arr[$arr_keys[0]]['percentage'] - $arr[$arr_keys[1]]['percentage']) < 5) {
                // not enough of a difference
                $s .= '<h2 class="tcenter">There was not enough of a statistical difference to recommend a Role for you</h2>';
            } else {
                $arr[$arr_keys[0]]['pullout'] = 1;
            }
        }

        // ok now gernarate the xml
        $xml =  "<?xml version='1.0' encoding='UTF-8'?><pie>";

        // the first role should now be the highest, so make sure that it is
        // pulled out

        $n = 1;

        foreach ($arr AS $k => $v) {
            /*if ($n == 1) {
                $xml .= "<slice title='Recommended: " . $this->sanitise($v['title']) . "' color='" . $v['color'] . "' pull_out='true'>" . $v['value'] . "</slice>";
                $n++;
            } else {
                $xml .= "<slice title='" . $this->sanitise($v['title']) . "' color='" . $v['color'] . "'>" . $v['value'] . "</slice>";
            }*/

            if ($v['pullout'] == 1) {
                $xml .= "<slice title='Recommended: " . $this->sanitise($v['title']) . "' color='" . $v['color'] . "' pull_out='true'>" . $v['value'] . "</slice>";
                $n++;
            } else {
                $xml .= "<slice title='" . $this->sanitise($v['title']) . "' color='" . $v['color'] . "'>" . $v['value'] . "</slice>";
            }

        }


        // end the xml
        $xml .= "</pie>";

        //coresurvey_debug($arr);
        //coresurvey_debug($this->results);
        //exit;

        //coresurvey_debug($xml);


        $s .=   '<script type="text/javascript">
                    $(document).ready(function() {
                                // <![CDATA[
                                var so = new SWFObject("/mod/coresurvey/lib/chart/ampie.swf", "rolesurveypie", "900", "600", "8", "#FFFFFF");
                                so.addVariable("settings_file", encodeURIComponent("/mod/coresurvey/lib/chart/role_pie_settings.xml"));
                                so.addVariable("chart_data", "' .$xml . '");
                                so.write("flashcontenttwo");
                                // ]]>
                     });
                </script>';

        return $s;
    } // end function


    /**
     * returns the number of times taken
     */

    function reportTaken() {

        return $this->taken;
    }

    /**
     * Pulling in functions from the extended classes and making them support dual
     * extended classes in order to make them more maintainable
     */

    /**
      * Updates the survey with number of times it's been taken.
      */

     protected function updateSurveyTaken() {
         if ($this->type == 'Skill') {
            coresurvey_update_skill_taken_count($this->id);
         } else {
            coresurvey_update_role_taken_count($this->id);
         }
     }

     /**
      * Public method for determineing whether survey has been taken
      * if so we will do different things based on whether this is a live
      * survey, or just a simulation
      */

     public function surveyTaken() {
         return $this->survey_taken;
     } // end function

     /**
      * Kill session
      */

     protected function killSession() {
         if ($this->type == 'Skill') {
             $_SESSION['skill_coresurvey'] = array();
             unset($_SESSION['skill_coresurvey']);
         } else {
             $_SESSION['role_coresurvey'] = array();
             unset($_SESSION['role_coresurvey']);
         }
     }

      /**
      * loads a members survey, checks for validity
      */

     protected function loadMemberSurvey($id = 0) {
         global $USER, $CFG;

         // quick hack to stop the db query being loaded twice...
         if (is_object($this->member_survey) && $this->member_survey->id = $id) {
             return true;
         }

         // make sure that the libraries are loaded :-(
        require_once($CFG->dirroot . '/mod/coresurvey/lib/class/Result.php');

        if ($this->type == 'Skill') {
            require_once($CFG->dirroot . '/mod/coresurvey/lib/class/SkillResult.php');
            $r = new SkillResult();
        } else {
            require_once($CFG->dirroot . '/mod/coresurvey/lib/class/RoleResult.php');
            $r = new RoleResult();
        }

         $r->load(intval($id));

         // lets do a very rough security check, if the id from the survey matches the current m_id
         // we can add it

         // TODO: change to account for admins!!!!
         if ($USER->id == $r->m_id) {
             $this->member_survey = $r;
             $this->results = $this->member_survey->results;
             $this->clientresults = $this->member_survey->results;
             //$this->ShowDebug($this->member_survey);
             return true;
         }

         return false;

     } // end function

      /**
      * saves the results from a previously published survey
      */

        protected function saveResults() {
         global $USER, $CFG;

         // add the result data
         $this->member_survey->setResults($this->results);

         $this->member_survey->save();
         //$this->ShowDebugHalt($this->member_survey);
     } // end function

     /**
      * Takes the survey, and creates a list of questions, and the answer
      * randomises the list each time it's generated
      */

     protected function createQuestionList() {

         $master = array();

         // loop through all of the roles
         foreach($this->data as $key => $role) {
             // loop through all of the aspects
             foreach($role['aspects'] AS $akey => $aval) {
                  $tmp = array(
                                'role' => $key,
                                'aspect' => $akey,
                                'idx' => $key . '_' . $akey,
                                'answer' => -1
                                );
                  $master[] = $tmp;
                  unset($tmp);
             }
         } // end role loop

         // ok we now have an array, lets do a very rough randomise....
         shuffle($master);

         return $master;
     } // end function

     /**
      * creates the code necessary to do the alignments as a javaarray
      */

     function createJavaAlignment() {
         $s =   '<script type="text/javascript">
                    var myalign=new Array();
                    myalign[0] = "' . $this->alignment[0] . '";
                    myalign[25] = "' . $this->alignment[1] . '";
                    myalign[50] = "' . $this->alignment[2] . '";
                    myalign[75] = "' . $this->alignment[3] . '";
                    myalign[100] = "' . $this->alignment[4] . '";
                 </script>';

         return $s;
     } // end function

     /**
      * creates and returns a set of xml results for the survey, uses
                    * different data sets for the roles, this one doubles the number of entries
      */

     function xmlResults() {
         $s = $d = '';

                    // this is a variable to fudge the results, Ie make sure that there is no
                    // zero result
                    $fudge = 50;

         // first of all we need to count the number of answers, this will
         // be the number of axes + the data
         $num = count($this->results);
                    $dbl = $num * 2;

                    // create the base array.......
                    $sets = array();
                    foreach ($this->data AS $key => $val) {
                            // now iterate through the loop
                            for($i=0; $i < $dbl; $i++) {
                                    $sets[$key][$i] = 0;
                            }
                    }

         // start the xml and chart
         $s .= "<?xml version='1.0' encoding='UTF-8'?><chart>";

         // start the axes
         $s .= '<axes>';

         $i = 0;
         // loop through the roles and then the aspects
         foreach ($this->data AS $rkey => $rval) {
             // loop through the aspects
             foreach ($rval['aspects'] AS $akey => $aval) {
                 $s .= "<axis xid='" . $i . "' fill_color='" . $rval['color'] ."' fill_alpha='25'>" . $this->sanitise($aval['title']) . "</axis>";
                                    // add to the data
                                    $sets[$rkey][$i] = ($this->fetchClientAnswer($rkey, $akey) + $fudge);
                 $i++;
                                    // do twice
                                     $s .= "<axis xid='" . $i . "' fill_color='" . $rval['color'] ."' fill_alpha='25'>" . $this->sanitise($aval['title']) . "</axis>";
                                    // add to the data
                                    $sets[$rkey][$i] = ($this->fetchClientAnswer($rkey, $akey) + $fudge);
                 $i++;


             } // end aspect loop


         } // end role loop



         // end the axe
         $s .=  '</axes>';

         // start the graph
         $s .= "<graphs>";

                    $k = 1;
                    // loop through all of the roles
                    foreach($sets AS $key => $val) {

                            // start the graph
                            $s .=	"<graph gid='" . $k . "' color='" . $this->data[$key]['color'] . "' fill_color='" . $this->data[$key]['color'] . "' fill_alpha='90' title='" . $this->data[$key]['name'] . "'>";

                            // create the datasets
                            for($i=0; $i < $dbl; $i++) {
                                    $s .=	"<value xid='" . $i . "' bullet='round_outlined' bullet_size='6'>" . $val[$i] . "</value>";
                            } // end dataset loop


                            // end the graph
                            $s .=	"</graph>";
                            // increment the key
                            $k++;
                    } // end loop

         // end the graph
         $s .=  "</graphs>";



         // end the xml and chart
         $s .= '</chart>';
         //coresurvey_debug($this->data);
         //coresurvey_debug($s);
         //exit;

         return $s;
     } // end function

     /**
      * Returns a clients answer, based on a given role and aspect key
      */

     protected function fetchClientAnswer($role, $aspect) {
         $combined = $role . '_' . $aspect;

         // roll through the results
         foreach($this->results AS $key => $val) {
              if ($val['idx'] == $combined) {
                  return $val['answer'];
              }
         } // end loop

         // hack if it's not found, this should never happen!
         return 0;
     } // end function

     /**
      * Returns a clients answer but remapped for display on the rose chart, based on a given role and aspect key
      */

     protected function fetchClientAnswerReMap($role, $aspect) {
         $combined = $role . '_' . $aspect;

	 $answer = 0;
	 $remap = 0;

         // roll through the results
         foreach($this->results AS $key => $val) {
              if ($val['idx'] == $combined) {
                  $answer = $val['answer'];
              }
         } // end loop

	 switch ($answer) {
	     case 0:
		 return 20;
	     case 25:
		 return 40;
	      case 50:
		  return 60;
	    case 75:
		return 80;
	    case 100:
		return 100;

	 }

         // hack if it's not found, this should never happen!
         return $remap;;
     } // end function

     /**
      * Fetches client alignment statement based on the answer
      */

     protected function fetchClientAlignment($i) {
        $i = intval($i);
         switch($i) {
             case 0:
                 return $this->alignment[0];
             case 25:
                 return $this->alignment[1];
             case 50:
                 return $this->alignment[2];
             case 75:
                 return $this->alignment[3];
             case 100:
                 return $this->alignment[4];
         }

     } // end function

     /**
      * Fetches client alignment based on the number and returns the array position
      */
     protected function fetchClientAlignmentNumber($i) {
        $i = intval($i);
         switch($i) {
             case 0:
                 return 0;
             case 25:
                 return 1;
             case 50:
                 return 2;
             case 75:
                 return 3;
             case 100:
                 return 4;
         }

     } // end function


        /**
        * Displays the survey in one long list with active sliders
        */

        function showCompletedSurvey() {
                $s = $j = '';

                $idx = 0;

                /*echo "<pre>";
                print_r($this->results);
                print_r($this->data);
                exit;
                */

		// headings
	     $s.=   '<dl class="tcenter">
			<dd class="w30 fleft dpad rpad">
			    <b>' . get_string('coresurvey_title_alignment', 'coresurvey') . '</b>
			</dd>
			<dd class="w30 fleft dpad rpad">
			    <b>' . get_string('coresurvey_title_activities', 'coresurvey') . '</b>
			</dd>
			<dd class="w35 fright dpad">
			    <b>' . get_string('coresurvey_title_statement', 'coresurvey') . '</b>
			</dd>
		    </dl>
		    <div class="fclear"></div>';
			// loop through the questions....
             for ($i = 0; $i <= count($this->results); $i++) {
                 // make sure that this question actually exists
                 if (isset($this->results[$i])) {
                     //$s .= $this->results[$i]['idx'] . '<br/>';

                     // fetch the answer, this will be -1 if it's a previous
                     // survey, otherwise it will be the result
                     $answer = $this->results[$i]['answer'] == -1 ? 50 : $this->results[$i]['answer'];

                     // create the dl
                     $s .=  '<dl class="dpad box_border">' . "\n";

		     // create the slider
                     $s .=  '<dd class="w30 fleft dpad rpad">
				<div id="slider_header_' . $this->results[$i]['idx'] . '" class="tcenter bpad">' . $this->fetchClientAlignment($this->results[$i]['answer']) . '</div>
                                <div id="slider_' . $this->results[$i]['idx'] . '" class="w95"></div>
                                <input type="hidden" name="answer_' . $this->results[$i]['idx'] . '" id="answer_' . $this->results[$i]['idx'] . '" value="' . $answer . '"/>
                            </dd>' . "\n";

                     // create the question
                     $s .=  '<dt class="w30 fleft dpad rpad">
                                ' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['title'] . '
                            </dt>' . "\n";



                     $j .= 'CreateSliderFinished("' . $this->results[$i]['idx'] . '", ' . $answer . ');' . "\n";

                     // create the statements, at the moment, these are hardcoded... yuck!!!
                     $s .=  '<dd class="w35 fright dpad">

                                <div id="statement_container_' . $this->results[$i]['idx'] . '">
                                    <div id="statement_' . $this->results[$i]['idx'] . '_0" class="align_statement">
                                        "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][0] . '"
                                    </div>
                                    <div id="statement_' . $this->results[$i]['idx'] . '_25" class="align_statement">
                                        "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][1] . '"
                                    </div>
                                    <div id="statement_' . $this->results[$i]['idx'] . '_50" class="align_statement">
                                        "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][2] . '"
                                    </div>
                                    <div id="statement_' . $this->results[$i]['idx'] . '_75" class="align_statement">
                                        "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][3] . '"
                                    </div>
                                    <div id="statement_' . $this->results[$i]['idx'] . '_100" class="align_statement">
                                        "' . $this->data[$this->results[$i]['role']]['aspects'][$this->results[$i]['aspect']]['alignment'][4] . '"
                                    </div>
                                </div>
                            </dd>
							<dd class="fclear"></dd>';



                     // end the dl
                     $s .=  '</dl>
                            <div class="fclear"></div>' . "\n";
                 } // end if it actually exists
             } // end loop

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
     * Threshhold stuff
     */

    protected function get_match_thresh_hold() {
	if (isset($this->matrix['match'])) {
	    return $this->matrix['match'];
	} else {
	    return 0;
	}
    }

    protected function get_separate_thresh_hold() {
	if (isset($this->matrix['separate'])) {
	    return $this->matrix['separate'];
	} else {
	    return 0;
	}
    }
} // end class

?>
