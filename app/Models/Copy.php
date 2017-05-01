<?php
// Copyright 2014 OCLC
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
// http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

namespace WorldShare\WMS;

use GuzzleHttp\Client, GuzzleHttp\Exception\RequestException, GuzzleHttp\Psr7\Response;

/**
 * A class that represents a Copy Resource in WMS
 *
 */
class Copy
{
	use Helpers;
	
	public static $serviceUrl = 'https://circ.sd00.worldcat.org';
	public static $testServer = FALSE;
	public static $userAgent = 'WMS Copy code library';
	
	protected $institution;
	protected $oclcNumber;
	protected $lastUpdateDate;
	protected $recordType;
	protected $receiptStatus;
	protected $holdingLocation;
	protected $shelvingLocation;
	protected $previousShelvingLocation;
	protected $shelvingScheme;
	protected $prefix;
	protected $shelvingInfo;
	protected $itemPart;
	protected $suffix;
	protected $holdings = array();
	
	/**
	 * Construct the Copy object
	 *
	 * @param string $uri
	 */
	public function __construct($id = null){
		if (isset($id)) {
			$this->copy = static::find($id);
		}

	}
	
	/**
	 * Find and retrieve a Copy by ID
	 *
	 * @param $id string
	 * @param $accessToken OCLC/Auth/AccessToken
	 * @param $options array
	 * @return WorldShare\WMS\Copy or \Guzzle\Http\Exception\BadResponseException
	 */
	public static function find($id, $accessToken, $options = null)
	{
		if (isset($options)){
			$logger = $parsedOptions['logger'];
			$log_format = $parsedOptions['log_format'];
		} else {
			$logger = null;
			$log_format = null;
		}
		
		if (!is_numeric($id)){
			Throw new \BadMethodCallException('You must pass a valid ID');
		} elseif (!is_a($accessToken, '\OCLC\Auth\AccessToken')) {
			Throw new \BadMethodCallException('You must pass a valid OCLC/Auth/AccessToken object');
		}
		
		static::requestSetup();
		
		$client = new Client(static::getGuzzleOptions(array('accessToken' => $accessToken, 'logger' => $logger, 'log_format' => $log_format)));
		
		$copyURI = Copy::$serviceUrl . '/LHR/' . $id;
		
		try {
			$response= $client->get($copyURI);
			$copy = Copy::parseResponse($response->getBody());
			return $copy;
		} catch (RequestException $error) {
			return Error::parseError($error);
		}
	}
	
	/**
	 * @param $query string
	 * @param $accessToken OCLC/Auth/AccessToken
	 * @param $options array All the optional parameters are valid
	 * - startIndex integer offset from the beginning of the search result set. defaults to 0
	 * - itemsPerPage integer representing the number of items to return in the result set. defaults to 10
	 *
	 * @return WorldShare\WMS\SearchResults or \Guzzle\Http\Exception\BadResponseException
	 */
	
	public static function search($query, $accessToken, $options = null)
	{
		$validRequestOptions = array(
				'startIndex' => 'integer',
				'itemsPerPage' => 'integer'
		);
		if (isset($options)){
			$parsedOptions = static::parseOptions($options, $validRequestOptions);
			$requestOptions = $parsedOptions['requestOptions'];
			$logger = $parsedOptions['logger'];
			$log_format = $parsedOptions['log_format'];
		} else {
			$requestOptions = array();
			$logger = null;
			$log_format = null;
		}
		
		if (!is_string($query)){
			Throw new \BadMethodCallException('You must pass a valid query');
		} elseif (!is_a($accessToken, '\OCLC\Auth\AccessToken')) {
			Throw new \BadMethodCallException('You must pass a valid OCLC/Auth/AccessToken object');
		}
		
		static::requestSetup();
		
		$client = new Client(static::getGuzzleOptions(array('accessToken' => $accessToken, 'logger' => $logger, 'log_format' => $log_format)));
		
		
		$copySearchURI = Copy::$serviceUrl . '/LHR?' . static::buildParameters($query, $requestOptions);
		
		try {
			$searchResponse = $client->get($copySearchURI);
			$search = Copy::parseSearchResponse($searchResponse->getBody());
			return $search;
		} catch (RequestException $error) {
			return Error::parseError($error);
		}
	}
	
	/**
	 * @param $response string
	 *
	 * @return WorldShare\WMS\Copy or \Guzzle\Http\Exception\BadResponseException
	 */
	
	private static function parseResponse($response)
	{
		try {
			$entry = simplexml_load_string($response);
			$entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
			$this->institution = (string) $entry->xpath('//copy/institution');
			$this->oclcNumber = $entry->xpath('//copy/bib');
			$this->lastUpdateDate = $entry->xpath('//copy/lastUpdateDate');
			$this->recordType = $entry->xpath('//copy/recordType');
			$this->receiptStatus = $entry->xpath('//copy/receiptStatus');
			$this->holdingLocation = $entry->xpath('//copy/holdingLocation');
			$this->shelvingLocation = $entry->xpath('//copy/shelvingLocation');
			$this->previousShelvingLocation = $entry->xpath('//copy/previousShelvingLocation');
			$this->shelvingScheme = $entry->xpath('//copy/shelvingScheme');
			$this->prefix = $entry->xpath('//copy/prefix');
			$this->shelvingInfo = $entry->xpath('//copy/shelvingInfo');
			$this->itemPart = $entry->xpath('//copy/itemPart');
			$this->suffix = $entry->xpath('//copy/suffix');
			
		} catch (RequestException $error) {
			throw new \Exception('Invalid XML');
		}
	}
	
	private static function parseSearchResponse($response)
	{
		try {
			$results = simplexml_load_string($response);
			$search = new CopySearch();
			
			$results->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
			$results->registerXPathNamespace("os", "http://a9.com/-/spec/opensearch/1.1/");
			
			// want an array of resource objects with their XML
			$entries = array();
			foreach ($results->xpath('/atom:feed/atom:entry') as $entry) {
				// create a new resource using class name
				$copy = new Copy();
				//extract all the properties
				
				$entries[] = $resource;
			}
			$search->setResultSet($entries);
			
			// set the currentPage
			$currentPage = $results->xpath('/atom:feed/os:startIndex');
			$search->setCurrentPage((string)$currentPage[0]);
			//set the total results
			$totalResults = $results->xpath('/atom:feed/os:totalResults');
			$search->setTotalResults((string)$totalResults[0]);
			// set the items per page
			$search= $results->xpath('/atom:feed/os:itemsPerPage');
			if ((string)$totalResults[0]  == 0) {
				$itemsPerPage = 0;
				$totalPages = 0;
			} elseif((string)$totalResults[0] < (string)$itemsPerPage[0]) {
				$itemsPerPage = (string)$totalResults[0];
				$totalPages = 1;
			} else {
				$totalPages = (string)$totalResults[0] /(string)$itemsPerPage[0];
				$itemsPerPage = (string)$itemsPerPage[0];
			}
			$search->setItemsPerPage((string)$itemsPerPage[0]);
			// calculate and set the total # of pages
			$search->setTotalPages($totalPages);
			
			return $search;
			
		} catch (RequestException $error) {
			throw new \Exception('Invalid XML');
		}
	}
}