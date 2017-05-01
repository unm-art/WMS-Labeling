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

use GuzzleHttp\HandlerStack, GuzzleHttp\Middleware, GuzzleHttp\MessageFormatter, GuzzleHttp\Psr7\Response;

trait Helpers {

    /**
     * Parse the $options array in parts
     */
    protected static function parseOptions($options, $validRequestOptions)
    {
        if (empty($options) || !is_array($options)){
            Throw new \BadMethodCallException('Options must be a valid array');
        } elseif (isset($options['logger']) && !is_a($options['logger'], 'Psr\Log\LoggerInterface')){
            Throw new \BadMethodCallException('The logger must be an object that uses a valid Psr\Log\LoggerInterface interface');
        }
        
        if (isset($options['logger'])){
            $logger = $options['logger'];
            if(isset($options['log_format'])){
            	$logFormat = $options['log_format'];
            } else {
            	$logFormat = null;
            }
        } else {
            $logger = null;
            $logFormat = null;
        }
        
        $optionParts = array(
            'requestOptions' => static::getRequestOptions($options, $validRequestOptions),
            'logger' => $logger,
        	'log_format' => $logFormat
        );
        return $optionParts;
    }
    
    protected static function getRequestOptions($options, $validRequestOptions){
    		unset($options['logger']);
    		unset($options['log_format']);
            $requestOptions = array();
            foreach ($options as $optionName => $option) {
                if (in_array($optionName, array_keys($validRequestOptions))){
                	if (gettype($option) == $validRequestOptions[$optionName]){
                    	$requestOptions[$optionName] = $option;
                	} else {
                		Throw new \BadMethodCallException($optionName . ' must be a ' . $validRequestOptions[$optionName]);
                	}
                } else {
                	Throw new \BadMethodCallException($optionName . ' is not a valid request parameter');
                }
                
            }
            return $requestOptions;
    }
    
    /**
     * Get the relevant Guzzle options for the request
     */
    protected static function getGuzzleOptions($options = null){
    	if (isset($options['accept'])){
    		$accept = $options['accept'];
    	} else{
    		$accept = 'text/plain';
    	}
    	
        $headers = array(
        		'Accept' => $accept,
        		'User-Agent' => static::$userAgent
        );
        
        if (isset($options['accessToken'])){
        	$headers['Authorization'] = 'Bearer ' . $options['accessToken']->getValue();
        }
        
        $guzzleOptions = array(
        		'headers' => $headers,
        		'allow_redirects' => array(
        				'strict' => true
        		),
        		'timeout' => 60
        );
        
        if (static::$testServer){
        	$guzzleOptions['verify'] = false;
        }
        
        if (isset($options['logger'])){
        	$logger = $options['logger'];
        	if (isset($options['log_format'])){
        		$logFormat = $options['log_format'];
        	} else {
        		$logFormat = '{request} - {response}';
        	}
        	$stack = HandlerStack::create();
        	$stack->push(
        			Middleware::log(
        					$logger,
        					new MessageFormatter($logFormat)
        					)
        			);
        	$guzzleOptions['handler'] = $stack;
        }
        return $guzzleOptions;
    }
    
    /**
     * Build the query string for the request
     *
     * @param string $query
     * @param array $options
     * @return string
     */
    protected static function buildParameters($query = null, $options = null)
    {
        $parameters = array();
        
        if (isset($query)){
            $parameters['q'] = $query;
        }
    
        $repeatingQueryParms = '';
        if (!empty($options)){
            foreach ($options as $option => $optionValue){
                if (!is_array($optionValue)){
                    $parameters[$option] = $optionValue;
                } else {
                    foreach ($optionValue as $value){
                        $repeatingQueryParms .= '&' . $option . '=' . $value;
                    }
                }
            }
        }
        
        $queryString =  http_build_query($parameters) . $repeatingQueryParms;
    
        return $queryString;
    }
}