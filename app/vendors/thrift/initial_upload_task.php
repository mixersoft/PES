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
$GLOBALS['THRIFT_SERVICE']['NAME'] = 'InitialUploadTask';		// use CamelCase
$GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_tasks';
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

function check_cakephp($shell){
	/*
	 *  test Cakephp Model reference
	 */
	$options = array(
		'fields'=>array("User.id, User.username"),
	);
	$data = ClassRegistry::init('User')->find('first', $options);
	
	if ($_SERVER['REQUEST_METHOD']=="GET") {
		debug("Cake Model->query(): result=".print_r($data, true));
	} else error_log("Cake Model->query(): result=".print_r($data, true));	
		
	return $data;
}

class snaphappi_tasks_InitialUploadTaskImpl implements snaphappi_tasks_InitialUploadTaskIf {
        public function GetFolders($sessionID) {
        	$folders[] = "C:\\temp"; 
			$folders[] = "C:\\session_id\\is\\".$sessionID;
        	return $folders;
        }
}


/*
 * 4. process the Thrift service request
 */
process_THRIFT_SERVICE_REQUEST();

?>