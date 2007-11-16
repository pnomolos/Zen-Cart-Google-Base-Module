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
 * created Aug 31, 2007
 *
 * @author pablif@gmail.com
 */
 
 /*
function getCurrentUrl()
{
    global $_SERVER;

    // Filter php_self to avoid a security vulnerability.
    $php_request_uri = htmlentities(substr($_SERVER['REQUEST_URI'], 0, strcspn($_SERVER['REQUEST_URI'], "\n\r")), ENT_QUOTES);

    if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    $host = $_SERVER['HTTP_HOST'];
    if ($_SERVER['HTTP_PORT'] != '' &&
        (($protocol == 'http://' && $_SERVER['HTTP_PORT'] != '80') ||
        ($protocol == 'https://' && $_SERVER['HTTP_PORT'] != '443'))) {
        $port = ':' . $_SERVER['HTTP_PORT'];
    } else {
        $port = '';
    }
    return $protocol . $host . $port . $php_request_uri;
}
*/
  
require_once('gb-http.php');

function gb_get_authentication_url($next_url=null) {
  if($next_url === null) {
    $next_url  = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  }
  $redirect_url = 'https://www.google.com/accounts/AuthSubRequest?session=1';
  $redirect_url .= '&next=';
  $redirect_url .= urlencode($next_url);
  $redirect_url .= "&scope=";
  $redirect_url .= urlencode('http://www.google.com/base/feeds/items');
  
  return $redirect_url;
}

/**
 * 
 */
function gb_get_session_token($token, $gbhttp=null) {
  if($gbhttp == null) {
    $gbhttp = new GoogleBaseHttpRequest();
  }
  
  $gbhttp->setUrl("https://www.google.com/accounts/AuthSubSessionToken");
  $gbhttp->setAuthorizationToken($token);
  $gbhttp->setHttpMethod('get');

  return $gbhttp->execute();
}

function gb_revoke_session_token($token, $gbhttp=null) {
  if($gbhttp == null) {
    $gbhttp = new GoogleBaseHttpRequest();
  }
  
  $gbhttp->setUrl("https://www.google.com/accounts/AuthSubRevokeToken");
  $gbhttp->setAuthorizationToken($token);
  $gbhttp->setHttpMethod('get');

  $response = $gbhttp->execute();
}

// TODO: implement AuthSubTokenInfo

?>