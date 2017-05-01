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

class EnumerationTest extends \PHPUnit_Framework_TestCase
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
    function testGetEnumeration(){
    	$copy = Copy::find(55171589, $this->mockAccessToken);
    	$holdings = $copy->getHoldings();
    	$enumerations = $holdings->getEnumerations();
    	$this->assertInstanceOf('WorldShare\WMS\Enumeration', $enumerations[0]);
    	return $enumerations[0];
    }
    
    /**
     * can parse Enumeration string
     * @depends testGetChronology
     */
    function testParseLiterals($enumeration)
    {
    	$this->assertNotEmpty($enumeration->getLabel());
    	$this->assertNotEmpty($enumeration->getValue());
 
    }
    
}
