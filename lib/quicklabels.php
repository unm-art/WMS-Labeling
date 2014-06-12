<?php

function quicklabels($nums, $title_p = "y") {

    /* 	php version of quicklabels.c using curl instead of Zend
      David Cunningham, UNB Libraries, Apr 25, 2014
     */

    include_once('wskeyv2.php');
    include_once('../config/config.php');
    include_once('../config/crosswalks.php');

    if ($title_p == 'y' || $title_p == 'Y') {
        $print_title = 1;
    } else {
        $print_title = 0;
    }

        $barcode = trim($nums);

        $url = URL . '/?q=barcode:' . $barcode . '&inst=' . $inst_id . '&principalID=' . PRINCIPALID . '&principalIDNS=' . PRINCIPALIDNS;

        $auth = wskey_v2_request_header_value($url, METHOD, '', WSKEY, SECRET);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: $auth", "Accept: application/json"));
        for ($i = 0; $i < 10; $i++) {
            $response = curl_exec($curl);
            if (stristr($response, "unexpected end of file") || $response == "") {
                continue;
            } else {
                break;
            }
        }
        curl_close($curl);

        $json = json_decode($response);

        if (stristr($response, "Unknown piece designation")) {
            return array("$barcode", "This barcode was not found in WMS.");
        }

        $copy = $json->entries[0]->content;
        $bib = $copy->bib;
        $oclc = substr($bib, 6);
        $copynum = $copy->copyNumber;
        $rectype = $copy->recordType;
        $branch = $copy->holdingLocation;
        $callnum = $copy->shelvingDesignation->information;

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
                $label = $enumerations[$k]->label;
                if ($label == "(year)") {
                    $label = "";
                }
                if ($label == "(month)") {
                    $label = "";
                }
                $value = $enumerations[$k]->value;
                if ($label == "(month)") {
                    $value = $months[(int) $enumerations[$k]->value];
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
                if ($chronologies[$k]->label == "(year)") {
                    $year = $chronologies[$k]->value;
                } elseif ($chronologies[$k]->label == "(month)") {
                    $month = $chronologies[$k]->value;
                } elseif ($chronologies[$k]->label == "(season)") {
                    $season = $chronologies[$k]->value;
                } elseif ($chronologies[$k]->label == "(quarter)") {
                    $quarter = $chronologies[$k]->value;
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

        $location = $copy->shelvingLocation;
        $c = strcspn($location, ":");
        $location = substr($location, 0, $c);
        $location_full = $shelf_loc[$branch][$location];
        $scheme = $copy->shelvingDesignation->scheme;

        if ($scheme == "LIBRARY_OF_CONGRESS" || $scheme == "UNKNOWN") {
            $d = strcspn($callnum, ".");
            if (($d > 0) && !is_numeric(substr($callnum, $d + 1, 1)) && (substr($callnum, $d - 1, 1) != ' ')) {
                $newcallnum = substr($callnum, 0, $d) . " " . substr($callnum, $d);
                $callnum = $newcallnum;
            }

            $c = strcspn($callnum, "0123456789");
            if (($c > 0) && (substr($callnum, $c - 1, 1) != ' ')) {
                $newcallnum = substr($callnum, 0, $c) . " " . substr($callnum, $c);
                $callnum = $newcallnum;
            }
        }

        if ($print_title) {
            $worldcat_url = BIBURL . '/' . $oclc . '?wskey=' . BIBKEY;

            $xml = simplexml_load_file($worldcat_url);
            $xml->registerXPathNamespace("marc", "http://www.loc.gov/MARC21/slim");

            foreach ($xml->xpath('//marc:record') as $book) {
                $book['xmlns:marc'] = 'http://www.loc.gov/MARC21/slim';
                $field = simplexml_load_string($book->asXML());
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
                }
                $return_author = rtrim($author[0], ',');
                if (strpos($return_author, ".") > 0) {
                    $return_author = rtrim($author[0], '.');
                }
            }
            $print_call_num = "$callnum";
        } else {
            $return_title = "";
            $return_author = "";
            $print_call_num = "";
        }

        $return_call_number = "$location_full<br />";
        $return_call_number .= str_replace(" ", "<br />", $callnum);

    return array("$return_call_number", "$return_title<br />$return_author<br />$print_call_num");
}

?>
