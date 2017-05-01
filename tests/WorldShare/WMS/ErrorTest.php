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

class ErrorTest extends \PHPUnit_Framework_TestCase
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
     * @vcr copyFailureInvalidAccessToken
     * Invalid Access Token
     */
    function testErrorInvalidAccessToken(){
    	$error = Copy::find(41266045, $this->mockAccessToken);
    	$this->assertInstanceOf('WorldShare\WMS\Error', $error);
    	$this->assertNotEmpty($error->getErrorType());
    	$this->assertEquals('401', $error->getErrorCode());
    	$this->assertEquals('The given access token is not authorized to view this resource.  Please check your Authorization header and try again.', $error->getErrorMessage());
    }
    
    /**
     * @vcr copyFailureExpiredAccessToken
     * Expired Access Token **/
    function testFailureExpiredAccessToken()
    {
    	$error = Bib::find(41266045, $this->mockAccessToken);
    	$this->assertInstanceOf('WorldShare\WMS\Error', $error);
    	$this->assertNotEmpty($error->getErrorType());
    	$this->assertEquals('401', $error->getErrorCode());
    	$this->assertEquals('The given access token is not authorized to view this resource.  Please check your Authorization header and try again.', $error->getErrorMessage());
    }
    
    /**
     * @vcr copyFailureSearchNoQuery
     * No query passed **/
    function testFailureNoQuery()
    {
    	$query = ' ';
    	$error = Copy::Search($query, $this->mockAccessToken);

    	$this->assertInstanceOf('WorldShare\WMS\Error', $error);
    	$this->assertNotEmpty($error->getErrorType());
    	$this->assertEquals('400', $error->getErrorCode());
    	$this->assertEquals('blah', $error->getErrorMessage());
    }
 
}
