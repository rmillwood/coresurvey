<?php
/*
 * Reports class
 */

class Reports {


    /**
     * function that returns summary data
     *
     */

    public function summary() {
        // fetch the roles and skills
        $role = new RoleSurvey();
        $skill = new SkillSurvey();

        $s =    '<dl class="dpad foverflow">
                    <dt class="w50 fleft tcenter">
                        <div class="w90 box_border tleft">
                            <h2 class="tcenter">Roles Survey</h2>
                            <ul>
                                <li>Roles: ' . $role->reportNumberofRoles() . '</li>
                                <li>Aspects: ' . $role->reportNumberofAspects() . '</li>
                                <li>Taken: ' . $role->reportTaken() . '</li>
                            </ul>
                        </div>
                    </dt>
                    <dd class="w50 fleft tcenter">
                        <div class="w90 box_border tleft">
                            <h2 class="tcenter">Skills Survey</h2>
                            <ul>
                                <li>Roles: ' . $skill->reportNumberofRoles() . '</li>
                                <li>Skills: ' . $skill->reportNumberofAspects() . '</li>
                                <li>Taken: ' . $skill->reportTaken() . '</li>
                            </ul>
                        </div>
                    </dd>
                </dl>';

        return $s;
    }
    
    /**
     * does the main report
     */
    
    public function main_report($start, $end) {
	$s = '';
	
	// fetch the roles and skills
        $role = new RoleSurvey();
        $skill = new SkillSurvey();
	
	// fetch all of the role surveys for this period
	$role->fetch_by_period($start, $end);
	$skill->fetch_by_period($start, $end);
	
	// do the title
	$s .=	'<h1>Macmillan Cancer Support</h1>';
	$s .=	'<h2>Cancer Voices Learning Needs Tool</h2>';
	$s .=	'<h3>Report for ' . date('d-m-Y', strtotime($start)) . ' to ' . date('d-m-Y', strtotime($end)) . '</h3>';
	
	// role analysis
	$s .=	'<table class="w90 fcenter">';
	$s .=	'<tr>
		    <td colspan="2">
			<h1>Role Analysis</h1>
		    </td>
		</tr>';
	
	// fetch all of the role surveys for this period
	$s .=	'<tr>
		    <td>
			Surveys Taken
		    </td>
		    <td>
			' . $role->num_taken() . '
		    </td>
		 </tr>';
	
	// paused
	$s .=	'<tr>
		    <td>
			Surveys Paused
		    </td>
		    <td>
			' . $role->get_paused() . '
		    </td>
		 </tr>';
	
	// completed
	$s .=	'<tr>
		    <td>
			Surveys Completed
		    </td>
		    <td>
			' . $role->get_completed() . '
		    </td>
		 </tr>';
	
	// activities
	$acts = $role->get_activity_proportions();
	
	
	// only do if not empty
	if (! empty($acts)) {
	    // title
	    $s .=   '<tr>
			<td colspan="2">
			    <h1>
				Activity Summary
			    </h1>
			</td>
		    </tr>';
	    
	    foreach($acts as $key => $item) {
		$s .=	'<tr>
			    <td>
				' . $role->get_aspect_title($item['role'], $item['aspect']) . '
			    </td>
			    <td>
				' . $this->return_percentage($item['answer'], $item['num']) . '%
			    </td>
			</tr>';	
	    }
	    
	    // do the role summary
	    $s .=   '<tr>
			<td colspan="2">
			    <h1>
				Role Summary
			    </h1>
			</td>
		    </tr>';
	    $r_sum = $this->get_role_totals($role->get_roles_summary(), $acts);
	    
	    foreach($r_sum as $key => $item) {
		$s .=	'<tr>
			    <td>
				' . $item['name'] . '
			    </td>
			    <td>
				' . $this->return_percentage($item['total'], $item['num']) . '%
			    </td>
			</tr>';
	    }
	} // end acts
	
	$s .=	'</table>';
	
	// activities
	$acts = $skill->get_activity_proportions();
	
	// Competency assessment
	$s .=	'<table class="w90 fcenter">';
	$s .=	'<tr>
		    <td colspan="2">
			<h1>Competency assessment</h1>
		    </td>
		</tr>';
	
	// fetch all of the role surveys for this period
	$s .=	'<tr>
		    <td>
			Surveys Taken
		    </td>
		    <td>
			' . $skill->num_taken() . '
		    </td>
		 </tr>';
	
	// paused
	$s .=	'<tr>
		    <td>
			Surveys Paused
		    </td>
		    <td>
			' . $skill->get_paused() . '
		    </td>
		 </tr>';
	
	// completed
	$s .=	'<tr>
		    <td>
			Surveys Completed
		    </td>
		    <td>
			' . $skill->get_completed() . '
		    </td>
		 </tr>';
	
	// only do if not empty
	if (! empty($acts)) {
	    // title
	    $s .=   '<tr>
			<td colspan="2">
			    <h1>
				Activity Summary
			    </h1>
			</td>
		    </tr>';
	    
	    foreach($acts as $key => $item) {
		$s .=	'<tr>
			    <td>
				' . $skill->get_aspect_title($item['role'], $item['aspect']) . '
			    </td>
			    <td>
				' . $this->return_percentage($item['answer'], $item['num']) . '%
			    </td>
			</tr>';	
	    }
	    
	    // do the role summary
	    $s .=   '<tr>
			<td colspan="2">
			    <h1>
				Role Summary
			    </h1>
			</td>
		    </tr>';
	    $r_sum = $this->get_role_totals($skill->get_skills_summary(), $acts);
	    
	    foreach($r_sum as $key => $item) {
		$s .=	'<tr>
			    <td>
				' . $item['name'] . '
			    </td>
			    <td>
				' . $this->return_percentage($item['total'], $item['num']) . '%
			    </td>
			</tr>';
	    }
	} // end acts
	
	$s .=	'</table>';
	
	return $s;
    } // end
    
    /**
     * produces a role summary
     */
    
    public function get_role_totals($role, $acts) {
	
	foreach($acts as $key => $item) {
	    $role[$item['role']]['total'] += $item['answer'];
	    $role[$item['role']]['num'] += $item['num'];
	}
	
	return $role;
    } // end 
    
    
    /**
     * returns a percentage
     */
    
    private function return_percentage($total, $num) {
	if ($total == 0 OR $num == 0) {
	    return 0;
	}
	
	return round(($total / $num));
    }
} // end class

?>
