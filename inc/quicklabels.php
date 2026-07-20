<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


// TODO: look into how 866-868 are used
// They may be used instead of the coded 853-855 Captions 
// and Pattern and 863-865 Enumeration and Chronology fields 
// for multipart and serial items when those fields cannot be 
// used adequately to describe the holdings. The 866-868 fields 
// may also be used in addition to an Enumeration and Chronology 
// field and any related Captions and Pattern field to record 
// and generate an alternative display for all or part of the 
// enumeration and chronology and captions and pattern fields.
//
// Overall i think if we dont find a chron or enum maybe check these
// otherwise these dont seem to be defaults
//  - data we would want is in 866$a matched on 866$8/6 
//    this is a little complicated though usure exactly how it works
//    https://www.loc.gov/marc/holdings/echdcntf.html



// record types for LHR results
// TODO seperate into a config consts file?
const RTYPE_UNKNOWN = "UNKNOWN";
const RTYPE_MULTIPART = "MULTIPART";
const RTYPE_SINGLEPART = "SINGLEPART";
const RTYPE_SERIAL = "SERIAL";

// max length allowed for the additional description
// - SERIAL: issue version
// - SINGLEPART: TODO
// - MULTIPART/UNKNOWN : TODO
const MAX_DESCRIPTION_LEN = 20;

/**
 * Decode the marcxml leader to determine record type
 * - specifications can be found here: 
 *   https://www.loc.gov/marc/holdings/hdleader.html
 * 		u - Unknown
 * 		v - Multipart item holdings
 * 		x - Single-part item holdings
 * 		y - Serial item holdings
 * 
 * - note that we could just match on the single character later, 
 *   but it is more manageable if we enumerate into consts
 */
function _decodeLeader($leader)
{
	$type_char = $leader[6] ?? '?';

	$types = [
		'u' => RTYPE_UNKNOWN,
		'v' => RTYPE_MULTIPART,
		'x' => RTYPE_SINGLEPART,
		'y' => RTYPE_SERIAL,
	];

	return $types[$type_char] ?? RTYPE_UNKNOWN;
}


/**
 * Find the "suffixes" (name from old api) unsure what these are exactly 
 * - we know copy number is onen of them and is from 853 / 863 / 876 $t
 * - otherwise they'd be from whatever 
 * 
 * 
 */
function _parseSuffixes($xml, $barcode, $description)
{
	$suffixes = "";

	$copy = $xml->xpath(
		"//marc:datafield[@tag='863'][marc:subfield[@code='p' and text()='$barcode']]/marc:subfield[@code='t']"
	);
	$copy_label = $xml->xpath(
		"//marc:datafield[@tag='853'][marc:subfield[@code='3' and text()='$description']]/marc:subfield[@code='t']"
	);

	// try grabbing from 876 instead if it doesnt exist
	if (!$copy) {
		$copy = $xml->xpath(
			"//marc:datafield[@tag='876'][marc:subfield[@code='p' and text()='$barcode']]/marc:subfield[@code='t']"
		);
	}


	if ($copy_label) {
		$copy_label = (string)$copy_label[0];
	} else {
		// TODO choose placeholder for copy label?
		$copy_label = "";
	}
	if ($copy) {
		$copy = (string)$copy[0];

		// ignore adding the copy if its number 1?
		// seems like all books have a copy number 
		// populated even if its the only one
		if ($copy != "1") {
			$suffixes .= $copy_label . $copy;
		}
	}


	return $suffixes;
}

/**
 * Handle the special types of items ie:
 * 	- serial
 *  - singlepart
 *  - multipart
 *  - unknown
 * 
 * Specifications for the marc21 format used by the metadata LHR endpoint
 * can be found here: https://www.loc.gov/marc/holdings/echdhome.html 
 * (note that the endpoint uses marcxml which is a direct mapping of marc to xml)
 */
function _callnumHandleType($callnum, $barcode, $entry, $client, $metadataToken)
{
	// load in the label mappings
	require('../config/crosswalks.php');

	// request the LHR info from metadatapi
	$lhrControlNumber = $entry["lhrControlNumber"];
	$url = LHRURL . $lhrControlNumber;

	// main quicklabels func will handle errors
	$response = $client->request('GET', $url, [
		'headers' => [
			'Authorization' => 'Bearer ' . $metadataToken->getToken(),
			'Accept' => 'application/marcxml+xml'
		]
	]);
	$xml = new SimpleXMLElement($response->getBody());
	$xml->registerXPathNamespace("marc", "http://www.loc.gov/MARC21/slim");

	// determine the type from the LHR leader
	$record_type = _decodeLeader((string)$xml->leader);

	// parts we might pull out of the data based on the type
	$description = "";
	$suffix_info = "";
	$chronology_info = "";
	$enumeration_info = "";

	if ($record_type == RTYPE_SERIAL) {
		// Handle Serial Records:
		// - get the 'description' (Materials specified, 876$3) information by matching the 
		//   barcode (piece designation, 876$p). 
		//   https://www.loc.gov/marc/holdings/hd876878.html
		// - lookup the matching chron (by $3 right now since $8/$6:the linking fields dont seem to be in any of our records...)
		//	 - 853: enum/chron labels: https://www.loc.gov/marc/holdings/hd853855.html
		// 	 - 863: enum/chron general info: https://www.loc.gov/marc/holdings/hd863865.html
		// - get the description (this is the 876$3)
		// - add copy number / suffix


		// search all of 876 fields and locate our specific barcode
		$description = $xml->xpath(
			"//marc:datafield[@tag='876'][marc:subfield[@code='p' and text()='$barcode']]/marc:subfield[@code='3']"
		);

		if ($description) {
			$description = (string)$description[0];
			$chron = NULL;
			$chron_text = NULL;

			// locate matching 863 (chronology data) by barcode 863$p 
			// the $p field is on 863, but not 853 for some reason? 
			// how are you supposed to ensure these get paired properly???
			$chron = $xml->xpath(
				"//marc:datafield[@tag='863'][marc:subfield[@code='p' and normalize-space(text())='$barcode']]"
			);

			// locate matching 853 (chronology labels) by Materials specified 853$3 = 876$3
			// the $3 field is on 876 and 853, and is noted as a linker field in the docs, but not for these two...
			$chron_text = $xml->xpath(
				"//marc:datafield[@tag='853'][marc:subfield[@code='3' and normalize-space(text())='$description']]"
			);

			if ($chron_text && $cron) {
				$chron = $chron[0];
				$chron_text = $chron_text[0];

				$year = "";
				$month = "";
				$season = "";
				$quarter = "";

				// loop over all the date chronology subfields
				foreach (["i", "j", "k", "l", "m", "v"] as $subfield) {
					$path = "marc:subfield[@code='" . $subfield . "']";

					// pull the data from 863 and the label for it from 853
					$field_data = $chron->xpath($path);
					$field_label = $chron_text->xpath($path);

					// if either doesnt exist then we continue
					// note that you can have data without a label but
					// our old code heavily relied on checking the label
					// so for consistency we check the label here too
					if (!$field_data || !$field_label) {
						continue;
					}

					// if exists force into string
					$field_data = (string)$field_data[0];
					$field_label = (string)$field_label[0];

					switch ($field_label) {
						case "(year)":
							$year = $field_data;
						case "(month)":
							$month = $field_data;
						case "(season)":
							$season = $field_data;
						case "(month)":
							$month = $field_data;
					}
				}

				// handle creation of the chronology_info string
				if ($year) {
					$chronology_info .= " " . $year;
				}

				if (stristr($month, "/")) {
					$split = explode("/", $month);
					$chronology_info .= " " . $months[(int)$split[0]];
					$chronology_info .= "/" . $months[(int)$split[1]];
				} elseif (is_numeric($month)) {
					$chronology_info .= " " . $months[(int)$month];
				} else {
					$chronology_info .= " " . $month;
				}

				if (stristr($season, "/")) {
					$split = explode("/", $season);
					$chronology_info .= " " . $seasons[(int)$split[0]];
					$chronology_info .= "/" . $seasons[(int)$split[1]];
				} elseif (is_numeric($season)) {
					$chronology_info .= " " . $seasons[(int)$season];
				}

				if ($quarter) {
					$quarter = (string)$q[0];
					$chronology_info .= " " . $quarters[(int)$quarter];
				}
			}

			// Suffix Handling (Copy Number, ..., TODO figure out what the old api considered suffixes)
			$suffix_info = _parseSuffixes($xml, $barcode, $description);
		}

	} elseif ($record_type == RTYPE_SINGLEPART) {
		// Handle Singlepart records:
		// - get and append description (materials specified 876$3) to callnum
		// - TODO: append shelving designation suffix (might already be done in new api...)
		// 	 could be detailedHoldings-> X -> location -> sublocationCollection
		// 	 this does just seem to be our branch mapping tho...

		// Pull out description by matching on barcode
		// NOTE: may not need to do this for singlepart items, could just
		//       grab the first 876 (and supposedly only) and get its $3
		$description = $xml->xpath(
			"//marc:datafield[@tag='876'][marc:subfield[@code='p' and text()='$barcode']]/marc:subfield[@code='3']"
		);
		if ($description) {
			$description = (string)$description[0];
			// Suffix Handling (Copy Number, ..., TODO figure out what the old api considered suffixes)
			$suffix_info = _parseSuffixes($xml, $barcode, $description);
		}
	} elseif ($record_type == RTYPE_MULTIPART || $record_type == RTYPE_UNKNOWN) {
		// handle multipart and unknown type items
		// - get and append description (materials specified 876$3) to callnum
		// - handle enumerations 863 and 853 ()
		// - handle suffixes


		// Pull out description by matching on barcode
		$description = $xml->xpath(
			"//marc:datafield[@tag='876'][marc:subfield[@code='p' and text()='$barcode']]/marc:subfield[@code='3']"
		);
		if ($description) {
			$description = (string)$description[0];
			// Suffix Handling (Copy Number, ..., TODO figure out what the old api considered suffixes)
			$suffix_info = _parseSuffixes($xml, $barcode, $description);
		}


		// handle enumerations
		// locate matching 863 (enumeration data) by barcode 863$p 
		$enumeration = $xml->xpath(
			"//marc:datafield[@tag='863'][marc:subfield[@code='p[' and normalize-space(text())='$barcode']]"
		);
		// locate matching 853 (enumeration labels) by Materials specified 853$3 = 876$3
		$enumeration_label = NULL;
		if ($description) {
			$enumeration_label = $xml->xpath(
				"//marc:datafield[@tag='853'][marc:subfield[@code='3' and normalize-space(text())='$description']]"
			);
		}

		if ($enumeration && $enumeration_label) {
			$enumeration = $enumeration[0];
			$enumeration_label = $enumeration_label[0];

			// loop over all the date chronology subfields
			foreach (["a", "b", "c", "d", "e", "f", "g", "h"] as $subfield) {
				$path = "marc:subfield[@code='" . $subfield . "']";

				// pull the data from 863 and the label for it from 853
				$field_data = $enumeration->xpath($path);
				$field_label = $enumeration_label->xpath($path);

				// if either doesnt exist then we continue
				if (!$field_data || !$field_label) {
					continue;
				}

				// if exists force into string
				$field_data = (string)$field_data[0];
				$field_label = (string)$field_label[0];

				// concat onto the enumerations info
				$enumeration_info .= " " . $field_label . $field_data;
			}
		}
	}

	// add on the  chron info if it exists (should only be serials with this)
	if ($chronology_info) {
		$callnum .= " " . $chronology_info;
	}

	// add on the description if it exists (876$3)
	// NOTE: these can be very long for some serial records as they 
	// 		 may use the 876$3 field as a summary of every item belonging
	//		 to the serial collection rather than invididual label per item
	if ($description && strlen($description) < MAX_DESCRIPTION_LEN) {
		$callnum .= " " . $description;
	}

	// add on suffix info if it exists
	if ($suffix_info) {
		$callnum .= " " . $suffix_info;
	}

	// add on enumeration details if they exist
	if ($enumeration_info) {
		$callnum .= " " . $enumeration_info;
	}

	return $callnum;
}

/**
 * handle the callnum based on the scheme used
 */
function _callnumHandleScheme($callnum, $scheme)
{
	// handle based on the scheme.
	if ($scheme == "DEWEY_DECIMAL") {
		// If Dewey, split on the decimal if the Dewey string is more than 6 digits
		// If more than 6 numbers in the number after the cutter, put the decimal on a new line
		$f = strcspn($callnum, "ABCDEFGHIJKLMNOPQRSTUVWXYZ");
		if ($f > 7) {
			$deweyparts = explode(".", $callnum);
			$newcallnum = $deweyparts[0] . "." . " " . $deweyparts[1];
			$callnum = $newcallnum;
		}
	} elseif ($scheme == "LIBRARY_OF_CONGRESS" || $scheme == "UNKNOWN" || $scheme == "Library of Congress classification") {
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
	} else {
		// Ensure space before cutters beginning with decimal
		// TODO: Add error handling
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

	return $callnum;
}

/**
 * Update the callnum based on the type of the entry as well as the scheme
 */
function parseCallnum($entry, $barcode, $client, $metadataToken)
{
	// load in relevant parts from the entry
	$scheme = $entry["callNumber"]['shelvingScheme'] ?? "UNKNOWN";
	$callnum = $entry['callNumber']['displayCallNumber'] ?? "";

	// Add extra space to any callnumbers with more than one period
	if (substr_count($callnum, ".") > 1) {
		$d = strrpos($callnum, ".");
		$callnum = substr_replace($callnum, " .", $d, 1);
	}

	// update the callnum based on record type eg serial, multipart
	$callnum = _callnumHandleType($callnum, $barcode, $entry, $client, $metadataToken);

	// handle the scheme of the call num 
	$callnum = _callnumHandleScheme($callnum, $scheme);


	return $callnum;
}


/**
 * Fetch marcxml bib record to generate the pocket label information
 * 
 * returns an array [author, title]
 */
function fetchPocketLabels($oclc, $client, $metadataToken)
{
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
			if (count((array) $field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='b']")) > 0) {
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
		if (count((array) $field->xpath("marc:datafield[@tag='100']/marc:subfield[@code='a']")) > 0) {
			$author = $field->xpath("marc:datafield[@tag='100']/marc:subfield[@code='a']");
		} elseif (count((array) $field->xpath("marc:datafield[@tag='110']/marc:subfield[@code='a']")) > 0) {
			$author = $field->xpath("marc:datafield[@tag='110']/marc:subfield[@code='a']");
		} elseif (count((array) $field->xpath("marc:datafield[@tag='111']/marc:subfield[@code='a']")) > 0) {
			$author = $field->xpath("marc:datafield[@tag='111']/marc:subfield[@code='a']");
		} elseif (count((array) $field->xpath("marc:datafield[@tag='700']/marc:subfield[@code='a']")) > 0) {
			$author = $field->xpath("marc:datafield[@tag='700']/marc:subfield[@code='a']");
		} elseif (count((array) $field->xpath("marc:datafield[@tag='710']/marc:subfield[@code='a']")) > 0) {
			$author = $field->xpath("marc:datafield[@tag='710']/marc:subfield[@code='a']");
		} elseif (count((array) $field->xpath("marc:datafield[@tag='711']/marc:subfield[@code='a']")) > 0) {
			$author = $field->xpath("marc:datafield[@tag='711']/marc:subfield[@code='a']");
		} else {
			$author = array("");
		}
		$return_author = rtrim($author[0], ',');
		if (strpos($return_author, ".") > 0) {
			$return_author = rtrim($author[0], '.');
		}
	}

	return [$return_author, $return_title];
}


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
function quicklabels($nums, $accessToken, $metadataToken, $title_p = "0")
{

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
		// Make api request to barcode and then load the data
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


		// OCLC number; can have multiple different fields apparently
		$oclc = $entry['oclcNumber']
			?? $entry['currentOclcNumber']
			?? $entry['requestedOclcNumber']
			?? "";


		// ------------- CALLNUM LOADING --------------- // 
		// get the callnum based on scheme & type
		// fetches lhr records to check type.
		$callnum = parseCallnum($entry, $barcode, $client, $metadataToken);


		// ------------- HANDLE LOCATION --------------- // 
		// lookup unm friendly names from shelf loc
		$branch = $entry["location"]['sublocationCollection'] ?? "";
		$shelving = $entry["location"]['shelvingLocation'] ?? "";

		$location = $shelving;
		$c = strcspn($location, ":");
		$location = substr($location, 0, $c);
		$location_full = $shelf_loc[(string)$branch][(string)$location] ?? "";

		// removes extra space after location <br />
		if ($location_full == "") {
			$return_call_number = "$location_full";
		} else {
			$return_call_number = "$location_full<br />";
		}

		// $return_call_number = "$location_full<br />";
		$return_call_number .= str_replace(" ", "<br />", $callnum);

		// ------------- POCKET LABELS --------------- // 
		// Prepare pocket label text if asked for
		// all empty by default
		$return_title = "";
		$return_author = "";
		$print_call_num = "";
		if ($print_title) {
			$print_call_num = "$callnum";
			[$return_author, $return_title] = fetchPocketLabels($oclc, $client, $metadataToken);
		}

		// Return array of call number, pocket label text
		return array("$return_call_number", "$return_title<br />$return_author<br />$print_call_num");
	} catch (RequestException $error) {
		if ($error->getResponse()) {
			$status = $error->getResponse()->getStatusCode();
		} else {
			$status = "Bad HTTP request";
		}
		return array("&nbsp;", "WMS Metadata API error ($status)");
	}
}
