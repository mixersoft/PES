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

 /*
  * set service Globals
  */ 
$GLOBALS['THRIFT_SERVICE']['VERSION'] = '1-0';  
$GLOBALS['THRIFT_SERVICE']['PACKAGE'] = 'Tasks';
$GLOBALS['THRIFT_SERVICE']['NAME'] = 'Task';		// use CamelCase
$GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_api';

if (!defined('THRIFT_LIB_BASEPATH')) define('THRIFT_LIB_BASEPATH', '/www-dev');
// manually set THRIFT_ROOT, outside cakephp
$GLOBALS['THRIFT_ROOT'] = THRIFT_LIB_BASEPATH."/app/vendors/thrift/{$GLOBALS['THRIFT_SERVICE']['VERSION']}/THRIFT_ROOT";
$GEN_DIR = $GLOBALS['THRIFT_ROOT'].'/packages';

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
$service = $GLOBALS['THRIFT_SERVICE'];
$thrift_service_file = $GEN_DIR . "/{$service['PACKAGE']}/{$service['NAME']}.php";
require_once $thrift_service_file;
require_once $GEN_DIR.'/Tasks/Tasks_types.php';

print("<br>THRIFT_ROOT={$GLOBALS['THRIFT_ROOT']}");
print("<br>LOADING Thrift Service (compiled), file=".$thrift_service_file);

error_reporting(E_ALL);

$API_VERSION = $GLOBALS['THRIFT_SERVICE']['VERSION'];
$ACTION = "/thrift/service/api:{$API_VERSION}/{$service['NAME']}/0";	// DEBUG MUST==0!!!!!
$SERVER = $_SERVER['SERVER_NAME'];
// $SERVER = '192.168.1.7';
// $SERVER = 'dev.snaphappi.com';




print "<br>REQUEST={$SERVER}{$ACTION}<br><br>";

try {
  if (!isset($argv) || (array_search('--http', $argv))) {
  	/*
	 * cakephp controller=thrift, action=service
	 */
	$socket = new THttpClient($SERVER, 80, $ACTION );
  } else {
    $socket = new TSocket('localhost', 9090);
  }
  // 
  $transport = new TBufferedTransport($socket, 1024, 1024);
  $protocol = new TBinaryProtocol($transport);
  
  
  /*
   * create client Class
   */
  $client_class = $service['NAME']."Client";
  $client_class = empty($service['NAMESPACE']) ? $client_class : $service['NAMESPACE'].'_'.$client_class;
  $client   = new $client_class($protocol);		// new snaphappi_api_TaskClient()
  
print "<br> Client Class={$client_class}";    

  $transport->open();
  try {
  	print "<BR />*************************************";
	// the authToken & sessionId will be passed as runtime flashvars 
	$authToken = 'b34f54557023cce43ab7213e0eb7da2a6b9d6b27';
	$sessionId='50a32a66-6680-4c29-a5b2-1644f67883f5';
	$deviceId = '2738ebe4-95a1-4d4a-aefe-761d97881535';
	
	print "<BR /> ";	
	print "<BR />   Testing AddFolder() with ".print_r(array('authToken'=>$authToken,'sessionId'=>$sessionId), true);
	print "<BR />*************************************";
	
	// call async/on timer, wait until $deviceId is available
	try {
		/**
		 * GetDeviceID()
		 *  
		 * Returns the device ID associated with this session or empty string
	 	 * if it is not yet known.
		 * 	
		 * @param $authToken
		 * @param $sessionId 
		 * @throws snaphappi_api_SystemException(), 
		 * 		ErrorCode::InvalidAuth for auth or session problems
		 * 		ErrorCode::DataConflict if DeviceID is not yet bound to Session, try again
		 * 		ErrorCode::Unknown for everything else
		 */		
		$deviceId = $client->GetDeviceID($authToken, $sessionId);
		print "<br>GetDeviceID()={$deviceId}";
		
	} catch (snaphappi_api_SystemException $e) {
		if ($e->ErrorCode == ErrorCode::DataConflict ) {
			// sleep for 1 sec and then try again
			// timeout after 30 sec
		}
		print "<BR />   Thrift Exception, msg=".$e->Information;
	}
	
  	$taskId = new snaphappi_api_TaskID(array(
  		'Session'=>$sessionId, 
  		'AuthToken'=>$authToken, 
  		'DeviceID'=>$deviceId)
	);
	print "<BR />";	
	print "<BR />Using TaskId=".print_r($taskId,true);
	
	
	
	try {
		/**
		 * AddFolder()
		 * 
		 * Add a top level folder to scan
		 * @param $taskID snaphappi_api_TaskID,
		 * @param $path String,
		 * @throws  snaphappi_api_SystemException(), 
		 * 		ErrorCode::DataConflict if folder already exists for the current DeviceID
		 * 		ErrorCode::Unknown for everything else
		 */
		$nativePath = "C:\\TEMP\\added from Thrift AddFolder";
		$ret = $client->AddFolder($taskId, $nativePath);
		print "<BR />";	
		print "<br>AddFolder() OK, native_path={$nativePath}";
	} catch (snaphappi_api_SystemException $e) {
		if ($e->ErrorCode == ErrorCode::DataConflict ) {
			// do nothing on duplicate folder
		}
		print "<BR />";
		print "<BR />   Thrift Exception, msg=".$e->Information;
	}
	
	
	$folders = $client->GetFolders($taskId);
	print "<BR />";
	print "<br>GetFolders()=".print_r($folders,true);
	

	print "<BR />";
	print "<BR />*************************************";	  
  } catch (Exception $e){}
  $transport->close();

} catch (TException $tx) {
  print 'TException: '.$tx->getMessage()."\n";
}

?>



