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
 * @package admin
 * @version $Id: googlebase.php 0 Sep 11, 2007 10:50:29 AM pablif@gmail.com $
 */
 
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

require_once('library/gb-items.php');
require_once('library/gb-http.php');
require_once('library/xml-processing/gb-xmlparser.php');

class googlebase {
  var $_options;
  
  function googlebase() {
    global $db;
    $result = $db->Execute("select configuration_value from " . 
                           TABLE_CONFIGURATION . 
                           " where configuration_key='GOOGLEBASE_BULK_OPTIONS'");
    if(!$result->EOF) {
      $this->_options = unserialize(zen_db_prepare_input(
                            $result->fields['configuration_value']));
    } else {
      $this->_options = array();
    }
  }
  
  function getOption($opt) {
    return @$this->_options[$opt];
  }
  
  function setOption($name, $value, $saveToDb=true) {
    $this->_options[$name] = $value;
    if($saveToDb) {
      $this->saveOptions();
    }
  }
  
  function getOptions() {
    return $this->_options;
  }
  
  function setOptions($opts) {
    $this->_options = $opts;
  }
  
  function saveOptions() {
    global $db;
    $options = zen_db_input(serialize($this->_options));
    $db->Execute("update ".TABLE_CONFIGURATION . 
                 " set configuration_value = '$options'" .
                 " where configuration_key = 'GOOGLEBASE_BULK_OPTIONS'");
  }
  
  function isEnabled() {
    return (bool)$this->getOption('enabled');
  }
  
  /**
   * static method
   * 
   * @param array $ids The ids of the items to retrieve.
   * 
   * @return array An array with id => GoogleBaseItem.
   */
  function getGbaseItemsFromDb($ids) {
    global $db;
    if(!defined('TABLE_GOOGLEBASE')) {
      require_once(dirname(realpath(__FILE__)) . '/../../languages/english/extra_definitions/googlebase.php');
    }
    $query = "select gb.products_id as id, gb.googlebase_item_xml as item_xml
              from ".TABLE_GOOGLEBASE." gb
              where gb.products_id in (".implode(', ', $ids).")
              group by gb.products_id";
    $result = $db->Execute($query);
    $items = array();
    while(!$result->EOF) {
      $xmlp = new XmlParser(stripslashes($result->fields['item_xml']));
      $data = $xmlp->GetData();
      $item = new GoogleBaseItem();
      $item->takeFromArray($data['entry']);
      $items[(int)$result->fields['id']] = $item;
      $result->MoveNext();
    }
    return $items;
  }
  
  /**
   * static method
   * 
   * @param GoogleBaseHttpResponse
   */
  function updateExpirationTimeInDb($response) {
    global $db;
    $pxml = $response->getParsedXmlResponseBody();
    if(!isset($pxml['xmldata']['atom:feed']['atom:entry'][0])) {
      $entry = $pxml['xmldata']['atom:feed']['atom:entry'];
      unset($pxml['xmldata']['atom:feed']['atom:entry']);
      $pxml['xmldata']['atom:feed']['atom:entry'] = array($entry);
    }
    
    foreach($pxml['xmldata']['atom:feed']['atom:entry'] as $entry) {
      if(isset($entry['g:expiration_date'])) {
        $expiration = "'".substr($entry['g:expiration_date']['VALUE'], 0, 20)."'";
        $expiration[11] = ' ';
        $db->Execute("update ".TABLE_GOOGLEBASE." 
                      set googlebase_expiration = $expiration,
                          googlebase_last_modified = now()
                      where googlebase_url = '{$entry['atom:id']['VALUE']}'");
      }
    }
  }
  
  function _buildItemFromProduct($p) {
    $this->_getParentCategoryNames($categories=array(), $p->categories);
    $p->categories = array_reverse($categories);
    $p->link = HTTP_SERVER . DIR_WS_CATALOG . "index.php?main_page=product_info&products_id={$p->zen_id}";
    $item = new GoogleBaseItem();
    $item->setDraftStatus((bool)$this->getOption('draft'));
    $item->setTitle($p->title, 'text/html');
    $item->setDescription($p->description, 'text/html');
    $item->setAuthor($this->getOption('authorname'),
                     $this->getOption('authoremail'));
    $item->setLink($p->link);
    $item->addGbaseAttribute('item_type', 'Products');
    $item->addGbaseAttribute('quantity', $p->quantity);
    $item->addGbaseAttribute('price', $p->price);
    $item->addGbaseAttribute('id', $p->zen_id);
    $item->addGbaseAttribute('zen_id', $p->zen_id, 'int', true);
    $item->addGbaseAttribute('condition', 'new');
    if(zen_not_null($p->image_link)) $item->addGbaseAttribute('image_link', DIR_WS_CATALOG_IMAGES . $p->image_link);
    if(zen_not_null($p->weight)) $item->addGbaseAttribute('weight', $p->weight.' '.TEXT_PRODUCT_WEIGHT_UNIT, 'numberunit');
    if(zen_not_null($p->upc)) $item->addGbaseAttribute('upc', $p->upc);
    if(zen_not_null($p->isbn)) $item->addGbaseAttribute('isbn', $p->isbn);
    if(defined('MODULE_PAYMENT_GOOGLECHECKOUT_STATUS') &&
       constant('MODULE_PAYMENT_GOOGLECHECKOUT_STATUS') == 'True') {
      $item->addGbaseAttribute('payment_notes', 'Google Checkout');
    }
    foreach($p->categories as $cat) {
      $item->addGbaseAttribute('product_type', $cat);
    }
    return $item;
  }
  
  function handleProductModification($action, $products_id) {
    global $db;
    
    if(!$this->isEnabled()) {
      return;
    }
    
    $products_id = (int)$products_id;
    if($action == 'update_product' || $action == 'insert_product') {
      $productsQuery = "select pd.products_name as title, pd.products_description as description,
                               pc.categories_id as categories, p.products_quantity as quantity,
                               p.products_price as price, p.products_image as image_link,
                               p.products_weight as weight, p.products_id as zen_id";
      if($this->getOption('upc')) {
        $productsQuery .= ', p.products_upc as upc, p.products_isbn as isbn';
      }
      $productsQuery .= " from ".TABLE_PRODUCTS." p natural join ".TABLE_PRODUCTS_DESCRIPTION." pd 
                               natural join ".TABLE_PRODUCTS_TO_CATEGORIES." pc
                          where p.products_id = $products_id
                          group by p.products_id";
      $result = $db->Execute($productsQuery);
      if(!$result->EOF) {
        $p = new objectInfo($result->fields);
        $item = $this->_buildItemFromProduct($p);
        
        $gbresult = $db->Execute("select googlebase_url from ".TABLE_GOOGLEBASE." where products_id = $products_id");
        if($gbresult->RecordCount() != 0) {
          $item->setId($gbresult->fields['googlebase_url']);
        }
      
        $gbhttp = googlebase::getGbaseHttpRequest();
        $response = $item->save($this->getOption('token'), $gbhttp);
        $gbhttp->close();
        if(!$response->hasErrors()) {
          $pxml = $response->getParsedXmlResponseBody();
          #echo '<xmp>';var_dump($pxml);echo '</xmp>';
          $entry = $pxml['xmldata']['entry'];
          //TODO: handle gc item errors
          $gb_id = zen_db_input($entry['id']['VALUE']);
          $item->setId($gb_id);
          $expiration_date = 'NULL';
          $item_xml = zen_db_input($item->getXml(true));
          if(isset($entry['g:expiration_date'])) {
            $expiration_date = substr($entry['g:expiration_date']['VALUE'], 0, 19);
            $expiration_date[10] = ' ';
          }
          if($action == 'update_product') {
            $query = "update ".TABLE_GOOGLEBASE."
                      set googlebase_last_modified = now(),
                          googlebase_item_xml = '$item_xml',
                          googlebase_expiration = '$expiration_date'
                      where products_id = $products_id";
          } else {
            $query = "insert into ".TABLE_GOOGLEBASE."
                                  (googlebase_url, products_id,
                                   googlebase_last_modified,
                                   googlebase_item_xml,
                                   googlebase_expiration) values 
                                  ('$gb_id', $products_id, now(), '$item_xml', '$expiration_date')";
          }
          $db->Execute($query);
        }
      }
    } else if($action == 'delete_product_confirm') {
      $items = googlebase::getGbaseItemsFromDb(array($products_id));
      if(empty($items))
        break;
      $gbhttp = googlebase::getGbaseHttpRequest();
      $response = $items[$products_id]->delete($this->getOption('token'), $gbhttp);
      $gbhttp->close();
      if(!$response->hasErrors()) {
        $db->Execute("delete from ".TABLE_GOOGLEBASE." where products_id = $products_id");
      } else { // TODO: else log / message in admin
      }
    }
  }
  
  /**
   * static method
   * 
   * @return GoogleBaseHttpRequest
   */
  function getGbaseHttpRequest() {
    $gbhttp = new GoogleBaseHttpRequest();
    $gbhttp->setTimeout(20);
    if(CURL_PROXY_REQUIRED == 'True')
      $gbhttp->setProxy(CURL_PROXY_SERVER_DETAILS);
    return $gbhttp;
  }
  
  function _getParentCategoryNames(&$categories, $categories_id) {
    global $db;
    $parent_categories_query = "select c.parent_id, cd.categories_name
                                from " . TABLE_CATEGORIES . " c, "
                                       . TABLE_CATEGORIES_DESCRIPTION . " cd 
                                where c.categories_id='".(int)$categories_id."' and c.categories_id = cd.categories_id";

    $parent_categories = $db->Execute($parent_categories_query);

    while (!$parent_categories->EOF) {
      $categories[] = $parent_categories->fields['categories_name'];
      if($parent_categories->fields['parent_id'] == 0) return true;
      if($parent_categories->fields['parent_id'] != $categories_id) {
        $this->_getParentCategoryNames($categories,
                                       $parent_categories->fields['parent_id']);
      }
      $parent_categories->MoveNext();
    }
  }
  
  function uploadProducts($batchSize=50) {
    global $db, $messageStack;
    
    if(!$this->isEnabled()) {
      return;
    }
    
    $maxUploads = $this->getOption('maxuploads');
    if($maxUploads != 0 && $batchSize > $maxUploads) {
      $batchSize = $maxUploads;
    }
                             
    $productsQuery = "select pd.products_name as title,
                             pd.products_description as description,
                             pc.categories_id as categories,
                             p.products_quantity as quantity,
                             p.products_price as price,
                             p.products_image as image_link,
                             p.products_weight as weight,
                             p.products_id as zen_id";
    if($this->getOption('upc')) {
      $productsQuery .= ', p.products_upc as upc, p.products_isbn as isbn';
    }
    $productsQuery .= " from ".TABLE_PRODUCTS." p
                             natural join ".TABLE_PRODUCTS_DESCRIPTION." pd 
                             natural join ".TABLE_PRODUCTS_TO_CATEGORIES." pc
                             natural left join ".TABLE_GOOGLEBASE." gb
                        where gb.products_id is null
                        group by p.products_id
                        order by p.products_id"; 
                        
    //natural join ".TABLE_MANUFACTURERS." m
    $res = $db->Execute("select count(*) as numrows from ".TABLE_PRODUCTS." p
                          natural join ".TABLE_PRODUCTS_DESCRIPTION." pd  natural join ".TABLE_PRODUCTS_TO_CATEGORIES." pc
                          natural left join ".TABLE_GOOGLEBASE." gb where gb.products_id is null");
    $numrows = $res->fields['numrows'];
                        
    if($maxUploads != 0 && $maxUploads < $numrows)
      $numrows = $maxUploads;
      
    $gbhttp = googlebase::getGbaseHttpRequest();
      
    for($page=1; $page*$batchSize <= $numrows; $page++) {
      $gbatch = new GoogleBaseBatchRequest();
      
      $query = $productsQuery;
      $split = new splitPageResults($page, $batchSize, $query, $n);
      $products = $db->Execute($query);
      
      $productsInfo = array();
      $items = array();
      while(!$products->EOF) {
        $p = new objectInfo($products->fields);
        $productsInfo[$p->zen_id] = $p;
        $items[(string)$p->zen_id] = $this->_buildItemFromProduct($p);
        $gbatch->addItemForInsert($items[(string)$p->zen_id], $p->zen_id);
        $products->MoveNext();
      }
      
      flush();
      $response = $gbatch->post($this->getOption('token'), $gbhttp);
      // debug
      //echo '<xmp>'.$gbatch->getXml().'</xmp>';
      //echo '<xmp>'; print_r($response->getParsedXmlResponseBody()); echo '</xmp>';
      
      if(!$response->hasErrors()) {
        $pxml = $response->getParsedXmlResponseBody();
        #echo '<xmp>';var_dump($pxml);echo '</xmp>';
        if(!isset($pxml['xmldata']['atom:feed']['atom:entry'][0])) {
          $entry = $pxml['xmldata']['atom:feed']['atom:entry'];
          unset($pxml['xmldata']['atom:feed']['atom:entry']);
          $pxml['xmldata']['atom:feed']['atom:entry'] = array($entry);
        }
        
        foreach($pxml['xmldata']['atom:feed']['atom:entry'] as $entry) {
          if($entry['batch:status']['code'][0] != '2') {
            $id = (int)$entry['atom:id']['VALUE'];
            $msg = "<p>product with id $id (".substr(strip_tags($productsInfo[$id]->title), 0, 20)." ...) returned error status ".$entry['batch:status']['code']."<br>";
            $msg .= " reason: ".$entry['batch:status']['reason']."<br>";
            $msg .= ' detail: <code style="font-size:1em">'.htmlspecialchars($entry['batch:status']['VALUE'])."</code></p>";
            echo $msg;
            continue;
          }
          //TODO: handle gc item errors
          $title = $entry['atom:title']['VALUE'];
          $gb_id = zen_db_input($entry['atom:id']['VALUE']);
          $zen_id = (string)$entry['g:zen_id']['VALUE'];
          $expiration_date = 'NULL';
          $items[$zen_id]->setId($gb_id);
          $item_xml = zen_db_input($items[$zen_id]->getXml(true));
          
          if(isset($entry['g:expiration_date'])) {
            $expiration_date = substr($entry['g:expiration_date']['VALUE'], 0, 20);
            $expiration_date[11] = ' ';
          }
          $query = "insert into ".TABLE_GOOGLEBASE."
                                (googlebase_url, products_id,
                                 googlebase_last_modified,
                                 googlebase_item_xml,
                                 googlebase_expiration) values 
                                ('$gb_id', $zen_id, now(), '$item_xml', '$expiration_date')";
          $db->Execute($query);
          echo "uploaded $title <br>";
        }
      } else {
        $msg = 'the following error occurred when posting one of the batches: ';
        $msg .= '<code>' . $response->getConnectionError() . '<br>';
        $msg .= strip_tags($response->getResponseBody()) . '</code>';
        echo $msg;
        if($response->getStatusCode() == '401') {
          $this->setOption('token', null);
          echo '<br>click <a href="?action=auth">here</a> to authenticate against Google Base.';
          return;
        }
      }
      flush();
      echo '<br>';
    }
    $gbhttp->close();
  }
  
  function updateProducts($productIds, $orderId=null) {
    global $db;
    
    if(!$this->isEnabled()) {
      return;
    }
    
    if($orderId !== null) {
      $result = $db->Execute("select products_id from ".TABLE_ORDERS_PRODUCTS.
                          " where orders_id = $orderId");
      $productIds = array();
      while(!$result->EOF) {
        $productIds[] = (int)$result->fields['products_id'];
        $result->MoveNext();
      }
    }
    
    $items = googlebase::getGbaseItemsFromDb($productIds);
    
    if(empty($items)) {
      return;
    }
    
    $query = "select products_quantity as quantity, products_id as id
              from ".TABLE_PRODUCTS."
              where products_id in (".implode(', ', $productIds).")";
                            
    $result = $db->Execute($query);
    $gbhttp = googlebase::getGbaseHttpRequest();
    $gbatch = new GoogleBaseBatchRequest();
    
    while(!$result->EOF) {
      $id = (int)$result->fields['id'];
      if(array_key_exists($id, $items)) {
        $quantity = $items[$id]->getGbaseAttribute('quantity');
        if(is_object($quantity[0])) {
          $quantity[0]->setValue($result->fields['quantity']);
        }
        $gbatch->addItemForUpdate($items[$id]);
      }
      $result->MoveNext();
    }
    
    $response = $gbatch->post($this->getOption('token'), $gbhttp);
    googlebase::updateExpirationTimeInDb($response);
    // TODO: handle errors / log somewhere
    #echo "<xmp>"; var_dump($gbatch->getXml()); echo "</xmp>";
    #echo "<xmp>"; var_dump($response->getParsedXmlResponseBody()); echo "</xmp>";
    $gbhttp->close();
  }
  
  function editOptions() {
    // TODO: ugly
    if(isset($_POST['submit'])) {
      if(isset($_POST['maxuploads']) && zen_not_null($_POST['maxuploads'])) $this->_options['maxuploads'] = (int)$_POST['maxuploads'];
      if(isset($_POST['authorname'])) $this->_options['authorname'] = $_POST['authorname'];
      if(isset($_POST['authoremail'])) $this->_options['authoremail'] = $_POST['authoremail'];
      $this->_options['draft'] = isset($_POST['draft']) && $_POST['draft'] == 'on';
      $this->_options['upc'] = isset($_POST['upc']) && $_POST['upc'] == 'on';
      $this->_options['enabled'] = isset($_POST['enabled']) && $_POST['enabled'] == 'on';
      $this->saveOptions();
    }
  }
}
?>
