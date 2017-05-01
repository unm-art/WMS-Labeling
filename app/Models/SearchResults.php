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


class CopySearchResults
{	
	protected $resultSet = null;
	protected $totalResults = null;
	protected $totalPages = null;
	protected $currentPage = null;
	protected $itemsPerPage = null;
	
	
	
	public function __construct($response){
		static::parseSearchResponse($response);
		return $this;
	}
	
	private static function parseSearchResponse($response)
	{
		try {
			$results = simplexml_load_string($response);
			
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