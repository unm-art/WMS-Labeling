<?php
require_once(__DIR__ . '/../vendor/autoload.php'); 

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;




function parse_callnum($callnum, $scheme){
	// Add extra space to any callnumbers with more than one period
	if (substr_count($callnum, ".") > 1) {
		$d = strrpos($callnum, ".");
		$callnum = substr_replace($callnum, " .", $d, 1);
	}

	// handle based on the scheme.
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
	elseif ($scheme == "DEWEY_DECIMAL") {
		// If Dewey, split on the decimal if the Dewey string is more than 6 digits
		// If more than 6 numbers in the number after the cutter, put the decimal on a new line
		$f = strcspn($callnum, "ABCDEFGHIJKLMNOPQRSTUVWXYZ");
		if ($f > 7) {
			$deweyparts = explode(".", $callnum);
			$newcallnum = $deweyparts[0] . "." . " " . $deweyparts[1];
			$callnum = $newcallnum;
		}
	}

	return $callnum;
}


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
function quicklabels($nums, $accessToken, $metadataToken, $title_p = "0"){

    require('../config/config.php');
    require('../config/crosswalks.php');

    //  Set pocket label variable
    if ($title_p == '1') {
        $print_title = 1;
    } else {
        $print_title = 0;
    }

        $barcode = urlencode(trim($nums));
		// use the metadata barcode endpoint
        $url = BARCODEURL . $barcode;
        
        
        $client = new Client();
        
        try {
			// Make api request to barcode and then laod the data
			$response = $client->request('GET', $url, [
				'headers' => [
					'Authorization' => 'Bearer ' . $metadataToken->getToken(),
					'Accept' => 'application/json'
				]
			]);

			$data = json_decode($response->getBody(), true);
			if ($data["numberOfHoldings"] > 0  && !isset($data['detailedHoldings'][0])) {
				return array("&nbsp;", "$barcode<br/>This barcode was not found in WMS holdings.");
			}
			$entry = $data['detailedHoldings'][0];

			// NOTE no rectype in new api? theres an item type but i think its different	
			// $rectype = $entry[holdingParts]['recordType'] ?? "";

			// Pull out the main information; note oclc can be multiple things
			$oclc = $entry['oclcNumber']
				?? $entry['currentOclcNumber']
				?? $entry['requestedOclcNumber']
				?? "";
			$branch = $entry["location"]['sublocationCollection'] ?? "";
			$shelving = $entry["location"]['shelvingLocation'] ?? "";
			$callnum = $entry['callNumber']['displayCallNumber'] ?? "";
        	$scheme = $entry["callNumber"]['shelvingScheme'] ?? "UNKNOWN";

			// parse the colnum based on scheme
			$callnum = parse_callnum($callnum, $scheme);

			// Mapping for location; lookup unm names from shelf_loc
			// note: dont think this cleanup is needed with the new api
			$location = $shelving;
        	$c = strcspn($location, ":");
        	$location = substr($location, 0, $c);
        	$location_full = $shelf_loc[(string)$branch][(string)$location];
			
        	// Prepare pocket label text if called for
        	if ($print_title) {
				// setup title and author for later
				$return_title = "";
				$return_author = "";

				// use the new metadata api
        		$metadataUrl = BIBURL . urlencode($oclc);

				$response = $client->request('GET', $metadataUrl, [
					'headers' => [
						'Authorization' => 'Bearer ' . $metadataToken->getToken(),
						'Accept'        => 'application/marcxml+xml' // request MARCXML format
					],
				]);

				$xmlString = (string) $response->getBody();
				$xml = new SimpleXMLElement($xmlString);

        		$xml->registerXPathNamespace("marc", "http://www.loc.gov/MARC21/slim");
        		
        		foreach ($xml->xpath('//marc:record') as $book) {
        			$book['xmlns:marc'] = 'http://www.loc.gov/MARC21/slim';
        			$field = simplexml_load_string($book->asXML());
        			// Title information
        			$subtitle = "";
        			if (count($field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='a']")) > 0) {
        				$title = $field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='a']");
        				if (count( (array) $field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='b']")) > 0) {
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
        				$return_title = rtrim($full_title, " /");
        			}
        			// Author information
        			if (count( (array) $field->xpath("marc:datafield[@tag='100']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='100']/marc:subfield[@code='a']");
        			} elseif (count( (array) $field->xpath("marc:datafield[@tag='110']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='110']/marc:subfield[@code='a']");
        			} elseif (count( (array) $field->xpath("marc:datafield[@tag='111']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='111']/marc:subfield[@code='a']");
        			} elseif (count( (array) $field->xpath("marc:datafield[@tag='700']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='700']/marc:subfield[@code='a']");
        			} elseif (count( (array) $field->xpath("marc:datafield[@tag='710']/marc:subfield[@code='a']")) > 0) {
        				$author = $field->xpath("marc:datafield[@tag='710']/marc:subfield[@code='a']");
        			} elseif (count( (array) $field->xpath("marc:datafield[@tag='711']/marc:subfield[@code='a']")) > 0) {
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
        	
        	// removes extra space after location <br />
			if ($location_full == "") {
				$return_call_number = "$location_full";
			} else {
				$return_call_number = "$location_full<br />";
			}      	
        	
        	// $return_call_number = "$location_full<br />";
        	$return_call_number .= str_replace(" ", "<br />", $callnum);
        	
        	// Return array of call number, pocket label text
        	return array("$return_call_number", "$return_title<br />$return_author<br />$print_call_num");
        }  catch (RequestException $error) {

        if ($error->getResponse()) {
            $status = $error->getResponse()->getStatusCode();
        } else {
            $status = "Bad HTTP request";
        }
        return array("&nbsp;", "WMS Metadata API error ($status)");
    }
}

?>
