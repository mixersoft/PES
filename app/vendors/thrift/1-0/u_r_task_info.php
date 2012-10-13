<?php
/*
 * 
 * Template for implementing Thrift Service Classes compiled from [Service].thrift
 *	0. set snappi API version number in thrift controller. example:
 * 		$GLOBALS['THRIFT_SERVICE']['VERSION'] = '1-0'; 
 */
if (!isset($GLOBALS['THRIFT_SERVICE']['VERSION'])) throw new Exception('Error: $GLOBALS[THRIFT_SERVICE][VERSION] is not set');
 
/* 
 *  1. set the global for the compiled thrift service, for 0.8.0 should be found in packages/
 * 		example:
// $GLOBALS['THRIFT_SERVICE']['PACKAGE'] = 'Tasks';			// THRIFT_ROOT/packages/Tasks
// $GLOBALS['THRIFT_SERVICE']['NAME'] = 'URTaskControl'; 	// service, see .thrift file
// $GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_api';  // php namespace, see .thrift file
 */   
$GLOBALS['THRIFT_SERVICE']['PACKAGE'] = 'Tasks';
$GLOBALS['THRIFT_SERVICE']['NAME'] = 'URTaskInfo';		// use CamelCase
$GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_api';
error_log("*** Thrift Server preparing to load, Service={$GLOBALS['THRIFT_SERVICE']['NAME']}");
/*
 * 2. bootstrap Thrift from Cakephp
 */ 
require_once ROOT."/app/vendors/thrift/{$GLOBALS['THRIFT_SERVICE']['VERSION']}/bootstrap_thrift_server.php";
bootstrap_THRIFT_SERVER();
load_THRIFT_SERVICE();
error_log("Thrift Server loaded, Service=".print_r($GLOBALS['THRIFT_SERVICE'], true));

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

class snaphappi_api_URTaskInfoImpl implements snaphappi_api_URTaskInfoIf {
		/**
		 * authenticate the User account from ProviderAccount.auth_token embedded in $taskID
		 * 		AppController::$userid, AppController::$role will be set here
		 * @return aa $data[ProviderAccount], $data[Owner], 
		 */
		public function _authUserFromTaskId($taskID) {
			$options = array(
				'contain' => 'Owner',
				'conditions'=>array('auth_token'=>$taskID->Session)
			);
			$data = ThriftController::$controller->ProviderAccount->find('first', $options);
			$authenticated = ThriftController::$controller->Auth->login(array('User'=>$data['Owner']));
			// ThriftController::log(ThriftController::$controller->Auth->user(), LOG_DEBUG);
			if ($authenticated) {
				AppController::$userid = ThriftController::$controller->Auth->user('id');
				$role = array_search(ThriftController::$controller->Auth->user('primary_group_id'), Configure::read('lookup.roles'), true);
				AppController::$role = $role;					
				return $data;
			} else return false;
		}
				
		/**
		 * get Task state for thrift api
		 * 		called by GetFolders, GetWatchedFolders
		 * WARNING: the task does NOT know the deviceId, must be posted by client
		 *		uses MetaData plugin for now, 
		 * TODO: move to Model/DB table?
		 * TODO: should method be moved to the Model under the native desktop uploader
		 * 
		 * @return ???
		 */
		public function _model_getTaskState($taskID) {
			if (!AppController::$userid) $this->_authUserFromTaskId($taskID);
			ThriftController::$controller->User->id = AppController::$userid;
			$thrift_GetTask = json_decode(ThriftController::$controller->User->getMeta("native-uploader.task.{$taskID->Session}"), true);
			if (empty($thrift_GetTask)) {
				$thrift_GetTask = array(
					'IsCancelled'=>0, 
					'FolderUpdateCount'=>0, 
					'FileUpdateCount'=>0, 
					'DeviceUuid'=>null,
				);
			}
// ThriftController::log($thrift_GetTask, LOG_DEBUG);			
			return $thrift_GetTask;
		}
		public function _model_setTaskState($taskID, $options) {
			if (!AppController::$userid) $this->_authUserFromTaskId($taskID);
			$thrift_GetTask = $this->_model_getTaskState($taskID);
			ThriftController::$controller->User->id = AppController::$userid;
			$options = array_filter_keys($options, array('IsCancelled', 'FolderUpdateCount', 'FileUpdateCount', 'DeviceUuid'));
			$thrift_GetTask = array_merge($thrift_GetTask, $options);
			ThriftController::$controller->User->setMeta("native-uploader.task.{$taskID->Session}", json_encode($thrift_GetTask));
ThriftController::log($this->_model_getTaskState(), LOG_DEBUG);			
			return $thrift_SetFolders;
		}			
	
	
        /**
		 * @param $taskID TaskID
		 * @return URTaskState, 			//  return array[IsCancelled] = boolean (?) 
		 * 	URTaskState->IsCancelled Boolean (optional)
		 *  URTaskState->FolderUpdateCount Int (optional), unique id for Folder state
		 *  URTaskState->FileUpdateCount Int (optional), unique id for File state
		 *  TODO: URTaskState->DeviceId UUID (optional), unique id for desktop device
		 */
        public function GetState($taskID) {
ThriftController::log(">>>   GetState", LOG_DEBUG);           	
        	$state = $this->_model_getTaskState($taskID);
        	$taskState = new snaphappi_api_URTaskState($state);
ThriftController::log("GetState(taskID), taskState=".print_r($taskState, true), LOG_DEBUG);	
        	return $taskState;
        }
                
}


/*
 * 4. process the Thrift service request
 */
process_THRIFT_SERVICE_REQUEST();



// error_log("Thrift Server ready, Service=".print_r($GLOBALS['THRIFT_SERVICE'], true));


?>