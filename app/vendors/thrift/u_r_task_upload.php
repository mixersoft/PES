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
$GLOBALS['THRIFT_SERVICE']['NAME'] = 'URTaskUpload';		// use CamelCase
$GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_api';
/*
 * 2. bootstrap Thrift from Cakephp
 */ 
require_once ROOT.'/app/vendors/thrift/bootstrap_thrift_server.php';
bootstrap_THRIFT_SERVER();
load_THRIFT_SERVICE();

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

class snaphappi_api_URTaskUploadImpl implements snaphappi_api_URTaskUploadIf {
		/**
		 * Add a top level folder to scan,
		 * ???: shouldn't this come from the FLEX app?
		 * 	Additional params: 
		 * 		- original or resized	 
		 *
		 * @param $id TaskID, array[0] int, array[1] string
		 * @param $path String,
		 * @return void 
		 */
        public function AddFolder($id, $path) {
error_log("UploadFile, taskId=".print_r($id, true));
error_log("UploadFile, path=".print_r($path, true));
        	return;
        }
			
		/**
		 * save binary data from uploaded JPG file
		 * 	Additional params: 
		 * 		- original or resized	 
		 *
		 * @param $id TaskID, array[0] int, array[1] string
		 * @param $path String,
		 * @param $data binary,
		 * @return void 
		 */
        public function UploadFile($id, $path, $data) {
error_log("UploadFile, taskId=".print_r($id, true));
error_log("UploadFile, path=".print_r($path, true));
        	return;
        }
}


/*
 * 4. process the Thrift service request
 */
process_THRIFT_SERVICE_REQUEST();

?>