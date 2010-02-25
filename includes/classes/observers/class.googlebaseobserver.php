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
 * @version $Id: googlebase.php 0 Oct 17, 2007 12:34:28 PM pablif@gmail.com $
 */

class GoogleBaseObserver extends base {
  var $_gb;
  function GoogleBaseObserver() {
    global $zco_notifier;
    $this->_gb = new googlebase();
    $zco_notifier->attach($this, array('NOTIFY_CHECKOUT_PROCESS_HANDLE_AFFILIATES'));
  }
      
  function update(&$callingClass, $eventId, $paramsArray) {
    switch($eventId) {
      case 'NOTIFY_CHECKOUT_PROCESS_HANDLE_AFFILIATES':
        require_once(DIR_WS_CLASSES . 'order.php');
        $order = new order();
        $ids = array();
        foreach($order->products as $p) {
          $ids[] = (int)$p['id'];
        }
        $this->_gb->updateProducts($ids);
        break;
    }
  }
}
?>
