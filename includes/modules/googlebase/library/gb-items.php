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
 
require_once('xml-processing/gb-xmlbuilder.php');
require_once('xml-processing/gb-xmlparser.php');

/**
 * Class that represents an xml element
 */
class BaseElement {
  /** @var string */
  var $_name;
  /** @var string */
  var $_value;
  /** @var array */
  var $_attributes;
  /** @var array */
  var $_children;
  
  // TODO: better handling of namespaces
  /** @var string */
  var $_rootNamespace = ''; 
  
  /**
   * @param string $name The name of the element.
   * @param string $value The value, or an empty string if it's an empty tag.
   * @param array $attributes (optional) An array with the tag attributes in the
   * form of 'attribute_name' => 'attribute_value'.
   * @param array $children (optional) An array of {@link BaseElement}s,
   * children of this attribute.
   */
  function BaseElement($name, $value, $attributes=null, $children=null) {
    $this->_name = $name;
    $this->_value = (string)$value;
    $this->_attributes = is_array($attributes)? $attributes : array();
    $this->_children = $children;
  }
  
  /**
   * @return string The element's name
   */
  function getName() {
    return $this->_name;
  }
  
  /**
   * @param string $name The element's name
   */
  function setName($name) {
    $this->_name = $name;
  }
  
  /**
   * @return string The element's value
   */
  function getValue() {
    return $this->_value;
  }
  
  /**
   * @param string $value The element's value
   */
  function setValue($value) {
    $this->_value = $value;
  }
  
  /**
   * @return string The element's namespace prefix
   */
  function getNamespace() {
    return $this->_rootNamespace;
  }
  
  /**
   * @param string nsPrefix
   */
   function setNamespace($nsPrefix) {
     $this->_rootNamespace = $nsPrefix;
   }
  
  /**
   * Add a child element.
   * 
   * @param mixed $element An instance of the {@link BaseElement} hierarchy
   */
  function addChild($element) {
    $this->_children[] = $element;
  }
  
  /**
   * Add an attribute to this element.
   * 
   * @param string $name The name of the attribute
   * @param string $value The value of the attribute
   */
  function addAttribute($name, $value) {
    $this->_attributes[$name] = $value;
  }
  
  /**
   * Remove an attribute
   * 
   * @param string $name The name of the attribute to remove
   */
  function removeAttribute($name) {
    if(array_key_exists($name, $this->_attributes))
      unset($this->_attributes[$name]);
  }
  
  /**
   * Get the value of an attribute
   * 
   * @param string $name The name of the attribute
   */
  function getAttributeValue($name) {
    if(array_key_exists($name, $this->_attributes)) {
      return $this->_attributes[$name];
    } else {
      return null;
    }
  }
  
  /**
   * @param XmlBuilder $xml
   */
  function addToXml(&$xml) {
    // TODO: htmlspecialchars should go here?
    $name = empty($this->_rootNamespace)? 
                $this->_name : $this->_rootNamespace . ':' . $this->_name;
    $value = htmlspecialchars($this->_value);
    if($this->_children == null) {
      if($value != '') {
        $xml->Element($name, $value, $this->_attributes);
      } else {
        $xml->EmptyElement($name, $this->_attributes);
      }
    } else {
      $xml->Push($name, $this->_attributes);
      
      if($value != '')
        $xml->Append($value);
        
      foreach($this->_children as $child) {
        $child->addToXml($xml);
      }
      
      $xml->Pop($name);
    }
  }
}

/**
 * Class that represents a Google Base attribute
 * 
 * info on attributes:
 * {@link http://base.google.com/base/api/itemTypeDocs}
 */
class GoogleBaseAttribute extends BaseElement {
  var $_rootNamespace = 'g';
  
  /**
   * @param string $name The name of the attribute
   * @param string $value The value of the attribute
   * @param string $type (optional) The attribute type
   * @param boolean $private True if the attribute should remain private.
   * Defaults to false.
   */
  function GoogleBaseAttribute($name, $value, $type=null, $private=false) {
    $attributes = array();
    if($type != null)
      $attributes['type'] = $type;
    if($private)
      $attributes['access'] = 'private';
      
    parent::BaseElement($name, $value, $attributes);
  }
  
  /**
   * @return string The attribute type
   */
  function getType() {
    return $this->getAttributeValue('type');
  }
  
  /**
   * @param string $type The attribute type
   */
  function setType($type) {
    $this->addAttribute('type', $type);
  }
  
  /**
   * @return boolean True if the attribute is marked as private
   */
  function isPrivate() {
    return $this->getAttributeValue('access') === 'private';
  }
  
  /**
   * @param boolean $private True if the attribute should remain private.
   */
  function setPrivate($private) {
    if($private) {
      $this->addAttribute('access', 'private');
    } else {
      $this->removeAttribute('access');
    }
  }
}

/**
 * Class that represents a Google Base batch attribute.
 * 
 * info on batch processing:
 * {@link http://code.google.com/apis/gdata/batch.html}
 */
class GoogleBaseBatchAttribute extends GoogleBaseAttribute {
  var $_rootNamespace = 'batch';
  
  /**
   * @param string $name The name of the attribute
   * @param string $value The value of the attribute
   * @param string $type (optional) The attribute type
   */
  function GoogleBaseBatchAttribute($name, $value, $type=null) {
    parent::GoogleBaseAttribute($name, $value, $type, false);
  }
}

/**
 * Class that represents a Google Base entry item.
 */
class GoogleBaseItem {
  /** @var string */
  var $_id;
  /** @var boolean */
  var $_draft;
  /** 'true' or 'false' @var string */
  var $_dryrun = 'false';
  /** @var BaseElement */
  var $_author;
  /** @var BaseElement */
  var $_title;
  /** @var array */
  var $_links = array();
  /** @var BaseElement */
  var $_description;
  /** @var string */
  var $_published;
  /** @var string */
  var $_updated;
  /** @var BaseElement */
  var $_category;
  /** @var array */
  var $_attributes;
  
  /**
   * @param string $id The id of the item or null for a new item. Defaults
   * to null.
   */
  function GoogleBaseItem($id=null) {
    $this->_id = $id;
    $this->_attributes = array();
  }
  
  /**
   * @param mixed $attr An instance from the {@link BaseElement} hierarchy
   */
  function addAttribute($attr) {
    $this->_attributes[] = $attr;
  }
  
  /**
   * @param string $name
   * @param string $value
   * @param string $type (optional)
   * @param boolean $private
   */
  function addGbaseAttribute($name, $value, $type=null, $private=false) {
    $this->_attributes[] = new GoogleBaseAttribute($name, $value, $type,
                                                   $private);
  }
  
  /**
   * @param string $name
   * 
   * @return array of {@link GoogleBaseAttribute}s
   */
  function getGbaseAttribute($name) {
    $matches = array();
    for ($i=0; $i < count($this->_attributes); $i++) {
      if($this->_attributes[$i]->getName() == $name &&
         $this->_attributes[$i]->getNamespace() == 'g') {
        $matches[] = &$this->_attributes[$i];
      }
    }
    return $matches;
  }
  
  /**
   * @return array of {@link GoogleBaseItem}s
   */
  function getGbaseAttributes() {
    return $this->_attributes;
  }
  
  /**
   * @param mixed $attr An instance of {@link GoogleBaseAttribute} or
   * {@link GoogleBaseBatchAttribute}
   */
   function removeGbaseAttribute($attr) {
     $attributes = $this->_attributes;
     for ($i = 0; $i < count($this->_attributes); $i++) {
       if ($this->_attributes[$i] == $attr) {
         array_splice($attributes, $i, 1);
         break;
       }
     }
     $this->_attributes = $attributes;
   }
  
  /**
   * @param string $name The name of the author.
   * @param string $email The email of the author.
   */
  function setAuthor($name=null, $email=null) {
    if($name != null || $email != null) {
      $children = array();
      if($name != null)
        $children[] = new BaseElement('name', $name);
      if($email != null)
        $children[] = new BaseElement('email', $email);
      $this->_author = new BaseElement('author', '', null, $children);
    }
  }
  
  /**
   * @return BaseElement
   */
  function getAuthor() {
    return $this->_author;
  }
  
  /**
   * @param string $title The title of the item.
   * @param string $type (optional) The content type of the title.
   */
  function setTitle($title, $type=null) {
    $this->_title = new BaseElement('title', $title);
    if($type != null)
      $this->_title->addAttribute('type', $type);
  }
  
  /**
   * @return BaseElement
   */
  function getTitle() {
    return $this->_title;
  }
  
  /**
   * @param string $url The url of the item.
   * @param string $rel The link rel attribute.
   */
  function setLink($url, $rel='alternate', $type=null) {
    $this->_links[$rel] = new BaseElement('link', '',
                                   array('rel'=>$rel,
                                         'href'=>$url));
    if($type !== null) {
      $this->_links[$rel]->addAttribute('type', $type);
    }
  }
  
  /**
   * @return BaseElement
   */
  function getLink($rel='alternate') {
    return @$this->_links[$rel];
  }
  
  /**
   * @return array {@link BaseElement}
   */
  function getLinks() {
    return $this->_links;
  }
  
  /**
   * @param string $description The description of the item.
   * @param string $type (optional) The content type of the description.
   */
  function setDescription($description, $type=null) {
    $this->_description = new BaseElement('content',
                                          $description);
    if($type != null)
      $this->_description->addAttribute('type', $type);
  }
  
  /**
   * @return string
   */
  function getPublished() {
    return $this->_published;
  }
  
  /**
   * @param string $published
   */
  function setPublished($published) {
    $this->_published = $published;
  }
  
  /**
   * @return string
   */
  function getUpdated() {
    return $this->_updated;
  }
  
  /**
   * @param string $updated
   */
  function setUpdated($updated) {
    $this->_updated = $updated;
  }
  
  /**
   * @return BaseElement
   */
  function getCategory() {
    return $this->_category;
  }
  
  /**
   * @param string $term
   * @param string $scheme 
   */
  function setCategory($term, $scheme=null) {
    $this->_category = new BaseElement('category', '', array('term'=>$term));
    if($scheme != null) {
      $this->_category->addAttribute('scheme', $scheme);
    } else {
      $this->_category->addAttribute('scheme',
          'http://base.google.com/categories/itemtypes');
    }
  }
  
  /**
   * @return BaseElement
   */
  function getDescription() {
    return $this->_description;
  }
  
  /**
   * @param boolean $status true to post the item as a draft, false to publish 
   * it. Defaults to false.
   */
  function setDraftStatus($status=false) {
    $this->_draft = (bool)$status;
  }
  
  /**
   * @return boolean
   */
  function getDraftStatus() {
    return $this->_draft;
  }
  
  /**
   * @param boolean $status true to send as dry-run, false otherwise. 
   * Defaults to false.
   */
  function setDryRunStatus($status=false) {
    $this->_dryrun = $status? 'true':'false';
  }
  
  /**
   * @param string $id The id of the item.
   */
  function setId($id) {
    $this->_id = $id;
  }
  
  /**
   * @return string The id of the item
   */
  function getId() {
    return $this->_id;
  }
  
  /**
   * @param boolean $batch true to build xml suitable to append to a batch xml 
   * request, false to build an xml to post this single item. Defaults to 
   * false.
   * @return string The rendered xml for this item.
   */
  function getXml($batch=false) {
    $xml = $batch? new XmlBuilder('') : new XmlBuilder();
    $entry_attrs = $batch? array() : 
                           array('xmlns' => 'http://www.w3.org/2005/Atom',
                                 'xmlns:g' => 'http://base.google.com/ns/1.0');
                                 
    $xml->Push('entry', $entry_attrs);

    if($this->_id) {
      $xml->Element('id', $this->_id);
    }
    if($this->_author) $this->_author->addToXml($xml);
    $this->_title->addToXml($xml);
    $this->_description->addToXml($xml);
    foreach($this->_links as $link) {
      $link->addToXml($xml);
    }
    
    foreach($this->_attributes as $attr) {
      $attr->addToXml($xml);
    }
    
    if($this->_draft) {
      // <app:control> <app:draft>yes</app:draft> </app:control>
      $xml->Push('app:control',
                 array('xmlns:app' => 'http://purl.org/atom/app#'));
      $xml->Element('app:draft', 'yes');
      $xml->Pop('app:control');
    }
    
    $xml->Pop('entry');
    
    return $xml->GetXML();
  }
  
  /**
   * @param array $entryArr
   */
  function takeFromArray($e, $batch=false) {
    $p = $batch? 'atom:':''; // if batch all atom ns elements are qualified, ugly
    $this->setId(@$e[$p.'id']['VALUE']);
    $this->setAuthor(@$e[$p.'author'][$p.'name']['VALUE'],
                     @$e[$p.'author'][$p.'email']['VALUE']);
    $this->setTitle(@$e[$p.'title']['VALUE'], @$e[$p.'title']['type']);
    $this->setDescription(@$e[$p.'content']['VALUE'], @$e[$p.'content']['type']);
    $this->setPublished(@$e[$p.'published']['VALUE']);
    $this->setUpdated(@$e[$p.'updated']['VALUE']);
    
    // TODO: ugly
    $links = array();
    if(isset($e[$p.'link'][0])) {
      $links = @$e[$p.'link'];
    } else if(isset($e[$p.'link'])){
      $links = array($e[$p.'link']);
    }
    
    foreach($links as $link) {
      $this->setLink($link['href'], $link['rel'], $link['type']);
    }
    
    if(@$e['app:control']['app:draft']['VALUE'] === 'yes') {
      $this->setDraftStatus(true);
    }
    
    foreach($e as $name => $val) {
      if(substr($name, 0, 2) == 'g:') { // ugly ugly
        $name = substr($name, 2);
        // TODO: fix this to take into account elements with value and children
        if(array_key_exists('VALUE', $val)) {
          $this->addGbaseAttribute($name, $val['VALUE'], $val['type'],
                                   @$val['access']==='private');
        } else {
          foreach($val as $v) {
            $this->addGbaseAttribute($name, $v['VALUE'], $v['type'],
                                     @$v['access']==='private');
          }
        }
      }
    }
  }
  
  /**
   * Save this item in Google Base.
   * If this item has an id it will be updated, otherwise it will be inserted.
   * 
   * @param string $token The authentication token.
   * @param GoogleBaseHttpRequest $gbhttp An instance of {@link
   * GoogleBaseHttpRequest} with any custom options set. If set to null an 
   * instance with the default options will be created. Defaults to null.
   * 
   * @return GoogleBaseHttpResponse
   */
  function save($token, $gbhttp=null) {
    if($gbhttp == null) {
      $gbhttp = new GoogleBaseHttpRequest();
    }
    
    if($this->_id) { // update an item
      $gbhttp->setUrl($this->_id);
      $gbhttp->setHttpMethod('put');
    } else { // post a new item
      $gbhttp->setUrl(
          "http://www.google.com/base/feeds/items?dry-run={$this->_dryrun}");
      $gbhttp->setHttpMethod('post');
    }
    $gbhttp->setAuthorizationToken($token);
    $gbhttp->setContentType('application/atom+xml');
    $gbhttp->setPostFields($this->getXml());
  
    return $gbhttp->execute();
  }
  
  /**
   * Delete this item from Google Base.
   * 
   * @param string $token The authentication token.
   * @param GoogleBaseHttpRequest $gbhttp An instance of {@link
   * GoogleBaseHttpRequest} with any custom options set. If set to null an 
   * instance with the default options will be created. Defaults to null.
   */
  function delete($token, $gbhttp=null) {
    if($gbhttp == null) {
      $gbhttp = new GoogleBaseHttpRequest();
    }
    // TODO: get actual edit url, don't assume id
    $editUrl = $this->_id;
    if($this->_dryrun == 'true') {
      $editUrl .= "?dry-run={$this->_dryrun}";
    }
    $gbhttp->setUrl($editUrl);
    $gbhttp->setAuthorizationToken($token);
    $gbhttp->setHttpMethod('delete');
  
    return $gbhttp->execute();
  }
}

class GoogleBaseBatchRequest {
  /** var array */
  var $_insert = array();
  /** var array */
  var $_delete = array();
  /** var array */
  var $_update = array();
  /** var BaseElement */
  var $_title;
  
  var $_dryrun;
  
  /**
   * @param boolean $dryrun True to perform the request as dry-run (nothing is
   * actually posted to gbase). Defaults to false.
   */
  function GoogleBaseBatchRequest($dryrun=false) {
    $this->_dryrun = $dryrun? 'true':'false';
    $this->_title = new BaseElement('content', 'Google Base batch request');
  }
  
  /**
   * @param string $content The title of this batch request.
   * @param string $type The content type of the title.
   */
  function setTitle($title, $type=null) {
    $this->_title = new BaseElement('content', $title, $type);
    if($type != null)
      $this->_title->addAttribute('type', $type);
  }
  
  /**
   * Adds an item for insertion.
   * 
   * @param GoogleBaseItem $item The item to be inserted.
   */
  function addItemForInsert($item, $batch_id=null) {
    if($batch_id != null) {
      $this->_insert[$batch_id] = $item;
    } else {
      $this->_insert[] = $item;
    }
  }
  
  /**
   * Adds an item for deletion.
   * 
   * @param mixed $i A string with the id of the item or a
   * {@link GoogleBaseItem} with its id set.
   */
  function addItemForDelete($i) {
    $item = is_a($i, 'GoogleBaseItem')? $i : new GoogleBaseItem($id=$i);
    $this->_delete[$item->getId()] = $item;
  }
  
  /**
   * Adds an item for update.
   * 
   * @param GoogleBaseItem $item The item to be updated.
   */
  function addItemForUpdate($item) {
    $this->_update[$item->getId()] = $item;
  }
  
  /**
   * @return string The rendered xml for this batch.
   */
  function getXml() {
    $xml = new XmlBuilder();
    $feed_attrs = array('xmlns'=>'http://www.w3.org/2005/Atom',
    'xmlns:openSearch'=>'http://a9.com/-/spec/opensearchrss/1.0/', //Gnot using
                        'xmlns:g'=>'http://base.google.com/ns/1.0',
                        'xmlns:batch'=>'http://schemas.google.com/gdata/batch');
    $xml->Push('feed', $feed_attrs);
    
    $this->_title->addToXml($xml);
    
    foreach($this->_insert as $id => $item) {
      $item->addAttribute(new GoogleBaseBatchAttribute('id', $id));
      $item->addAttribute(
          new GoogleBaseBatchAttribute('operation', '', 'insert'));
      $xml->Append($item->getXml($batch=true));
    }
    
    foreach($this->_delete as $id => $item) {
      $item->addAttribute(
          new GoogleBaseBatchAttribute('operation', '', 'delete'));
      $xml->Append($item->getXml($batch=true));
    }
    
    foreach($this->_update as $id => $item) {
      $item->addAttribute(
          new GoogleBaseBatchAttribute('operation', '', 'update'));
      $xml->Append($item->getXml($batch=true));
    }
    
    $xml->Pop('feed');
    return $xml->GetXML();
  }
  
  /**
   * Post this batch to Google Base.
   * 
   * @param string $token The authentication token.
   * @param GoogleBaseHttpRequest $gbhttp An instance of {@link
   * GoogleBaseHttpRequest} with any custom options set. If set to null an 
   * instance with the default options will be created. Defaults to null.
   * 
   * @return GoogleBaseHttpResponse
   */
  function post($token, $gbhttp=null) {
    if($gbhttp == null) {
      $gbhttp = new GoogleBaseHttpRequest();
    }
    
    $gbhttp->setUrl(
       "http://www.google.com/base/feeds/items/batch?dry-run={$this->_dryrun}");
    $gbhttp->setAuthorizationToken($token);
    $gbhttp->setContentType('application/atom+xml');
    $gbhttp->setHttpMethod('post');
    $gbhttp->setPostFields($this->getXml());
  
    return $gbhttp->execute();
  }
}
?>