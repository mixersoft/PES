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

$sessionId = ('Session-0');
$authToken = ('aHR0cDovL3d3dy5zbmFwaGFwcGkuY29t');
$launch_URI = "snaphappi://".base64_encode($authToken)."_".base64_encode($sessionId)."_ur";

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
  $client   = new $client_class($protocol);
  
print "<br> Client Class={$client_class}";    

  $transport->open();
  try {
  	print "<BR />*************************************";
  	$taskId = new snaphappi_api_TaskID(array('Session'=>base64_encode("null"), 'AuthToken'=>base64_encode("null")));
	print "<BR />Using INVALID TaskId=".print_r($taskId,true);
	print "<BR />Expecting to shutdown helperApp";	
	$e = $client->GetState($taskId);
	print "<br>GetState()=".print_r($e,true);	
	print "<BR />";	
	
	
	print "<BR />              ***";
  	$taskId = new snaphappi_api_TaskID(array('Session'=>$sessionId, 'AuthToken'=>$authToken, 'DeviceID'=>'5ec5006d-8dee-48ef-8c04-bac06c16d36e'));
	print "<BR />Using TaskId=".print_r($taskId,true);
	print "<BR />";	
	
	$e = $client->GetState($taskId);
	print "<br>GetState()=".print_r($e,true);
	
	$folders = $client->GetFolders($taskId);
	print "<br>GetFolders()=".print_r($folders,true);
	
	$folders = $client->GetWatchedFolders($taskId);
	print "<br>GetWatchedFolders()=".print_r($folders,true);
		
	$files = $client->GetFiles($taskId, $folders[0]);
	print "<br>GetFiles()=".print_r($files,true);
	
	$client->ReportFolderNotFound($taskId, $folders[0]);
	if (count($files)) $client->ReportUploadFailed($taskId, $folders[0], $files[0]);
	$client->ReportFolderUploadComplete($taskId, $folders[0]);
	
	$count = $client->GetFileCount($taskId, $folders[0]);
	print "<br>GetFileCount(), count={$count}";
	
	$client->ReportFileCount($taskId, $folders[0], $count);
	print "<br>ReportFileCount(), count={$count}";
	
	
	
		

	print "<BR />";
	print "<BR />*************************************";	  
  } catch (Exception $e){}
  $transport->close();

} catch (TException $tx) {
  print 'TException: '.$tx->getMessage()."\n";
}

?>



<DIV>
	<BR /><BR />
Once the helper app is installed, the following link should invoke it: <a href=<?php echo "'$launch_URI'"; ?> ><?php echo $launch_URI; ?></a>.
</DIV>