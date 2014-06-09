#!/usr/bin/php
<?php

/*	php version of quicklabels.c using curl instead of Zend
	David Cunningham, UNB Libraries, Apr 25, 2014
*/

include 'wskeyv2.php';

// define ("WSKEY", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
 define ("WSKEY", "MTK9b14WyAlgZn2f3QCeTWR7sEpeKjnV83rC8O5svm8ocrmFHkRisqeF3Fpr8lfPy641mb1EakGeVW20");
//define ("WSKEY", "psomBeyDZAvtxS5VaQHLwTYUtXam6LE1slugnpfSsnxjYYi99YVGLdqSfdOTBLf9a8wUxs0sjfdszEgn");

// define ("BIBKEY", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
 define ("BIBKEY", "MTK9b14WyAlgZn2f3QCeTWR7sEpeKjnV83rC8O5svm8ocrmFHkRisqeF3Fpr8lfPy641mb1EakGeVW20");
//define ("BIBKEY", "psomBeyDZAvtxS5VaQHLwTYUtXam6LE1slugnpfSsnxjYYi99YVGLdqSfdOTBLf9a8wUxs0sjfdszEgn");

// define ("SECRET", "XXXXXXXXXXXXXXXXXXXXXXXX");
 define ("SECRET", "aqyK7qqhB04RHvH5o8yWZw==");
//define ("SECRET", "Gsw5eHStUPKg47S0Fb2t7w==");

// $inst_id = '999999';
$inst_id = "1822";
//$inst_id = "128807";

// define ("PRINCIPALID", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
 define ("PRINCIPALID", "f41b2eba-08f1-4bf5-8a1c-d0d47d9bff90");
//define ("PRINCIPALID", "8eaa9f92-3951-431c-975a-d7df26b8d131");

define ("PRINCIPALIDNS", "urn:oclc:wms:da");

// define ("URL", "https://circ.sd04.worldcat.org/LHR");
define ("URL", "https://circ.sd00.worldcat.org/LHR");

define ("BIBURL", "http://www.worldcat.org/webservices/catalog/content");

define ("METHOD", "GET");

define ("BODYHASH", "");

$months = array("nil","Jan.","Feb.","Mar.","Apr.","May","June","July","Aug.","Sept.","Oct.","Nov.","Dec.");

$seasons = array(
        "21" => "Spring",
        "22" => "Summer",
        "23" => "Autumn",
        "24" => "Winter"
    );

$quarters = array(
        "1" => "1st_qtr.",
        "2" => "2nd_qtr.",
        "3" => "3rd_qtr.",
        "4" => "4th_qtr."
    );
    
$buildings = array(
        "IQUU" => "ZIM",
        "IQUC" => "CSWR",
        "IQUF" => "FAL",
        "IQUP" => "PML",
        "IQUS" => "CSEL",
        "IQUW" => "UNM WEST"
    );


$lmargin = " ";

echo "Do you want to print Single labels or a Batch? (s/b/q) ";
$input = trim(fgets(STDIN));

if( $input == 'b' || $input == 'B' ) {
	$single_label = 0;
}
elseif( $input == 's' || $input == 'S' ) {
	$single_label = 1;
}
else {
	exit;
}

echo "Do you want to print the title on the pocket label? (y/n) ";
$input = trim(fgets(STDIN));

if( $input == 'y' || $input == 'Y' ) {
	$print_title = 1;
}
else {
	$print_title = 0;
}

$tmpname = tempnam("/tmp", "qlabel");
$tmpfile = fopen($tmpname, "w");
fwrite($tmpfile, "[5i");

$count=1;

while(1) {

	for( $j=0; $j<4; $j++ ) {
		$titles[$j] = "";
	}
	echo "\nEnter barcode $count: ";
	$barcode = trim(fgets(STDIN));
	if( $barcode == 'q' || $barcode == 'Q' ) {
		break;
	}

	$url = URL .'/?q=barcode:' . $barcode . '&inst=' . $inst_id . '&principalID=' . PRINCIPALID . '&principalIDNS=' . PRINCIPALIDNS;
	$auth = wskey_v2_request_header_value( $url, METHOD, '', WSKEY, SECRET );

	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, $url );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	#curl_setopt( $curl, CURLOPT_HEADER, true );
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Authorization: $auth", "Accept: application/json" ) );
	$response = curl_exec( $curl );
#var_dump($response);

	curl_close( $curl );

	$json = json_decode( $response );

#var_dump($json);

	$count++;
	if( stristr( $response, "Unknown piece designation" )) {
		echo "Barcode not found in WMS.\n";
		continue;
	}

	$copy = $json->entries[0]->content;
	$bib = $copy->bib;
	$oclc = substr($bib, 6);
	$copynum = $copy->copyNumber;
	$rectype = $copy->recordType;
	$branch = $buildings[$copy->holdingLocation];
	$callnum = $copy->shelvingDesignation->information;

	if( $rectype == "SERIAL" ) {
		$i = count( $copy->holding );
		for( $j=0; $j<$i; $j++ ) {
			$itemid = $copy->holding[$j]->pieceDesignation[0];
			if( $itemid == $barcode ) {
				break;
			}
		}

		$itemparts = $copy->shelvingDesignation->itemPart;
		$result = count($itemparts);
		if( $result > 0 ) {
			for( $k=0; $k<$result; $k++ ) {
				$itempart = $itemparts[$k];
				$callnum = $callnum . " " . $itempart;
			}
		}

		$enumerations = $copy->holding[$j]->caption->enumeration;
		$result = count($enumerations);
		for( $k=0; $k<$result; $k++ ) {
			$label = $enumerations[$k]->label;
			if( $label == "(year)" ) {
				$label = "";
			}
			if( $label == "(month)" ) {
				$label = "";
			}
			$value = $enumerations[$k]->value;
			if( $label == "(month)" ) {
				$value = $months[(int)$enumerations[$k]->value];
			}
			$callnum = $callnum . " " . $label . $value;
		}

		$year = "";
		$month = "";
		$season = "";
		$quarter = "";
		$chronologies = $copy->holding[$j]->caption->chronology;
		$result = count($chronologies);
		for( $k=0; $k<$result; $k++ ) {
			if( $chronologies[$k]->label == "(year)" ) {
				$year = $chronologies[$k]->value;
			}	
			elseif( $chronologies[$k]->label == "(month)" ) {
				$month = $chronologies[$k]->value;
			}	
			elseif( $chronologies[$k]->label == "(season)" ) {
				$season = $chronologies[$k]->value;
			}	
			elseif( $chronologies[$k]->label == "(quarter)" ) {
				$quarter = $chronologies[$k]->value;
			}	
		}
		if( $year ) {
			$callnum = $callnum . " " . $year;
		}
		if( $month ) {
			if( stristr( $month, "/" )) {
				$split = explode( "/", $month);
				$callnum = $callnum . " " . $months[(int)$split[0]];
				$callnum = $callnum . "/" . $months[(int)$split[1]];
			}
			elseif( is_numeric($month) ) {
				$callnum = $callnum . " " . $months[(int)$month];
			}
			else {
				$callnum = $callnum . " " . $month;
			}
		}
		if( $season ) {
			if( stristr( $season, "/" )) {
				$split = explode( "/", $season);
				$callnum = $callnum . " " . $seasons[(int)$split[0]];
				$callnum = $callnum . "/" . $seasons[(int)$split[1]];
			}
			elseif( is_numeric($season) ) {
				$callnum = $callnum . " " . $seasons[(int)$season];
			}
		}
		if( $quarter ) {
			$callnum .= " " . $quarters[(int)$quarter];
		}

		$description = $copy->holding[$j]->caption->description;
		if( $description ) {
			$callnum = $callnum . " " . $description;
		}

	}

	elseif( $rectype == "SINGLE_PART" ) {        # MONOGRAPH
		$itemparts = $copy->shelvingDesignation->itemPart;
		$result = count($itemparts);

		if( isset($copy->holding[0]->caption->description) ) {
			$description = $copy->holding[0]->caption->description;
			$callnum = $callnum . " " . $description;
		}
		if( $result > 0 ) {
			for( $k=0; $k<$result; $k++ ) {
				$itempart = $itemparts[$k];
				$callnum = $callnum . " " . $itempart;
			}
		}
		$suffixes = $copy->shelvingDesignation->suffix;
		$result = count($suffixes);
		if( $result > 0 ) {
			for( $k=0; $k<$result; $k++ ) {
				$suffix = $suffixes[$k];
				$callnum = $callnum . " " . $suffix;
			}
		}
	}

	elseif( $rectype == "MULTI_PART" || $rectype == "UNKNOWN" ) {
		# MULTIPART-MONOGRAPH
		$i = count( $copy->holding );
		for( $j=0; $j<$i; $j++ ) {
			$itemid = $copy->holding[$j]->pieceDesignation[0];
			if( $itemid == $barcode ) {
				break;
			}
		}

		$itemparts = $copy->shelvingDesignation->itemPart;
		$result = count($itemparts);

		if( $result > 0 ) {
			for( $k=0; $k<$result; $k++ ) {
				$itempart = $itemparts[$k];
				$callnum = $callnum . " " . $itempart;
			}
		}

		$suffixes = $copy->shelvingDesignation->suffix;
		$result = count($suffixes);
		for( $i=0; $i<$result; $i++ ) {
			$suffix = $suffixes[$i];
			$callnum = $callnum . " " . $suffix;
		}

		$enumerations = $copy->holding[$j]->caption->enumeration;
		$result = count($enumerations);
		for( $i=0; $i<$result; $i++ ) {
			$label = $enumerations[$i]->label;
			$value = $enumerations[$i]->value;
			$callnum = $callnum . " " . $label . $value;
		}

		$description = $copy->holding[$j]->caption->description;
		if( $description ) {
			$callnum = $callnum . " " . $description;
		}
	}

	$location = $copy->shelvingLocation;
	$c = strcspn($location, ":");
	$location = substr($location, 0, $c);
	$scheme = $copy->shelvingDesignation->scheme;

	if( $scheme == "LIBRARY_OF_CONGRESS" || $scheme == "UNKNOWN" ) {

		$c = strcspn($callnum, "0123456789");

		if( ($c > 0) && (substr($callnum,$c-1,1) != ' ') ) {
			$newcallnum = substr($callnum, 0, $c) . " " . substr($callnum, $c);
			$callnum = $newcallnum;
		}
	}

	if( $print_title ) {
    	$worldcat_url = BIBURL . '/' . $oclc . '?wskey=' . BIBKEY;
    
    	$xml = simplexml_load_file($worldcat_url);
    	$xml->registerXPathNamespace("marc", "http://www.loc.gov/MARC21/slim");
    
    	foreach($xml->xpath('//marc:record') as $book) {
    		$book['xmlns:marc'] = 'http://www.loc.gov/MARC21/slim';
    		$field = simplexml_load_string($book->asXML());
    		$subtitle = "";
    		if (count($field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='a']")) > 0 ) {
    			$title = $field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='a']");
    			if (count($field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='b']")) > 0 ) {
    				$subtitle = $field->xpath("marc:datafield[@tag='245']/marc:subfield[@code='b']");
    			}
    			$full_title = (string)$title[0];
    			if( $subtitle ) {
    				$full_title = $full_title . ' ' . $subtitle[0];
    			}
    			$full_title = rtrim($full_title, " /");
    
    			$length = strlen($full_title);
    			$clean_title = "";
    
    			for($i=0; $i<$length; $i++) { 
    				if( ord($full_title[$i]) < 128 ) {
    					$clean_title = $clean_title . $full_title[$i];
    				}
    			}
    
    			$full_title = $clean_title;
    			$clength = strlen($clean_title);
    			$number = $length / 30;
    			$title1 = substr($full_title, 0, 30);
    			$title2 = $title3 = $title4 = "";
    			if( $number > 1 ) {
    				$title2 = substr($full_title, 30, 30);
    			}
    			if( $number > 2 ) {
    				$title3 = substr($full_title, 60, 30);
    			}
    			if( $number > 3 ) {
    				$title4 = substr($full_title, 90, 30);
    			}
    			$titles = array( $title1, $title2, $title3, $title4 );
    		}
    	}
	} else {
	   $titles = array( "", "", "", "" );
	}
	
	fwrite($tmpfile, "$lmargin$branch\n");
	fwrite($tmpfile, "$lmargin$location");
	if( $print_title ) {
    	$length = 12 - strlen($location) + 3;
    	for( $j=0; $j<$length; $j++) {
    		fwrite($tmpfile, " ");
    	}
    	fwrite($tmpfile, "$titles[0]\n");
	} else {
	   fwrite($tmpfile, "\n");
	}
	$lines = 2;

	$callparts = substr_count($callnum, " ") + 1;
	$callnums = explode(" ", $callnum);
	
	for( $j=0; $j<$callparts; $j++) {

		if( $scheme == "LIBRARY_OF_CONGRESS" || $scheme == "UNKNOWN" ) {
			while( $callnums[$j][0] == '0' ) {
				$callnums[$j] = substr($callnums[$j], 1);
			}	
		}
		$callnums[$j] = str_replace("_", " ", $callnums[$j]);
		fwrite($tmpfile, "$lmargin$callnums[$j]");
		$length = 12 - strlen($callnums[$j]) + 3;
		for( $i=0; $i<$length; $i++) {
			fwrite($tmpfile, " ");
		}
		$i = $j+1;
		if( $i < 4 && $print_title) {
			fwrite($tmpfile, "$titles[$i]\n");
		}
		else {
			fwrite($tmpfile, "\n");
		}
		$lines++;
	}

	$skiplines = 10;
	if( ($count-1) % 7 == 0 ) {
		$skiplines = 10;
	}
	while( $lines < $skiplines ) {
		fwrite($tmpfile, "\n");
		$lines++;
	}
	
	if( $single_label ) {
		fwrite($tmpfile, "[4i");
		fclose($tmpfile);
		$tmpfile = fopen($tmpname, "r");
		$contents = fread($tmpfile, filesize($tmpname));
		fclose($tmpfile);
		echo $contents;
		unlink($tmpname);
		$tmpname = tempnam("/tmp", "qlabel");
		$tmpfile = fopen($tmpname, "w");
		fwrite($tmpfile, "[5i");
	}

}

if( $single_label ) {
	fclose($tmpfile);
	unlink($tmpname);
}

if( !$single_label ) {
	fwrite($tmpfile, "[4i");
	fclose($tmpfile);
	$tmpfile = fopen($tmpname, "r");
	$contents = fread($tmpfile, filesize($tmpname));
	fclose($tmpfile);
	echo $contents;
	unlink($tmpname);
}

?>
