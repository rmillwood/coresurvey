<?php
/*
 * Provides low level db functions amongst classes
 */

class DBhandler {
    /**
     * Compresses data for storage
     */

    public static function compress($str) {
	if (empty($str)) {
	    return $str;
	} else {
	    return base64_encode(gzcompress(serialize($str)));
	}
    } // end function

    /**
     * Decompresses data from storage
     */

    public static function deCompress($str) {
	if (empty($str)) {
	    return $str;
	} else {
	    return unserialize(gzuncompress(base64_decode($str)));
	}
    }

    /**
     * cleans a string
     */


    public static function clean($string)
    {
        if (! is_string($string)) return;
        $url = str_replace("'", '', $string);
        $url = str_replace('%20', ' ', $url);
        $url = preg_replace('~[^\\pL0-9_]+~u', '-', $url); // substitutes anything but letters, numbers and '_' with separator
        $url = trim($url, "-");
        $url = iconv("utf-8", "us-ascii//TRANSLIT", $url);  // you may opt for your own custom character map for encoding.
        $url = strtolower($url);
        $url = preg_replace('~[^-a-z0-9_]+~', '', $url); // keep only letters, numbers, '_' and separator
        return $url;
    } // end function

    /**
     * shows a varibale
     */

    public static function ShowDebug($str) {
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }

    /**
     * shows and halts
     */

    public static function ShowDebugHalt($str) {
        echo '<pre>';
        print_r($str);
        exit;
    }
} // end class

?>
