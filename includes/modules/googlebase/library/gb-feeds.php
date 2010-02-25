<?php
/*
 * Copyright (C) 2006 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *  http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
 
/**
 * @author pablif@gmail.com
 * @version $Id: gb-feeds.php 0 Sep 25, 2007 1:33:44 PM pablif@gmail.com $
 */
 
/**
 *  Convert timestamp into RFC 3339 date string.
 *  2005-04-19T15:30:00
 *
 * @param int $timestamp
 */
function format_timestamp($timestamp) {
  $rfc3339 = '/^(\d{4})\-?(\d{2})\-?(\d{2})((T|t)(\d{2})\:?(\d{2})' .
             '\:?(\d{2})(\.\d{1,})?((Z|z)|([\+\-])(\d{2})\:?(\d{2})))?$/';

  if(ctype_digit($timestamp)) {
    return gmdate('Y-m-d\TH:i:sP', $timestamp);
  } elseif(preg_match($rfc3339, $timestamp) > 0) {
    // timestamp is already properly formatted
    return $timestamp;
  } else {
    $ts = strtotime($timestamp);
    if($ts === false) {
      return null;
    }
    return date('Y-m-d\TH:i:s', $ts);
  }
}

define('GBASE_ITEM_FEED_URL', 'http://www.google.com/base/feeds/items');
define('GBASE_SNIPPET_FEED_URL', 'http://www.google.com/base/feeds/snippets');

class GoogleBaseQuery {
  var $_params;
  var $_url = GBASE_ITEM_FEED_URL;
  

  function GoogleBaseQuery($url=null) {
    $this->_params = array();
    if($url !== null) {
      $this->_url = $url;
    }
  }
  
  /**
   * @param string $url
   */
  function setUrl($url) {
    $this->_url = $url;
    return $this;
  }
  
  /*
   * @param string $alt
  function setAlt($alt) {
    if($alt!= null) {
      $this->_params['alt'] = $alt;
    } else {
      unset($this->_params['alt']);
    }
    return $this;
  }
   */

  /**
   * @param int $maxResults
   */
  function setMaxResults($maxResults) {
    if($maxResults != null) {
      $this->_params['max-results'] = $maxResults;
    } else {
      unset($this->_params['max-results']);
    }
    return $this;
  }

  /**
   * @param string $query
   */
  function setQuery($query) {
    if($query != null) {
      $this->_params['q'] = $query;
    } else {
      unset($this->_params['q']);
    }
    return $this;
  }

  /**
   * @param int $startIndex
   */
  function setStartIndex($startIndex) {
    if($startIndex != null) {
      $this->_params['start-index'] = $startIndex;
    } else {
      unset($this->_params['start-index']);
    }
    return $this;
  }

  /**
   * @param string $updatedMax
   */
  function setUpdatedMax($updatedMax) {
    if($updatedMax != null) {
      $this->_params['updated-max'] = format_timestamp($updatedMax);
    } else {
      unset($this->_params['updated-max']);
    }
    return $this;
  }

  /**
   * @param string $updatedMin 
   */
  function setUpdatedMin($updatedMin) {
    if($updatedMin != null) {
      $this->_params['updated-min'] = format_timestamp($updatedMin);
    } else {
      unset($this->_params['updated-min']);
    }
    return $this;
  }

  /**
   * @param string $publishedMax
   */
  function setPublishedMax($publishedMax) {
    if($publishedMax !== null) {
      $this->_params['published-max'] = format_timestamp($publishedMax);
    } else {
      unset($this->_params['published-max']);
    }
    return $this;
  }

  /**
   * @param string $publishedMin
   */
  function setPublishedMin($publishedMin) {
    if($publishedMin != null) {
      $this->_params['published-min'] = format_timestamp($publishedMin);
    } else {
      unset($this->_params['published-min']);
    }
    return $this;
  }

  /**
   * @param string $author
   */
  function setAuthor($author) {
    if($author != null) {
      $this->_params['author'] = $author;
    } else {
      unset($this->_params['author']);
    }
    return $this;
  }
  
  /**
   * @param string $key
   */
  function setKey($key) {
    if($key !== null) {
      $this->_params['key'] = $key;
    } else {
      unset($this->_params['key']);
    }
    return $this;
  }

  /**
   * @param string $bq
   */
  function setBq($bq) {
    if($bq !== null) {
      $this->_params['bq'] = $bq;
    } else {
      unset($this->_params['bq']);
    }
    return $this;
  }

  /**
   * @param string $refine
   */
  function setRefine($refine) {
    if($refine !== null) {
      $this->_params['refine'] = $refine;
    } else {
      unset($this->_params['refine']);
    }
    return $this;
  }

  /**
   * @param string $content
   */
  function setContent($content) {
    if($content !== null) {
      $this->_params['content'] = $content;
    } else {
      unset($this->_params['content']);
    }
    return $this;
  }

  /**
   * @param string $orderBy
   */
  function setOrderBy($orderBy) {
    if($orderBy !== null) {
      $this->_params['orderby'] = $orderBy;
    } else {
      unset($this->_params['orderby']);
    }
    return $this;
  }

  /**
   * @param string $sortOrder
   */
  function setSortOrder($sortOrder) {
    if($sortOrder !== null) {
      $this->_params['sortorder'] = $sortOrder;
    } else {
      unset($this->_params['sortorder']);
    }
    return $this;
  }


  /**
   * @param string $crowdBy
   */
  function setCrowdBy($crowdBy) {
    if($crowdBy !== null) {
      $this->_params['crowdby'] = $crowdBy;
    } else {
      unset($this->_params['crowdby']);
    }
    return $this;
  }

  /**
   * @param string $adjust
   */
  function setAdjust($adjust) {
    if($adjust !== null) {
      $this->_params['adjust'] = $adjust;
    } else {
      unset($this->_params['adjust']);
    }
    return $this;
  }
  
  /**
   * @param string $category
   */
  function setCategory($category) {
    if($category !== null) {
      $this->_params['category'] = $category;
    } else {
      unset($this->_params['category']);
    }
    return $this;
  }
  
  /**
   * @param string $name
   * @param string $value
   */
   function setParam($name, $value) {
     $this->_params[$name] = $value;
     return $this;
   }
  
  /**
   * @return string url
   */
  function getUrl() {
    return $this->_url;
  }
  
  /*
   * @return string rss or atom
  function getAlt() {
    if(array_key_exists('alt', $this->_params)) {
      return $this->_params['alt'];
    } else {
      return null;
    }
  }
   */

  /**
   * @return int maxResults
   */
  function getMaxResults() {
    if(array_key_exists('max-results', $this->_params)) {
      return intval($this->_params['max-results']);
    } else {
      return null;
    }
  }

  /**
   * @return string query
   */
  function getQuery() {
    if(array_key_exists('q', $this->_params)) {
      return $this->_params['q'];
    } else {
      return null;
    }
  }

  /**
   * @return int startIndex
   */
  function getStartIndex() {
    if(array_key_exists('start-index', $this->_params)) {
      return intval($this->_params['start-index']);
    } else {
      return null;
    }
  }

  /**
   * @return string updatedMax
   */
  function getUpdatedMax() {
    if(array_key_exists('updated-max', $this->_params)) {
      return $this->_params['updated-max'];
    } else {
      return null;
    }
  }

  /**
   * @return string updatedMin
   */
  function getUpdatedMin() {
    if(array_key_exists('updated-min', $this->_params)) {
      return $this->_params['updated-min'];
    } else {
      return null;
    }
  }

  /**
   * @return string publishedMax
   */
  function getPublishedMax() {
    if(array_key_exists('published-max', $this->_params)) {
      return $this->_params['published-max'];
    } else {
      return null;
    }
  }

  /**
   * @return string publishedMin
   */
  function getPublishedMin() {
    if(array_key_exists('published-min', $this->_params)) {
      return $this->_params['published-min'];
    } else {
      return null;
    }
  }

  /**
   * @return string author
   */
  function getAuthor() {
    if(array_key_exists('author', $this->_params)) {
      return $this->_params['author'];
    } else {
      return null;
    }
  }

  /**
   * @return string key
   */
  function getKey() {
    if(array_key_exists('key', $this->_params)) {
      return $this->_params['key'];
    } else {
      return null;
    }
  }

  /**
   * @return string bq
   */
  function getBq() {
    if(array_key_exists('bq', $this->_params)) {
      return $this->_params['bq'];
    } else {
      return null;
    }
  }

  /**
   * @return string refine
   */
  function getRefine() {
    if(array_key_exists('refine', $this->_params)) {
      return $this->_params['refine'];
    } else {
      return null;
    }
  }

  /**
   * @return string content
   */
  function getContent() {
    if(array_key_exists('content', $this->_params)) {
      return $this->_params['content'];
    } else {
      return null;
    }
  }

  /**
   * @return string orderby
   */
  function getOrderBy() {
    if(array_key_exists('orderby', $this->_params)) {
      return $this->_params['orderby'];
    } else {
      return null;
    }
  }

  /**
   * @return string sortorder
   */
  function getSortOrder() {
    if(array_key_exists('sortorder', $this->_params)) {
      return $this->_params['sortorder'];
    } else {
      return null;
    }
  }

  /**
   * @return string crowdby
   */
  function getCrowdBy() {
    if(array_key_exists('crowdby', $this->_params)) {
      return $this->_params['crowdby'];
    } else {
      return null;
    }
  }

  /**
   * @return string adjust
   */
  function getAdjust() {
    if(array_key_exists('adjust', $this->_params)) {
      return $this->_params['adjust'];
    } else {
      return null;
    }
  }
  
  /**
   * @return string category
   */
  function getCategory() {
    if(array_key_exists('category', $this->_params)) {
      return $this->_params['category'];
    } else {
      return null;
    }
  }
  
  /**
   * @param string $name
   */
  function getParam($name) {
    return $this->_params[$name];
  }
  
  /**
   * @access private
   * @return string querystring
   */
  function _getQueryString() {
    $queryArray = array();
    foreach($this->_params as $name => $value) {
      if(substr($name, 0, 1) == '_') {
        continue;
      }
      $queryArray[] = urlencode($name) . '=' . urlencode($value);
    }
    if(count($queryArray) > 0) {
      return '?' . implode('&', $queryArray);
    } else {
      return '';
    }
  }

  /**
   *
   */
  function resetParameters() {
    $this->_params = array();
  }

  /**
   * @return string url
   */
  function getQueryUrl() {
    return $this->_url . $this->_getQueryString();
  }

}

require_once('gb-http.php');
class GoogleBaseFeed {
  var $_gbhttp;
  var $_response;
  var $_entries = array();
  
  function GoogleBaseFeed($gbhttp=null) {
    if($gbhttp === null) {
      $this->_gbhttp = new GoogleBaseHttpRequest();
    } else {
      $this->_gbhttp = $gbhttp;
    }
  }
  
  /**
   * @return GoogleBaseHttpResponse
   */
  function getResponse() {
    return $this->_response;
  }
  
  /**
   * Get a single item.
   * 
   * @param string $id The Google id of the item.
   * 
   * @return GoogleBaseItem
   */
  function getItem($id, $token=null) {
    $this->_gbhttp->setAuthorizationToken($token);
    $this->_gbhttp->setUrl($id);
    $this->_gbhttp->setHttpMethod('get');
    $this->_response = $this->_gbhttp->execute();
    if(!$this->_response->hasErrors()) {
      $pxml = $this->_response->getParsedXmlResponseBody();
      $item = new GoogleBaseItem();
      $item->takeFromArray(@$pxml['xmldata']['entry']);
      return $item;
    } else {
      return null;
    }
    
  }
  
  /**
   * @param mixed $location The location of the feed, as a url or a
   * {@link GoogleBaseQuery}. Defaults to null, using the default snippets feed
   * url.
   * 
   * @return GoogleBaseHttpResponse
   */
  function querySnippetsFeed($location=null) {
    if($location === null) {
      $url = GBASE_SNIPPET_FEED_URL;
    } elseif(get_class($location) == strtolower('GoogleBaseQuery') ||
             is_subclass_of($location, 'GoogleBaseQuery')) {
      $url = $location->getQueryUrl();
    } else {
      $url = $location;
    }
    
    $this->_gbhttp->setAuthorizationToken(null);
    $this->_gbhttp->setUrl($url);
    $this->_gbhttp->setHttpMethod('get');
    $this->_response = $this->_gbhttp->execute();
    $this->_parseEntries();
    return $this->_response;
  }
  
  /**
   * @param string $token
   * @param mixed $location The location of the feed, as a url or a
   * {@link GoogleBaseQuery}. Defaults to null, using the default customer
   * items feed url.
   * 
   * @return GoogleBaseHttpResponse
   */
  function queryCustomerItemsFeed($token, $location=null) {
    if($location === null) {
      $url = GBASE_ITEM_FEED_URL;
    } elseif(get_class($location) == strtolower('GoogleBaseQuery') ||
             is_subclass_of($location, 'GoogleBaseQuery')) {
      $url = $location->getQueryUrl();
    } else {
      $url = $location;
    }
    
    $this->_gbhttp->setAuthorizationToken($token);
    $this->_gbhttp->setUrl($url);
    $this->_gbhttp->setHttpMethod('get');
    $this->_response = $this->_gbhttp->execute();
    $this->_parseEntries();
    return $this->_response;
  }
  
  /**
   * @param GoogleBaseQuery $query
   * @param string $token
   * 
   * @return GoogleBaseHttpResponse
   */
  function queryFeed($query, $token=null) {
    $this->_gbhttp->setAuthorizationToken($token);
    $this->_gbhttp->setUrl($query->getQueryUrl());
    $this->_gbhttp->setHttpMethod('get');
    $this->_response = $this->_gbhttp->execute();
    $this->_parseEntries();
    return $this->_response;
  }
  
  /**
   * 
   */
  function _parseEntries() {
    if($this->_response->hasErrors()) {
      return;
    }
    
    $pxml = $this->_response->getParsedXmlResponseBody();
    /*
    $this->_updated = $pxml['xmldata']['feed']['updated']['VALUE'];
    $this->_title = $pxml['xmldata']['feed']['title']['VALUE'];
    */
    $totalResults = (int)@$pxml['xmldata']['feed']['openSearch:totalResults']['VALUE'];
    if($totalResults != 0) {
      if($totalResults == 1) {
        $entries = array($pxml['xmldata']['feed']['entry']);
      } else {
        $entries = @$pxml['xmldata']['feed']['entry'];
      }
      
      foreach($entries as $entry) {
        $item = new GoogleBaseItem();
        $item->takeFromArray($entry);
        $this->_entries[] = $item;
      }
    }
  }
  
  /**
   * @return array of {@link GoogleBaseItem}
   */
  function getEntries() {
    return $this->_entries;
  }
}
?>
