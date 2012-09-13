<?php
	// the base class for messaging, status and error messages
	
	class Message {
		var $title = ''; // the title
		var $list = array(); // the lis of messages
		
		// changes the title
		
		function ChangeTitle($str) {
			$this->title = $str;
		} // end chnage title
		
		// adds a line to the message
		
		function AddMessage($str) {
			$this->list[] = $str;
		} // end addmessage
	
	}


?>