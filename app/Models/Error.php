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

/**
 * A class that represents an Error in WMS Collection Management
 *
 */
class Error
{
	
	protected $errorType;
	protected $errorCode;
	protected $errorMessage;
    
    /**
     * Get Error Type
     *
     * @return string
     */
    function getErrorType()
    {   
        return $this->errorType;
    }
    
    /**
     * Get Error Code
     *
     * @return string
     */
    function getErrorCode()
    {
        return $this->errorCode;
    }
    
    /**
     * Get Error Message
     *
     * @return string
     */
    function getErrorMessage()
    {
        return $this->errorMessage;
    }
    
    /**
     * Parse the response body for the error information
     * 
     * @param string $error
     * @return array WorldShare\WMS\Error
     */
    static function parseError($error){

    	try {
    		$error_response = simplexml_load_string($error->getResponse()->getBody());
    		// create an array of errors
            return $errors[0];
        } catch (\EasyRdf_Exception $e) {
        	throw new \Exception('Invalid XML');
        }
        
    }
    
    
}