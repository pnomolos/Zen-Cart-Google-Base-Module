<?php
/*
 * Copyright (C) 2006 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
 
/**
 * @author pablif@gmail.com
 * @version $Id: gb-http.php 0 Sep 11, 2007 4:03:36 PM pablif@gmail.com $
 */
 
/**
 * Wrapper for processing the response of a {@link GoogleBaseHttpRequest}.
 */
class GoogleBaseHttpResponse {
  var $_body;
  var $_status_code;
  var $_curl_errno;
  var $_curl_error;
  
  /**
   * @param string $http_status_code The http status code of the response
   * @param string $body The body of the response
   * @param int $connection_error_no The error number (errno) of the curl 
   * connection (if any). Defaults to 0.
   * @param string $connection_error_str The descriptive error string of the
   * curl connection (if any). Defaults to ''.
   */
  function GoogleBaseHttpResponse($http_status_code, $body,
                                  $connection_error_no=0,
                                  $connection_error_str='') {
    $this->_body = $body;
    $this->_status_code = (string)$http_status_code;
    $this->_connection_error_no = $connection_error_no;
    $this->_connection_error_str = $connection_error_str;
  }
  
  /**
   * @return boolean True if the response had any connection errors or a 
   * status code >= 4xx.
   */
  function hasErrors() {
    return $this->_curl_errno != 0 || $this->_status_code[0] != '2';
  }
  
  /**
   * @return string The http status code of the response.
   */
  function getStatusCode() {
    return $this->_status_code;
  }
  
  /**
   * @return string A descriptive string of the connection error (if any).
   */
  function getConnectionError() {
    return $this->_connection_error_str;
  }
  
  /**
   * @return string The body of the response.
   */
  function getResponseBody() {
    return $this->_body;
  }
  
  /**
   * @return array An array representation of the xml data as 
   * array('xmlroot' => the root element name of the data,
   * 			 'xmldata' => the parsed xml), as returned by {@link XmlParser}
   */
  function getParsedXmlResponseBody() {
    $xmlp = new XmlParser($this->_body);
    return array('xmlroot'=>$xmlp->GetRoot(), 'xmldata'=>$xmlp->GetData());
  }
  
  /**
   * @return string The token
   */
  function getParsedToken() {
    $split_str = @split('=', $this->_body);
    $token = @trim($split_str[1]);
    return $token;
  }
}

/**
 * 
 */
class GoogleBaseHttpRequest {
  /** @var resource curl handler */
  var $ch;
  var $proxy = null;
  var $token = null;
  var $devKey = null;
  var $contentType = null;
  var $postFields = null;
  var $url;
  var $timeout = 30;
  var $method = 'post';
  
  /**
   * @param string $proxy The proxy to use in the format 'http://host:port', or 
   * null for no proxy use. Defaults to null.
   */
  function GoogleBaseHttpRequest($proxy=null) {
    $this->ch = curl_init();
    $this->proxy = $proxy;
  }
  
  /**
   * @param string $method One of 'post', 'put', 'get' or 'delete'
   */
  function setHttpMethod($method) {
    $this->method = $method;
    return $this;
  }
  
  /**
   * @param string $token
   */
  function setAuthorizationToken($token) {
    $this->token = $token;
    return $this;
  }
  
  /**
   * @param string $key
   */
  function setDeveloperKey($key) {
    $this->devKey = $key;
    return $this;
  }
  
  /**
   * @param string $url
   */
  function setUrl($url) {
    $this->url = $url;
    return $this;
  }
  
  /**
   * @param string $contentType
   */
  function setContentType($contentType) {
    $this->contentType = $contentType;
    return $this;
  }
  
  /**
   * @param string $content
   */
  function setPostFields($content) {
    $this->postFields = $content;
    return $this;
  }
  
  /**
   * @param string $proxy The proxy to use in the format 'http://host:port'
   */
  function setProxy($proxy) {
    $this->proxy = $proxy;
    return $this;
  }
  
  /**
   * @param int $timeout
   */
  function setTimeout($timeout) {
    $this->timeout = $timeout;
    return $this;
  }
  
  /**
   * Executes the http request.
   * 
   * @return GoogleBaseHttpResponse
   */
  function execute() {
    curl_setopt($this->ch, CURLOPT_URL, $this->url);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->ch, CURLOPT_FAILONERROR, false);
    curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
    
    $headers = array();
    if($this->token != null)
      $headers[] = 'Authorization: AuthSub token=' . trim($this->token);
    if($this->devKey != null)
      $headers[] = 'X-Google-Key: key=' . trim($this->devKey);
    if($this->contentType != null)
      $headers[] = 'Content-Type: ' . $this->contentType;
    
    if($this->proxy != null)
      curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
      
    switch(strtolower($this->method)) {
      case 'post':
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->postFields);
        break;
      case 'put':
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->postFields);
        $headers[] = 'X-HTTP-Method-Override: PUT';
        break;
      case 'delete':
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, '');
        $headers[] = 'X-HTTP-Method-Override: DELETE';
        break;
      default: // 'get'
        break;
    }
    
    if(!empty($headers)) 
      curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
  
    $response_body = curl_exec($this->ch);
    $status_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    
    return new GoogleBaseHttpResponse($status_code, $response_body,
                                      curl_errno($this->ch),
                                      curl_error($this->ch));
  }
  
  /**
   * Closes the connection. This objects shouldn't be used again.
   */
  function close() {
    curl_close($this->ch);
  }
}

?>
