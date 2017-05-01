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

use OCLC\Auth\WSKey;
use OCLC\Auth\AccessToken;
use WorldShare\WMS\Copy;

use Monolog\Logger;
use Monolog\Handler\TestHandler;

class CopyLogTest extends \PHPUnit_Framework_TestCase
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
    function testLoggerSuccess(){
        $logger = new Logger('testLogger');
        $handler = new TestHandler;
        $logger->pushHandler($handler);
        $options = array(
            'logger' => $logger
        );
        $copy = Copy::find(206732470, $this->mockAccessToken);
        
        $records = $handler->getRecords();
        $this->assertContains('/LHR/206732470', $records[0]['message']);
        
    }
    
    /**
     *@vcr copySuccess
     */
    function testLoggerSuccessSpecificFormat(){
    	$logger = new Logger('testLogger');
    	$handler = new TestHandler;
    	$logger->pushHandler($handler);
    	$options = array(
    			'logger' => $logger,
    			'log_format' => 'Request - {method} - {uri} - {code}'
    	);
    	$copy = Copy::find(55171589, $this->mockAccessToken);
    	
    	$records = $handler->getRecords();
    	$this->assertContains('Request - GET - https://circ.sd00.worldcat.org/LHR/206732470 - 200', $records[0]['message']);
    	
    }
    
    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage The logger must be an object that uses a valid Psr\Log\LoggerInterface interface
     */
    function testLoggerNotValid()
    {
        $options = array(
            'logger' => 'lala'
        );
        $copy= Copy::find('string', $this->mockAccessToken, $options);
    }
}
