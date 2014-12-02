<?php

/* php version of quicklabels.c using curl instead of Zend
 * David Cunningham, UNB Libraries, Apr 25, 2014
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

    include_once('wskeyv2.php');
    require('../config/config.php');
    require('../config/crosswalks.php');

    //  Set pocket label variable
    if ($title_p == '1') {
        $print_title = 1;
    } else {
        $print_title = 0;
    }

        $barcode = trim($nums);

        $url = URL . '/?q=barcode:' . $barcode . '&inst=' . $inst_id . '&principalID=' . PRINCIPALID . '&principalIDNS=' . PRINCIPALIDNS;

        $auth = wskey_v2_request_header_value($url, METHOD, '', WSKEY, SECRET);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        //Used for local server testing without ssl cert
        //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: $auth", "Accept: application/atom+xml"));
        // Try up to 10 times to get a response; avoid failure due to network
        for ($i = 0; $i < 10; $i++) {
            $response = curl_exec($curl);
            if (stristr($response, "unexpected end of file") || $response == "" || (stristr($response, "The Server Failed to Respond Correctly") && !stristr($response, "Unknown piece designation"))) {
                continue;
            } else {
                break;
            }
        }
        curl_close($curl);

        $xml = new SimpleXMLElement($response);

        // If barcode not found, return immediately with that info
        if (stristr($response, "Unknown piece designation")) {
            return array("&nbsp;", "$barcode<br/>This barcode was not found in WMS.");
        }

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

            //Add copy number / suffix to callnum
            $suffixes = $copy->shelvingDesignation->suffix;
            $result = count($suffixes);
            if ($result > 0) {
                for ($k = 0; $k < $result; $k++) {
                    $suffix = $suffixes[$k];
                    $callnum = $callnum . " " . $suffix;
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

            $description = $copy->holding[$j]->caption->description;
            if ($description) {
                $callnum = $callnum . " " . $description;
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

            $description = $copy->holding[$j]->caption->description;
            if ($description) {
                $callnum = $callnum . " " . $description;
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
}

?>
