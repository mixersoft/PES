#!/usr/bin/env php
<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

if (!defined('THRIFT_LIB_BASEPATH')) define('THRIFT_LIB_BASEPATH', 'W:/www-dev/');
$GLOBALS['THRIFT_ROOT'] = THRIFT_LIB_BASEPATH.'app/vendors/THRIFT_ROOT/';
$GEN_DIR = $GLOBALS['THRIFT_ROOT'].'packages';

require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/THttpClient.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';

/**
 * Suppress errors in here, which happen because we have not installed into
 * $GLOBALS['THRIFT_ROOT'].'/packages/tutorial' like we are supposed to!
 *
 * Normally we would only have to include Calculator.php which would properly
 * include the other files from their packages/ folder locations, but we
 * include everything here due to the bogus path setup.
 */
error_reporting(0);
// $GEN_DIR = '../gen-php';
require_once $GEN_DIR.'/Hello/HelloService.php';
require_once $GEN_DIR.'/Hello/Hello_types.php';
error_reporting(E_ALL);

$SERVER = 'snappi-dev';

print "<br>SERVER={$SERVER}<br><br>";

try {
  if (!isset($argv) || (array_search('--http', $argv))) {
    $socket = new THttpClient($SERVER, 80, '/thrift/helloServer.php');
  } else {
    $socket = new TSocket('localhost', 9090);
  }
  // 
  $transport = new TBufferedTransport($socket, 1024, 1024);
  $protocol = new TBinaryProtocol($transport);
  $client = new HelloServiceClient($protocol);

  $transport->open();
  try {
	  $e = $client->say_hello();
	  print "<br>say_hello()={$e}";
	  
	  $e = $client->say_foreign_hello(HelloLanguage::SPANISH);
	  print "<br>say_foreign_hello()={$e}";
	  
	  $e = $client->say_hello_repeat(10);
	  print "<br>say_hello_repeat()=".print_r($e,true);
	  
  } catch (Exception $e){}
  $transport->close();

} catch (TException $tx) {
  print 'TException: '.$tx->getMessage()."\n";
}

?>
