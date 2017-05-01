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

class HoldingTest extends \PHPUnit_Framework_TestCase
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
     *@vcr copySuccess
     */
    function testGetHolding(){
    	$copy = Copy::find(55171589, $this->mockAccessToken);
    	$holdings = $copy->getHoldings();
    	$this->assertInstanceOf('WorldShare\WMS\Holding', $holdings[0]);
    	return $holdings[0];
    }
    
    /**
     * can parse Single Holding strings
     * @depends testGetHolding
     */
    function testParseLiterals($holding)
    {
    	$this->assertNotEmpty($holding->getPieceDesignation());
    	$this->assertNotEmpty($holding->getCategory());
 
    }
    
    /**
     * can parse Holding objects
     * @depends testGetHolding
     */
    function testParseObjects($holding)
    {
    	$captions = $holding->getCaptions();
    	$notes = $holding->getNotes();
    	$this->assertInstanceOf('WorldShare\WMS\Caption', $captions[0]);
    	$this->assertInstanceOf('WorldShare\WMS\Note', $notes[0]);
    }
    
}
