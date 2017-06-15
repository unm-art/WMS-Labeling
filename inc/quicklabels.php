<?php
require_once('../vendor/autoload.php'); 

use OCLC\Auth\WSKey;
use OCLC\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/* Fetch labels using OCLC Auth and Guzzle
 * 
 * Put into a function, added author information, and made
 * pocket label printing optional, UNM University Libraries,
 * Jul 14, 2014
 */

/** 
 * Function quicklabels 
 * Takes barcode and returns call number, optionally pocket label text
 * 
 * Parameters
 * $nums    barcode of item
 * $title_p whether to print pocket label
 *          1 = print
 *          0 = do not print (default)
 * 
 * Returns array of 
 *   - call number
 *   - pocket label text or empty string
 */
function quicklabels($nums, $title_p = "0") {

    require('../config/config.php');
    require('../config/crosswalks.php');

    //  Set pocket label variable
    if ($title_p == '1') {
        $print_title = 1;
    } else {
        $print_title = 0;
    }

        $barcode = trim($nums);
        
        $url = URL . '/?q=barcode:' . $barcode;
        
        $wskey = new WSKey(WSKEY, SECRET);
        
        $user = new User($inst_id, PRINCIPALID, PRINCIPALIDNS);
        $options = array('user'=> $user);
        
        $authorizationHeader = $wskey->getHMACSignature('GET', $url, $options);
        
        $client = new Client(
        		[
        				'curl' => [
        						CURLOPT_SSLVERSION => '3'
        				]]
        		);
        $headers = array();
        $headers['Authorization'] = $authorizationHeader;
        
        try {
        	$response = $client->request('GET', $url, ['headers' => $headers]);
        	$xml = new SimpleXMLElement($response->getBody());
        	
        	// Set item information
        	if (isset($xml->entry->content->copy)) {
        		$copy = $xml->entry->content->copy;
        	} else {
        		//Temp fix for now
        		header("HTTP/1.1 500 Internal Server Error");
        		exit();
        	}
        	$bib = $copy->bib;
        	$oclc = substr($bib, 6);
        	$copynum = $copy->copyNumber;
        	$rectype = $copy->recordType;
        	$branch = $copy->holdingLocation;
        	$callnum = $copy->shelvingDesignation->information;
        	//Add extra space to any callnumbers with more than one period
        	if (substr_count($callnum, ".") > 1) {
        		$d = strrpos($callnum, ".");
        		$callnum = substr_replace($callnum, " .", $d, 1);
        	}
        	
        	if ($rectype == "SERIAL") {
        		$i = count($copy->holding);
        		for ($j = 0; $j < $i; $j++) {
        			$itemid = $copy->holding[$j]->pieceDesignation[0];
        			if ($itemid == $barcode) {
        				break;
        			}
        		}
        		
        		$itemparts = $copy->shelvingDesignation->itemPart;
        		$result = count($itemparts);
        		if ($result > 0) {
        			for ($k = 0; $k < $result; $k++) {
        				$itempart = $itemparts[$k];
        				$callnum = $callnum . " " . $itempart;
        			}
        		}
        		
        		$enumerations = $copy->holding[$j]->caption->enumeration;
        		$result = count($enumerations);
        		for ($k = 0; $k < $result; $k++) {
        			$label = $enumerations[$k]['label'];
        			if ($label == "(year)") {
        				$label = "";
        			}
        			if ($label == "(month)") {
        				$label = "";
        			}
        			$value = $enumerations[$k];
        			if ($label == "(month)") {
        				$value = $months[(int) $enumerations[$k]];
        			}
        			$callnum = $callnum . " " . $label . $value;
        		}
        		
        		$year = "";
        		$month = "";
        		$season = "";
        		$quarter = "";
        		$chronologies = $copy->holding[$j]->caption->chronology;
        		$result = count($chronologies);
        		for ($k = 0; $k < $result; $k++) {
        			if ($chronologies[$k]['label'] == "(year)") {
        				$year = $chronologies[$k];
        			} elseif ($chronologies[$k]['label'] == "(month)") {
        				$month = $chronologies[$k];
        			} elseif ($chronologies[$k]['label'] == "(season)") {
        				$season = $chronologies[$k];
        			} elseif ($chronologies[$k]['label'] == "(quarter)") {
        				$quarter = $chronologies[$k];
        			}
        		}
        		if ($year) {
        			$callnum = $callnum . " " . $year;
        		}
        		if ($month) {
        			if (stristr($month, "/")) {
        				$split = explode("/", $month);
        				$callnum = $callnum . " " . $months[(int) $split[0]];
        				$callnum = $callnum . "/" . $months[(int) $split[1]];
        			} elseif (is_numeric($month)) {
        				$callnum = $callnum . " " . $months[(int) $month];
        			} else {
        				$callnum = $callnum . " " . $month;
        			}
        		}
        		if ($season) {
        			if (stristr($season, "/")) {
        				$split = explode("/", $season);
        				$callnum = $callnum . " " . $seasons[(int) $split[0]];
        				$callnum = $callnum . "/" . $seasons[(int) $split[1]];
        			} elseif (is_numeric($season)) {
        				$callnum = $callnum . " " . $seasons[(int) $season];
        			}
        		}
        		if ($quarter) {
        			$callnum .= " " . $quarters[(int) $quarter];
        		}
        		
        		//Add description field to callnum
        		$description = $copy->holding[$j]->caption->description;
        		if ($description) {
        			$callnum = $callnum . " " . $description;
        		}
        		
        		//Add copy number / suffix to callnum
        		$suffixes = $copy->shelvingDesignation->suffix;
        		$result = count($suffixes);
        		if ($result > 0) {
        			for ($k = 0; $k < $result; $k++) {
        				$suffix = $suffixes[$k];
        				$callnum = $callnum . " " . $suffix;
        			}
        		}
        	} elseif ($rectype == "SINGLE_PART") {        # MONOGRAPH
        		$itemparts = $copy->shelvingDesignation->itemPart;
        		$result = count($itemparts);
        		
        		if (isset($copy->holding[0]->caption->description)) {
        			$description = $copy->holding[0]->caption->description;
        			$callnum = $callnum . " " . $description;
        		}
        		if ($result > 0) {
        			for ($k = 0; $k < $result; $k++) {
        				$itempart = $itemparts[$k];
        				$callnum = $callnum . " " . $itempart;
        			}
        		}
        		$suffixes = $copy->shelvingDesignation->suffix;
        		$result = count($suffixes);
        		if ($result > 0) {
        			for ($k = 0; $k < $result; $k++) {
        				$suffix = $suffixes[$k];
        				$callnum = $callnum . " " . $suffix;
        			}
        		}
        	} elseif ($rectype == "MULTI_PART" || $rectype == "UNKNOWN") {
        		# MULTIPART-MONOGRAPH
        		$i = count($copy->holding);
        		for ($j = 0; $j < $i; $j++) {
        			$itemid = $copy->holding[$j]->pieceDesignation[0];
        			if ($itemid == $barcode) {
        				break;
        			}
        		}
        		
        		$itemparts = $copy->shelvingDesignation->itemPart;
        		$result = count($itemparts);
        		if ($result > 0) {
        			for ($k = 0; $k < $result; $k++) {
        				$itempart = $itemparts[$k];
        				$callnum = $callnum . " " . $itempart;
        			}
        		}
        		
        		$description = $copy->holding[$j]->caption->description;
        		if ($description) {
        			$callnum = $callnum . " " . $description;
        		}
        		
        		$suffixes = $copy->shelvingDesignation->suffix;
        		$result = count($suffixes);
        		for ($i = 0; $i < $result; $i++) {
        			$suffix = $suffixes[$i];
        			$callnum = $callnum . " " . $suffix;
        		}
        		
        		$enumerations = $copy->holding[$j]->caption->enumeration;
        		$result = count($enumerations);
        		for ($i = 0; $i < $result; $i++) {
        			$label = $enumerations[$i]->label;
        			$value = $enumerations[$i]->value;
        			$callnum = $callnum . " " . $label . $value;
        		}
        	}
        	
        	// Set building/location using $shelf_loc array
        	$location = $copy->shelvingLocation;
        	$c = strcspn($location, ":");
        	$location = substr($location, 0, $c);
        	$location_full = $shelf_loc[(string)$branch][(string)$location];
        	$scheme = $copy->shelvingDesignation['scheme'];
        	
        	if ($scheme == "LIBRARY_OF_CONGRESS" || $scheme == "UNKNOWN") {
        		// Ensure space before cutters beginning with decimal
        		$d = strcspn($callnum, ".");
        		if (($d > 0) && !is_numeric(substr($callnum, $d + 1, 1)) && (substr($callnum, $d - 1, 1) != ' ')) {
        			$newcallnum = substr($callnum, 0, $d) . " " . substr($callnum, $d);
        			$callnum = $newcallnum;
        		}
        		// Ensure space before second cutter
        		$e = strcspn($callnum, ".", $d);
        		if (($e > 0) && !is_numeric(substr($callnum, $d + $e + 1, 1)) && (substr($callnum, $d + $e - 1, 1) != ' ')) {
        			$newcallnum = substr($callnum, 0, $d + $e) . " " . substr($callnum, $d + $e);
        			$callnum = $newcallnum;
        		}
        		// Ensure space between class number's letters and numbers
        		$c = strcspn($callnum, "0123456789");
        		if (($c > 0) && (substr($callnum, $c - 1, 1) != ' ')) {
        			$newcallnum = substr($callnum, 0, $c) . " " . substr($callnum, $c);
        			$callnum = $newcallnum;
        		}
        	}
        	// If Dewey, split on the decimal if the Dewey string is more than 6 digits
        	if ($scheme == "DEWEY_DECIMAL") {
        		//If more than 6 numbers in the number after the cutter, put the decimal on a new line
        		$f = strcspn($callnum, "ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        		if ($f > 7) {
        			$deweyparts = explode(".", $callnum);
        			$newcallnum = $deweyparts[0] . "." . " " . $deweyparts[1];
        			$callnum = $newcallnum;
        		}
        	}
        	// Prepare pocket label text if called for
        	if ($print_title) {
        		$worldcat_url = BIBURL . '/' . $oclc . '?wskey=' . BIBKEY;
        		
        		// Try up to 10 times to get a response; avoid failure due to network
        		for ($i = 0; $i < 10; $i++) {
        			$xml = simplexml_load_file($worldcat_url);
        			if (!$xml || $xml == "") {
        				continue;
        			} else {
        				break;
        			}
        		}
        		$xml->registerXPathNamespace("marc", "http://www.loc.gov/MARC21/slim");
        		
        		foreach ($xml->xpath('//marc:record') as $book) {
        			$book['xmlns:marc'] = 'http://www.loc.gov/MARC21/slim';
        			$field = simplexml_load_string($book->asXML());
        			// Title information
        			$subtitle = "";
        			if (count($field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='a']")) > 0) {
        				$title = $field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='a']");
        				if (count($field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='b']")) > 0) {
        					$subtitle = $field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='b']");
        				}
        				$full_title = (string) $title[0];
        				if ($subtitle) {
        					$full_title = $full_title . ' ' . $subtitle[0];
        				}
        				$full_title = rtrim($full_title, " /");
        				
        				$length = strlen($full_title);
        				$clean_title = "";
        				
        				for ($i = 0; $i < $length; $i++) {
        					if (ord($full_title[$i]) < 128) {
        						$clean_title = $clean_title . $full_title[$i];
        					}
        				}
        				
        				$full_title = $clean_title;
        				$return_title = "$full_title";
        			}
        			// Author information
        			if (count($field->xpath("marc:datafield[@tag='100']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='100']/marc:subfield[@code='a']");
        			} elseif (count($field->xpath("marc:datafield[@tag='110']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='110']/marc:subfield[@code='a']");
        			} elseif (count($field->xpath("marc:datafield[@tag='111']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='111']/marc:subfield[@code='a']");
        			} elseif (count($field->xpath("marc:datafield[@tag='700']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='700']/marc:subfield[@code='a']");
        			} elseif (count($field->xpath("marc:datafield[@tag='710']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='710']/marc:subfield[@code='a']");
        			} elseif (count($field->xpath("marc:datafield[@tag='711']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='711']/marc:subfield[@code='a']");
        			} else {
        				$author = array("");
        			}
        			$return_author = rtrim($author[0], ',');
        			if (strpos($return_author, ".") > 0) {
        				$return_author = rtrim($author[0], '.');
        			}
        		}
        		$print_call_num = "$callnum";
        	} else { // Set vars to empty if pocket label not desired
        		$return_title = "";
        		$return_author = "";
        		$print_call_num = "";
        	}
        	
        	$return_call_number = "$location_full<br />";
        	$return_call_number .= str_replace(" ", "<br />", $callnum);
        	
        	// Return array of call number, pocket label text
        	return array("$return_call_number", "$return_title<br />$return_author<br />$print_call_num");
        } catch (RequestException $error) {
        	if ($error->getResponse()){
        		$status = $error->getResponse()->getStatusCode();
        		if (implode($error->getResponse()->getHeader('Content-Type')) !== 'text/html;charset=utf-8'){
	        		$error_xml = new SimpleXMLElement($error->getResponse()->getBody());
	        		$message = $error_xml->children('http://worldcat.org/xmlschemas/response')->message;
	        		$detail = $error_xml->children('http://worldcat.org/xmlschemas/response')->detail;
        		} else {
        			$message = "";
        			$detail = "";
        		}
        	} else {
        		$status = 'Bad url';
        		$message = '';
        		$detail = '';
        	}
        	
        	// If barcode not found, return immediately with that info
        	if (stristr($detail, "Unknown piece designation")) {
        		return array("&nbsp;", "$barcode<br/>This barcode was not found in WMS.");
        	// If user invalid, return immediately with that info
        	} elseif (stristr($detail, "No SecurityContext present.") || stristr($message, "AuthorizationException.defaultMessage")){
        		return array("&nbsp;", "Please check your config.php file. Your principalID and principalIDNS are invalid");
        	// If Wskey is for the wrong service
        	}elseif (stristr($message, "&quot;WMS Collection Management&quot; (WMS_COLLECTION_MANAGEMENT) not found on WSKey")){
        		return array("&nbsp;", "Please check your config.php file. Your Wskey is not for the right web service");
        	// If Wskey is invalid, return immediately with that info	
        	} elseif ($status == '401' || $status == '403'){
        		return array("&nbsp;", "Please check your config.php file. Your Wskey is invalid");
        	// if URL bad, return immediately with that info
        	} elseif ($status == 'Bad url'){
        		return array("&nbsp;", "Please check your config.php file. Your URL is invalid");
        	} else{
        		return array("&nbsp;", "WMS Collection Management Service is unavailable");
        	}
        }
                

        
}

?>
