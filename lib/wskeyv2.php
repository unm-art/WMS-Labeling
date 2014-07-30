<?php

/*
	These functions are used to help build the authorization header that is required to be included in http requests for
	services protected by WSKey V2.

	wskey_v2_request_header_name() returns the name of the header to include
	wskey_v2_request_header_value(...) returned the value for that header

	wskey_v2_response_header_name() returns the name of the header that is included in the response when an
	   authorization error occurs.

	See http://intranet-wiki.oclc.org/wiki/WSKey for more information on WSKey.

*/

function wskey_v2_request_header_name() {
  return "Authorization";
}

function wskey_v2_request_header_value($url, $method, $bodyHash, $wskey, $secret)
{
    /*

    Parameters:
        $url - the WSKeyV2-protected URL you want to access
        $method - the method you are using to access it ("GET" or "POST")
        $bodyHash - the hash of the body of the request
          ( $bodyHash can just be set to an empty string if you don't want to use it; it is not required )
        $wskey - your web service key
        $secret - your secret key

    Return value is the value to include in the http header

    */
   
    $signatureUrl = 'https://www.oclc.org/wskey';

    $method = strtoupper($method);

    $parsedUrl = parse_url($url);
    $parsedSigUrl = parse_url($signatureUrl);

	// fill in request
	$request["clientId"] = $wskey;
	$request["timestamp"] = time();
    $request["nonce"] = sprintf("%08x",mt_rand(0, 0x7fffffff));

    $request["bodyHash"] = $bodyHash;
    $request["method"] = $method;
    
    /*  
       SZO - for now, use the hard coded url for the host,
             port, and path instead of from the requested
             url
    */

    //$request["host"] = $parsedUrl["host"];
    //if ($parsedUrl["port"]) {
    //	$request["port"] = $parsedUrl["port"];
    //}
    //else if ($parsedUrl["scheme"] == "http") {
    //	$request["port"] = 80;
    //}
    //else if ($parsedUrl["scheme"] == "https") {
    //	    	$request["port"] = 443;
    //}
    //$request["path"] = $parsedUrl["path"];

    $request["host"] = $parsedSigUrl["host"];
    if (isset($parsedSigUrl["port"])) {
      $request["port"] = $parsedSigUrl["port"];
    }
    else if ($parsedSigUrl["scheme"] == "http") {
      $request["port"] = 80;
    }
    else if ($parsedSigUrl["scheme"] == "https") {
              $request["port"] = 443;
    }
    $request["path"] = $parsedSigUrl["path"];

    if ($parsedUrl["query"]) {
    	//print("parsing " . $parsedUrl["query"]);
    	$params = array();
    	foreach (explode('&', $parsedUrl["query"]) as $pair) {
    		list($key, $value) = explode('=', $pair);
    		$params[] = array(urldecode($key), urldecode($value));
    	}
    	sort($params);
    	//print_r($params);
    }

    //print_r($request);

    $request["normalizedRequest"] = $request["clientId"] . "\n" .
		$request["timestamp"] . "\n" .
		$request["nonce"] . "\n" .
		$request["bodyHash"] . "\n" .
		$request["method"] . "\n" .
		$request["host"] . "\n" .
		$request["port"] . "\n" .
		$request["path"] . "\n";

	foreach ($params as $key) {
		$name = urlencode($key[0]);
		$value = urlencode($key[1]);
		$nameAndValue = "$name=$value";
		$nameAndValue = str_replace("+", "%20", $nameAndValue);
		$nameAndValue = str_replace("*", "%2A", $nameAndValue);
		$nameAndValue = str_replace("%7E", "~", $nameAndValue);
		$request["normalizedRequest"] .= $nameAndValue . "\n";
	}


	//print("Normalized Request: " . $request["normalizedRequest"]);


	$request["signature"] = base64_encode(hash_hmac("sha256",$request["normalizedRequest"], $secret, True));

	//print("Signature: " . $request["signature"]);
	//print "</pre>";

    return "http://www.worldcat.org/wskey/v2/hmac/v1" .
    	" clientId=\"" . $request["clientId"] . "\"" .
    	", timestamp=\"" . $request["timestamp"] . "\"" .
    	", nonce=\"" . $request["nonce"] . "\"" .
    	", signature=\"" . $request["signature"] . "\"";
}

function wskey_v2_response_header_name() {
  return "WWW-Authenticate";
}



?>
