<?php
	// the error class
	
	class Error extends Message {
		
		// creates the html required to display the error
		
		function DisplayMessage() {
			$str = '';
			
			// only do if there is actually a error
			if (count($this->list) == 0) { return; }
			
			$str .= '<div id="errormessage">';
			
			// if there is a title
			if ($this->title != '') {
				$str .= '<h2>' . $this->title . '</h2>';
			}
			
			// do for each line
			$str .= '<ul>';
			
			foreach ($this->list as $key => $info) {
				$str .= '<li>' . $info . '</li>';
			}
			
			$str .= '</ul>';
			
			$str .= '</div>';
			
			return $str;
		} // end display message
	}


?>