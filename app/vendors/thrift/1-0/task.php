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
$GLOBALS['THRIFT_SERVICE']['NAME'] = 'Task';		
$GLOBALS['THRIFT_SERVICE']['NAMESPACE'] = 'snaphappi_api';	
error_log("Thrift Server preparing to load, Service={$GLOBALS['THRIFT_SERVICE']['NAME']}");
/*
 * 2. bootstrap Thrift from Cakephp, 
 * 		sets $GLOBALS['THRIFT_ROOT']
 * 
 */ 
require_once ROOT."/app/vendors/thrift/{$GLOBALS['THRIFT_SERVICE']['VERSION']}/bootstrap_thrift_server.php";
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

class TestData {
	public static $folders;
	public static $files;
	public function TestData(){
    	$folders[] = "C:\\TEMP\\May"; 
    	// $folders[] = "C:\\temp.2";
    	// $folders[] = "C:\\temp.3"; 		
		TestData::$folders = $folders;
		
		$folderpath = $folders[0];
		$filepaths[] = "{$folderpath}\\MAY\\2013.JPG";
    	$filepaths[] = "{$folderpath}\\MAY\\2014.JPG";
    	// $filepaths[] = "{$folderPath}\\session\\3.JPG"; 		
		TestData::$files = $filepaths;
	}
} 

class CakePhpHelper {
		/**
		 * authenticate the User account from ProviderAccount.auth_token embedded in $taskID
		 * 		AppController::$userid, AppController::$role will be set here
		 * @param $authToken, from $taskID->AuthToken
		 * @return aa $data[ProviderAccount], $data[Owner], 
		 */
		public static function _loginFromAuthToken($authToken) {
			/*
			 * Session::read(Auth.user);
			 * 	check Session before checking DB
			 */ 
			
// ThriftController::log('_loginFromAuthToken()  ProviderAccount.auth_token='.($authToken), LOG_DEBUG);				
			$options = array(
				'contain' => 'Owner',
				'conditions'=>array('auth_token'=>$authToken)
			);
			$data = ThriftController::$controller->ProviderAccount->find('first', $options);
			$authenticated = ThriftController::$controller->Auth->login(array('User'=>$data['Owner']));
			if ($authenticated) {
// ThriftController::log("_loginFromAuthToken OK, data=".print_r($data,true), LOG_DEBUG);
				// Session::write('Auth.user', ThriftController::$controller->Auth->user());				
				AppController::$userid = ThriftController::$controller->Auth->user('id');
				$role = array_search(ThriftController::$controller->Auth->user('primary_group_id'), Configure::read('lookup.roles'), true);
				AppController::$role = $role;					
				return $data;
			} else return false;
		}
		
		/**
		 * get RAW folder state for thrift api
		 * 		called by GetFolders, GetWatchedFolders
		 * 
		 *		uses MetaData plugin for now, 
		 * TODO: move to Model/DB table?
		 * TODO: should method be moved to the Model under the native desktop uploader
		 * 
		 * @return aa
		 */
		public static function _model_getFolderState($taskID) {
// ThriftController::log("_model_getFolderState taskID=".print_r((array)$taskID, true), LOG_DEBUG);			
			if (!AppController::$userid) CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			ThriftController::$controller->User->id = AppController::$userid;
			// get native desktop uploader state for thrift API
			$thrift_GetFolders = json_decode(ThriftController::$controller->User->getMeta("native-uploader.{$taskID->DeviceID}.state"), true);
// ThriftController::log("_model_getFolderState, key=native-uploader.{$taskID->DeviceID}.state, thrift_GetFolders=", LOG_DEBUG);			
			if (empty($thrift_GetFolders)) ThriftController::log("***** make sure you are testing with user=venice ****", LOG_DEBUG);
// ThriftController::log($thrift_GetFolders, LOG_DEBUG);			
			return $thrift_GetFolders;
		}
		public static function _model_setFolderState($taskID, $thrift_SetFolders) {
			if (!AppController::$userid) CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			ThriftController::$controller->User->id = AppController::$userid;
			ThriftController::$controller->User->setMeta("native-uploader.{$taskID->DeviceID}.state", json_encode($thrift_SetFolders));
ThriftController::log("_model_setFolderState, key=native-uploader.{$taskID->DeviceID}.state, GetFolders() = ", LOG_DEBUG);			
ThriftController::log(json_encode(CakePhpHelper::_model_getFolderState($taskID)), LOG_DEBUG);			
			return $thrift_SetFolders;
		}
		
		/**
		 * get Task state for thrift api
		 * 		called by GetFolders, GetWatchedFolders
		 * WARNING: the task does NOT know the deviceId, must be posted by client
		 *		uses MetaData plugin for now, 
		 * 
		 * TODO: How do we delete keys for expired sessions?
		 *
		 * TODO: move to Model/DB table?
		 * TODO: should method be moved to the Model under the native desktop uploader
		 * 
		 * @return aa 
		 */
		public static function _model_getTaskState($taskID) {
			if (!AppController::$userid) CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			ThriftController::$controller->User->id = AppController::$userid;
			$thrift_GetTask = json_decode(ThriftController::$controller->User->getMeta("native-uploader.task.{$taskID->Session}"), true);
// ThriftController::log("_model_getTaskState(): key=native-uploader.task.{$taskID->Session} data=".print_r($thrift_GetTask, true), LOG_DEBUG);			
			return $thrift_GetTask;
		}
		public static function _model_setTaskState($taskID, $options) {
			if (!AppController::$userid) CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			$thrift_GetTask = CakePhpHelper::_model_getTaskState($taskID);
			ThriftController::$controller->User->id = AppController::$userid;
			$options = array_filter_keys($options, array('IsCancelled', 'FolderUpdateCount', 'FileUpdateCount', 'DeviceUuid'));
			$default = array(
				'IsCancelled'=>0, 
				'FolderUpdateCount'=>0, 
				'FileUpdateCount'=>0, 
				'DeviceUuid'=>null,
			);
			if (isset($options['FileUpdateCount']) && $thrift_GetTask['FileUpdateCount']) {
				$thrift_GetTask['FileUpdateCount'] += 1;
				unset($options['FileUpdateCount']);
			}  
			$thrift_GetTask = array_merge($default, $thrift_GetTask, $options);
			ThriftController::$controller->User->setMeta("native-uploader.task.{$taskID->Session}", json_encode($thrift_GetTask));
ThriftController::log("_model_setTaskState() state=".json_encode($thrift_GetTask), LOG_DEBUG);			
			return $thrift_GetTask;
		}
		public static function _model_deleteTask($taskID) {
			ThriftController::$controller->User->setMeta("native-uploader.task.{$taskID->Session}", null);
		}	
		
		public static function _model_getFiles($taskID) {
			if (!AppController::$userid) CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			ThriftController::$controller->User->id = AppController::$userid;
			$device_files = json_decode(ThriftController::$controller->User->getMeta("native-uploader.{$taskID->DeviceID}.files"), true);
			if (empty($device_files)) $device_files = array();
			// $device_files = is_array($device_files) ? array_filter($device_files) : array();
// ThriftController::log("_model_getFiles, key=native-uploader.{$taskID->DeviceID}.files, GetFiles() = ", LOG_DEBUG);			
// ThriftController::log(($device_files), LOG_DEBUG);			
			return $device_files;
		}		
		public static function _model_setFiles($taskID, $filepath) {
			// $filepath = preg_replace('/^(\w)\\\(.*)/', '${1}:\\\${2}', $filepath, 1);	// addback Win32 Drive delim, i.e. C:\
			if (empty($filepath)) return;			
			if (!AppController::$userid) CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			ThriftController::$controller->User->id = AppController::$userid;
			// $device_files = CakePhpHelper::_model_getFiles($taskID);
			$device_files = json_decode(ThriftController::$controller->User->getMeta("native-uploader.{$taskID->DeviceID}.files"), true);
			$device_files = array_filter($device_files);
			$device_files[] = $filepath;
			ThriftController::$controller->User->setMeta("native-uploader.{$taskID->DeviceID}.files", json_encode($device_files));
ThriftController::log("_model_setFiles, key=native-uploader.{$taskID->DeviceID}.files, GetFiles() = ", LOG_DEBUG);			
ThriftController::log(array( count($device_files) =>$filepath), LOG_DEBUG);			
			return $device_files;
		}
	
}

class snaphappi_api_TaskImpl implements snaphappi_api_TaskIf {
	
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
        	$state = CakePhpHelper::_model_getTaskState($taskID);
			if (empty($state)) $state['IsCancelled'] = true;	// terminate HelperApp if state=null
        	$taskState = new snaphappi_api_URTaskState($state);
ThriftController::log("GetState(taskID), taskState=".print_r($taskState, true), LOG_DEBUG);	
        	return $taskState;
        }
        	
		/**
		 * Return the list of folders to scan for images, exclude watch folders
		 * 	scan=1 || watch=0
		 * @param $taskID snaphappi_api_TaskID
		 * @return array of Strings
		 */
        public function GetFolders($taskID) {
ThriftController::log("***   GetFolders", LOG_DEBUG);        	
			// get native desktop uploader state for thrift API
			$thrift_GetFolders = CakePhpHelper::_model_getFolderState($taskID);
			$folders = array();
			foreach ($thrift_GetFolders as $i=>$folder) {
				if (!$folder['is_scanned'] && !$folder['is_watched']) $folders[] = $folder['folder_path'];
			}
ThriftController::log($folders, LOG_DEBUG);	
			if (empty($folders)) {
				// Cancel Task right away, GetWatchFolders() called from different taskID
				CakePhpHelper::_model_setTaskState($taskID, array('IsCancelled'=>1));
			}				
        	return $folders;
        }
		
		/**
		 * Return the list of Watched folders to scan for images
		 * 	watch=1
		 * @param $taskID snaphappi_api_TaskID
		 * @return array of Strings
		 */
        public function GetWatchedFolders($taskID) {
ThriftController::log("***   GetWatchedFolders", LOG_DEBUG);        	
			// get native desktop uploader state for thrift API
			$thrift_GetFolders = CakePhpHelper::_model_getFolderState($taskID);
			$folders = array();
			foreach ($thrift_GetFolders as $i=>$folder) {
				if ($folder['is_watched']) $folders[] = $folder['folder_path'];
			}
ThriftController::log($folders, LOG_DEBUG);			
        	return $folders;
        }
				
		/**
		 * Return the list of all files uploaded from the given folder within
	 	 * the given task.
		 * @param $taskID snaphappi_api_TaskID
		 * @return array of Strings
		 */
        public function GetFiles($taskID , $folderPath) {
ThriftController::log("***   GetFiles, deviceID={$taskID->DeviceID}, folder={$folderPath}", LOG_DEBUG);         
			$device_files = CakePhpHelper::_model_getFiles($taskID);
			$filtered = array();	
			foreach ($device_files as $i=>$filepath) {
				if (strpos($filepath, $folderPath)===0) $filtered[] = $filepath;
			}		
ThriftController::log(($filtered), LOG_DEBUG);	
        	return $filtered;
        }                
		/**
		 * Report that a folder could not be searched. and prepare for restart/retry
		 * update UX, prompt for moved TopLevelFolder? 
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String
		 */
        public function ReportFolderNotFound($taskID, $folderPath) {
ThriftController::log("ReportFolderNotFound, folder={$folderPath},  taskID=".print_r($taskID, true), LOG_DEBUG);
        	return;
        }
		/**
		 * Report a failed upload.
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String
		 * @param $filePath String
		 */
        public function ReportUploadFailed($taskID, $folderPath, $filePath) {
ThriftController::log("ReportUploadFailed, folder={$folderPath}, file={$filePath},  taskID=".print_r($taskID, true), LOG_DEBUG);
        	return;
        }  		
		/**
		 * Report that all files in a folder have been uploaded.
		 * 	for watched=0, mark folder scan=0
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String
		 */
        public function ReportFolderUploadComplete($taskID, $folderPath) {
ThriftController::log("***   ReportFolderUploadComplete, folder={$folderPath},  taskID=".print_r($taskID, true), LOG_DEBUG);
			$data = CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			$thrift_GetFolders = CakePhpHelper::_model_getFolderState($taskID);
			$found = false; $done_uploading = true;
			foreach ($thrift_GetFolders as $i=>$folder) {
				if ($folder['is_watched']) continue;
				if ($folder['folder_path'] == $folderPath) {
					$thrift_GetFolders[$i]['is_scanned'] = 1;
					// $thrift_GetFolders[$i]['is_watched'] = 1;
					CakePhpHelper::_model_setFolderState($taskID, $thrift_GetFolders);
					$found = true;
					if (!$done_uploading) return;	// keep scanning
				} else {
					$done_uploading = $done_uploading && $folder['is_scanned']; 
				}
			}
			if (!$found) throw new Exception("ReportFolderUploadComplete(): folder not found, path={$folderPath}");
			if ($done_uploading) {
				// cancel TaskId
				CakePhpHelper::_model_setTaskState($taskID, array('IsCancelled'=>1));
			}
        	return;
        }  		
		/**
		 * Sets the number of files expected to be uploaded from a folder.
		 * 	called by native uploader after folder scan complete
		 * @param $taskID snaphappi_api_TaskID
		 * @param $count int
		 */
        public function ReportFileCount($taskID, $folderPath, $count) {
ThriftController::log("***   ReportFileCount, folder={$folderPath}, count={$count}, taskID=".print_r($taskID, true), LOG_DEBUG);
			$data = CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			$thrift_GetFolders = CakePhpHelper::_model_getFolderState($taskID);
			foreach ($thrift_GetFolders as $i=>$folder) {
				if ($folder['folder_path'] == $folderPath) {
					$thrift_GetFolders[$i]['count'] = $count;	
// ThriftController::log("ReportFileCount taskID=".print_r((array)$taskID, true), LOG_DEBUG);
					CakePhpHelper::_model_setFolderState($taskID, $thrift_GetFolders);
					return true; 
				}
			}
			throw new Exception("ReportFileCount(): folder not found, path={$folderPath}");
        	return;
        }
		/**
		 * Report that a Task is being shutdown/cancelled
		 * 	
		 * @param $taskID snaphappi_api_TaskID
		 */
        // public function ReportTaskShutdown($taskID) {
// ThriftController::log("ReportTaskShutdown,  taskID=".print_r($taskID, true), LOG_DEBUG);
			// // TODO: delete Task session keys from DB
        	// return;
        // }		 
		/**
		 * Return the number of files expected to be uploaded from a folder.
		 * called by UI for progress bar stats
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String 
		 */
        public function GetFileCount($taskID, $folderPath) {
ThriftController::log("***   GetFileCount, folder={$folderPath}, taskID=".print_r($taskID, true), LOG_DEBUG);
			$data = CakePhpHelper::_loginFromAuthToken($taskID->AuthToken);
			$thrift_GetFolders = CakePhpHelper::_model_getFolderState();
			foreach ($thrift_GetFolders as $i=>$folder) {
				if ($folder['folder_path'] == $folderPath) {
					return $folder['count'];	 
				};
			}
			throw new Exception("GetFileCount(): folder not found, path={$folderPath}");
        	return;
        }  		 
		/**
		 * Add a top level folder to scan, uploads resized
		 * ???: shouldn't this come from the FLEX app?
		 *  Is this called by the TopLevelFolder app, or just in the DB?
		 * 	Additional params: 
		 *
		 * @param $id TaskID, array[0] int, array[1] string
		 * @param $path String,
		 * @return void 
		 */
        public function AddFolder($id, $path) {
error_log("URTaskUpload->AddFolder(), taskId=".print_r($id, true));
error_log("URTaskUpload->AddFolder(), path={$path}");
        	return;
        }
			
		/**
		 * save binary data from uploaded, *resampled* JPG file
		 * throw Exception on error
		 *
		 * @param $id TaskID,
		 * @param $path String,
		 * @param $data binary,
		 * @return void 
		 */
        public function UploadFile($id, $path, $data) {
ThriftController::log("###   UploadFile(), AuthToken={$id->AuthToken}, path={$path}", LOG_DEBUG);        	
        	$staging_root = Configure::read('path.fileUploader.folder_basepath').($id->AuthToken);
			// make relpath from path
			$relpath = str_replace(':','', $path);
			$fullpath = cleanpath($staging_root.DS.$relpath);
ThriftController::log("URTaskUpload->UploadFile(), fullpath={$fullpath}", LOG_DEBUG);			
			if (!file_exists(dirname($fullpath))) {
				$old_umask = umask(0);
				$ret = mkdir(dirname($fullpath), 0777, true);
				umask($old_umask);
			}
			try {
				$fp = fopen($fullpath,'w+b');
				if (!$fp) throw new Exception('URTaskUpload->UploadFile(): error opening file handle, fopen()');
				if (!fwrite($fp, $data)) throw new Exception('URTaskUpload->UploadFile(): error writing JPG data to file');
				if (!fclose($fp)) throw new Exception('URTaskUpload->UploadFile(): error fclose()');
				
				/*
				 * save file for GetFiles
				 */
				CakePhpHelper::_model_setFiles($id, $path);
				
				/*
				 * update TaskID[FileUpdateCount]
				 */
				CakePhpHelper::_model_setTaskState($id, array('FileUpdateCount'=>1));  
			} catch (Exception $e) {
				error_log($e->getMessage());
				throw $e;
			}
        	return;
        }
}


/*
 * 4. process the Thrift service request
 */
process_THRIFT_SERVICE_REQUEST();



// error_log("Thrift Server ready, Service=".print_r($GLOBALS['THRIFT_SERVICE'], true));


?>