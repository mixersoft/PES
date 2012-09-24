<?php
/*
 * 
 * Template for implementing Thrift Service Classes compiled from [Service].thrift
 * 
 * 
 *  1. set the global for the compiled thrift service, for 0.8.0 should be found in packages/
 * 		example: $GLOBALS['THRIFT_ROOT']/packages/Hello/HelloService.php"
 */  
$GLOBALS['THRIFT_SERVICE']['PACKAGE'] = 'Tasks';
$GLOBALS['THRIFT_SERVICE']['NAME'] = 'URTaskControl';		// use CamelCase
$GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_api';
error_log("Thrift Server preparing to load, Service=".print_r($GLOBALS['THRIFT_SERVICE'], true));
/*
 * 2. bootstrap Thrift from Cakephp
 */ 
require_once ROOT.'/app/vendors/thrift/bootstrap_thrift_server.php';
bootstrap_THRIFT_SERVER();
load_THRIFT_SERVICE();
// error_log("Thrift Server loaded, Service=".print_r($GLOBALS['THRIFT_SERVICE'], true));

/*
 * 3. Implement the compiled thrift service interface here
 * 		example: class HelloServiceImpl implements HelloServiceIf() 
 * 
 * 
 * 		AND DON'T FORGET TO 
 * 4. Process_THRIFT_SERVICE_REQUEST() at the END of this file (see below)
 * 
 * 
 */ 

class snaphappi_api_URTaskControlImpl implements snaphappi_api_URTaskControlIf {
		/**
		 * @param TaskID, array[0] int, array[1] string
		 * @return array of Strings
		 */
        public function GetFolders($taskID) {
// error_log("GetFolders, taskID=".print_r($taskID, true));
        	$folders[] = "C:\\temp"; 
        	$folders[] = "C:\\task\\id\\is\\{$taskID->Task}";
        	$folders[] = "C:\\session\\id\\is\\{$taskID->Session}"; 
        	return $folders;
        }
		/**
		 * @param TaskID, array[0] int, array[1] string
		 * @return array of Strings
		 */
        public function GetFiles($taskID) {
// error_log("GetFolders, taskID=".print_r($taskID, true));
			$filepaths[] = "C:\\temp\\1.JPG";
        	$filepaths[] = "C:\\task\\id\\is\\{$taskID->Task}\\2.JPG";
        	$filepaths[] = "C:\\session\\id\\is\\{$taskID->Session}\\3.JPG"; 
        	return $filepaths;
        }                
}


/*
 * 4. process the Thrift service request
 */
process_THRIFT_SERVICE_REQUEST();



// error_log("Thrift Server ready, Service=".print_r($GLOBALS['THRIFT_SERVICE'], true));


?>