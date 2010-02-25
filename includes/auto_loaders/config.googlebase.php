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
 */
$autoLoadConfig[180][] = array('autoType'=>'class',
                               'loadFile'=>'googlebase.php',
                                'classPath' => DIR_WS_MODULES . 'googlebase/');
$autoLoadConfig[180][] = array('autoType'=>'class',
                               'loadFile'=>'observers/class.googlebaseobserver.php');
$autoLoadConfig[180][] = array('autoType'=>'classInstantiate',
                               'className'=>'GoogleBaseObserver',
                               'objectName'=>'gbaseObserver');
?>
