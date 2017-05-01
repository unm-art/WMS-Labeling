<?php
// Copyright 2013 OCLC
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

use OCLC\Auth\WSKey;
use OCLC\Auth\AccessToken;
use WorldShare\WMS\Copy;

class CopyTest extends \PHPUnit_Framework_TestCase
{
    
    function setUp()
    {
    	$options = array(
    			'authenticatingInstitutionId' => 128807,
    			'contextInstitutionId' => 128807,
    			'scope' => array('WorldCatDiscoveryAPI')
    	);
    	$this->mockAccessToken = $this->getMockBuilder(AccessToken::class)
    	->setConstructorArgs(array('client_credentials', $options))
    	->getMock();
    	
    	$this->mockAccessToken->expects($this->any())
    	->method('getValue')
    	->will($this->returnValue('tk_12345'));
    }
    
    /**
     * @vcr copySearchSuccessOCLCNumber
     * can parse set of Copies from a Search Result */
    
    function testSearchByOCLCNumber(){
    	$query = 'oclc:6692485';
    	$search = Copy::Search($query, $this->mockAccessToken);
    	
    	$this->assertInstanceOf('WorldShare\WMS\SearchResults', $search);
    	$this->assertEquals('0', $search->getStartIndex());
    	$this->assertEquals('10', $search->getItemsPerPage());
    	$this->assertInternalType('integer', $search->getTotalResults());
    	$this->assertEquals('10', count($search->getSearchResults()));
    	$results = $search->getSearchResults();
    	$i = $search->getStartIndex();
    	foreach ($search->getSearchResults() as $searchResult){
    		$this->assertInstanceOf('WorldShare\WMS\Copy', $searchResult);
    		$i++;
    		$this->assertEquals($i, $searchResult->getDisplayPosition());
    	}
    }
    
    /**
     * @vcr copySearchSuccessBarcode
     * can parse set of Copies from a Search Result */
    
    function testSearchByBarcode(){
    	$query = 'barcode:99887766112299';
    	$search = Copy::Search($query, $this->mockAccessToken);
    	
    	$this->assertInstanceOf('WorldShare\WMS\SearchResults', $search);
    	$this->assertEquals('0', $search->getStartIndex());
    	$this->assertEquals('10', $search->getItemsPerPage());
    	$this->assertInternalType('integer', $search->getTotalResults());
    	$this->assertEquals('10', count($search->getSearchResults()));
    	$results = $search->getSearchResults();
    	$i = $search->getStartIndex();
    	foreach ($search->getSearchResults() as $searchResult){
    		$this->assertInstanceOf('WorldShare\WMS\Copy', $searchResult);
    		$i++;
    		$this->assertEquals($i, $searchResult->getDisplayPosition());
    	}
    }
    
}
