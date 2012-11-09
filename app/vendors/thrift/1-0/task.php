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

 /*
  * helper Class for array_filter callback
  */ 
class FolderContains {
	public static $folderPath = null;
	public static $case_sensitive = 1;
	public function asset($filepath) {
		if (FolderContains::$case_sensitive) strpos($filepath, FolderContains::$folderPath)===0;
		else return stripos($filepath, FolderContains::$folderPath)===0;
	}
	public function stripDeviceId($deviceAndFilepath) {
		return substr($deviceAndFilepath, strpos($deviceAndFilepath,'~')+1);
	}
}

class CakePhpHelper {
		public static $DUPLICATE_FILE_EXCEPTION_THRESHOLD = 4;
		public static $OTHER_EXCEPTION_THRESHOLD = 4;
		
		/**
		 * authenticate the User account from ProviderAccount.auth_token embedded in $taskID
		 * 		AppController::$userid, AppController::$role will be set here
		 * @param $taskID, from $taskID
		 * @return aa $data[ProviderAccount,Owner,ThriftSession,ThriftDevice] 
		 */
		public static function _loginFromAuthToken($taskID) {
// ThriftController::log('_loginFromAuthToken()  ProviderAccount.auth_token='.($authToken), LOG_DEBUG);
			try {
				/*
				 * - finds pa with authToken and $providerKey == DeviceID, 
				 * - if empty, then checks for authToken with providerKey=null, and 
				 * -- updates record to save DeviceID as providerKey
				 */  
				$data = ThriftController::$controller->ProviderAccount->thrift_findByAuthToken($taskID->AuthToken, $taskID->DeviceID);
				if (!$data) throw new Exception("Error: authToken not found, authToken={$taskID->AuthToken}");	
				$authenticated = ThriftController::$controller->Auth->login(array('User'=>$data['Owner']));
				if (!$authenticated) throw new Exception("Error: authToken invalid, authToken={$taskID->AuthToken}");
				// Session::write('Auth.user', ThriftController::$controller->Auth->user());				
				AppController::$userid = ThriftController::$controller->Auth->user('id');
				$role = array_search($data['Owner']['primary_group_id'], Configure::read('lookup.roles'), true);
				AppController::$role = $role;		
				
				// attach to Session, bind DeviceID if necessary
				// TODO: deprecate once sw task sends taskID->Session
				$sessionId = $taskID->Session ? $taskID->Session : $taskID->DeviceID;
				$session = ThriftController::$controller->ThriftSession->checkDevice($sessionId, $taskID->DeviceID);
				if (!$session) $session = ThriftController::$controller->ThriftSession->bindDeviceToSession($sessionId, $taskID->AuthToken, $taskID->DeviceID);
				$data = array_merge($data, $session);
				ThriftController::$session = $data;
debug($data);				
// ThriftController::log("_loginFromAuthToken OK, data=".print_r($data,true), LOG_DEBUG);
				return $data;
			} catch (Exception $e) {
				ThriftController::log("==============   ".$e->getMessage(), LOG_DEBUG);
				$thrift_exception['ErrorCode'] = ErrorCode::InvalidAuth;
				$thrift_exception['Information'] = $e->getMessage();
debug($thrift_exception);				
				//TODO: UI should notify the user to relaunch HelperApp with new token, or reschedule
				throw new snaphappi_api_SystemException($thrift_exception);	
			}
		}
		
		/**
		 * get RAW folder state for thrift api
		 * 		called by GetFolders, GetWatchedFolders
		 * 
		 * @param $taskID, from $taskID
		 * @param $isWatched boolean, 
		 *		if true, return only watched folders, 
		 *		if false return only unwatched folders, 
		 * 		if null return all folders,
		 * @return array of array_keys='folder_path', is_scanned, is_watched, count
		 */
		public static function _model_getFolderState($taskID, $isWatched = null) {
// ThriftController::log("_model_getFolderState taskID=".print_r((array)$taskID, true), LOG_DEBUG);			
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			$thrift_device_id = $session['ThriftDevice']['id'];
			$thrift_GetFolders = ThriftController::$controller->ThriftFolder->findByThriftDeviceId(
				$thrift_device_id, $isWatched
			);
			$folders = array();
			foreach ($thrift_GetFolders as $i=>$row){
				$folders[] = array(
					'folder_path'=> $row['ThriftFolder']['native_path'],
					'is_scanned' => $row['ThriftFolder']['is_scanned'],
            		'is_watched' => $row['ThriftFolder']['is_watched'],
            		'count' => $row['ThriftFolder']['count'],
				);
			}
			return $folders;
		}

		/**
		 * update ThriftFolder, using native_path as key 
		 * @param $taskID
		 * @param $data, data[ThriftFolder]
		 */ 
		public static function _model_setFolderState($taskID, $data) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			$thrift_device_id = $session['ThriftDevice']['id'];
			$ret = ThriftController::$controller->ThriftFolder->updateFolderByNativePath($thrift_device_id, $data);
ThriftController::log("############################## thrift_SetFolders=".print_r($ret, true), LOG_DEBUG);				
			return $ret;
		}
		
		/**
		 * get Task state for thrift api
		 * 		called by GetFolders, GetWatchedFolders
		 * WARNING: the task does NOT know the deviceId, must be posted by client
		 *		uses MetaData plugin for now, 
		 * 
		 * TODO: move to Model/DB table?
		 * TODO: should method be moved to the Model under the native desktop uploader
		 * 
		 * @return aa 
		 */
		public static function _model_getTaskState($taskID) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			$data = ThriftController::$controller->ThriftSession->read(null, $session['ThriftSession']['id']);
			ThriftController::$session['ThriftSession'] = $data['ThriftSession'];
			$thrift_GetTask = array(
				'IsCancelled'=>$session['ThriftSession']['is_cancelled'], 
				'FolderUpdateCount'=>0, 
				'FileUpdateCount'=>0, 
				// TODO: check timezone for batchId
				'BatchId'=>strtotime($session['ThriftSession']['modified']), 
				'DuplicateFileException'=>$session['ThriftSession']['DuplicateFileException'],
				'OtherException'=>$session['ThriftSession']['OtherException'],
			);
			return $thrift_GetTask; 
		}
		public static function _model_setTaskState($taskID, $options) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			$thrift_GetTask = CakePhpHelper::_model_getTaskState($taskID);
			
			$options = array_filter_keys($options, 
				array('IsCancelled', 'FolderUpdateCount', 'FileUpdateCount', 
					'DuplicateFileException', 'OtherException',
					'BatchId',
					));
			// save to DB
			if (isset($options['FileUpdateCount']) && $thrift_GetTask['FileUpdateCount']) {
				$thrift_GetTask['FileUpdateCount'] += 1;
				unset($options['FileUpdateCount']);
			}
			if (isset($options['DuplicateFileException'])) {
				if (!isset($thrift_GetTask['DuplicateFileException'])) $thrift_GetTask['DuplicateFileException'] = 1;
				else $thrift_GetTask['DuplicateFileException'] += 1;
ThriftController::log("**** WARNING: DuplicateFileException count={$thrift_GetTask['DuplicateFileException']}", LOG_DEBUG);				
				unset($options['DuplicateFileException']);
			}
			if (isset($options['OtherException'])) {
				if (!isset($thrift_GetTask['OtherException'])) $thrift_GetTask['OtherException'] = 1;
				else $thrift_GetTask['OtherException'] += 1;
				unset($options['OtherException']);
			}
			$thrift_GetTask = array_merge($thrift_GetTask, $options);
			// handle Exception thrisholds, or save to DB
			$shutdown = false;
			if ($thrift_GetTask['DuplicateFileException'] > CakePhpHelper::$DUPLICATE_FILE_EXCEPTION_THRESHOLD) {
				$message = "DuplicateFileException exceeds threshold, count=".$thrift_GetTask['DuplicateFileException'];
				$shutdown = true;
			}
			if ($thrift_GetTask['OtherException'] > CakePhpHelper::$OTHER_EXCEPTION_THRESHOLD) {
				$message = "OtherException exceeds threshold, count=".$thrift_GetTask['OtherException'];
				$shutdown = true;
			}			
			// save to db
			$thrift_GetTask = ThriftController::$controller->ThriftSession->saveTaskState(
				$session['ThriftSession']['id'], $thrift_GetTask
			); 
ThriftController::log("_model_setTaskState() state=".print_r($thrift_GetTask, true), LOG_DEBUG);				
			if ($shutdown) CakePhpHelper::_shutdown_client($taskID, $message);
			return $thrift_GetTask;
		}


		public static function _model_deleteTask($taskID) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftSession->delete($session['ThriftSession']['id']);
			ThriftController::$controller->User->setMeta("native-uploader.task.{$taskID->Session}", null);
		}	
		
		public static function _model_getFiles($taskID, $folderPath) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			
			$device_files = ThriftController::$controller->ThriftFolder->getFiles(
				$session['ThriftDevice'], 
				$folderPath
			);
			if (!empty($folderPath)) {
				FolderContains::$folderPath = $folderPath; 
				FolderContains::$case_sensitive = Configure::read('Config.os')=='win' ? 0 : 1;
// ThriftController::log("_model_getFiles() case sensitive=".FolderContains::$case_sensitive." BEFORE filter=".print_r($device_files, true), LOG_DEBUG);				

				$device_files = array_map('FolderContains::stripDeviceId', $device_files);
				/*
				 * Skip if we are filtering in the DB using LIKE, see ThriftFolder::getFiles()
				 */ 
				// $device_files = array_filter($device_files, 'FolderContains::asset');	
			} else {
				$device_files = array_filter($device_files);
			}
			// filter for files in folderPath, or use SQL LIKE clause
ThriftController::log("_model_getFiles() AFTER FILTER=".print_r($device_files, true), LOG_DEBUG);			
			return $device_files;
		}		
		

		/**
		 * shutdown client and log message
		 * for type=UR, use TaskID->IsCancelled=1
		 * for type=SQ, throw SystemException() 
		 * @param $TaskID
		 * @param $message string
		 */
		public static function _shutdown_client($taskID, $message) {
			ThriftController::log("*** SYSTEM ERROR: shutdown client, message={$message}", LOG_DEBUG);
			if (!empty($taskID->Session)) {
				// skip if already cancelled
				if (ThriftController::$session['ThriftSession']['is_cancelled']) return;
				
				ThriftController::log("*** using IsCancelled", LOG_DEBUG);
				// UR task, use IsCancelled
				CakePhpHelper::_model_setTaskState($taskID, array('IsCancelled'=>1));
				return;
				// // when called from _model_setTaskState(), make sure TaskState is not overwritten with a cached copy of $options
				// $options['IsCancelled'] = 1;
			} else {
				// SW task, use UnknownException
				$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
				$thrift_exception['Information'] = $message;
				ThriftController::log("*****   SHUTDOWN REQUEST by SystemException=".print_r($thrift_exception,true), LOG_DEBUG);
				$ex = new snaphappi_api_SystemException($thrift_exception);
				throw $ex;
				// implicit return;
			}
			return;
		}
		
	/**
	 * Import uploaded file into DB, staging server
	 * @param $basepath String, the path on server to the folder used for staging uploaded files
	 * @param $fullpath String, full path on server to uploaded file, inside $basepath
	 * @param $origPath String, the native path on the device where the uploaded file was found
	 * @param $batchId int, unixtime(), used to find files uploaded at the same time
	 * @param $isOriginal boolean, the uploaded file is the original file from device, not resampled to preview size
	 */
	public static function __importPhoto( $basepath, $fullpath, $origPath, $batchId, $isOriginal=false){
		if (empty( ThriftController::$session)) throw new Exception("ERROR: CakePhpHelper::__importPhoto - Thrift Session is not defined");
		// setup meta data
		$BATCH_ID = $batchId; // WARNING: session not preseved between calls
		$PROVIDER_NAME = 'native-uploader';
		$data = array();
		$data['Asset']['id'] = null;
		$data['Asset']['batchId'] = $BATCH_ID;
		$devicePrefix = ThriftController::$session['ThriftDevice']['id'].ThriftFolder::$DEVICE_SEPARATOR;
		$device_origPath = $devicePrefix . $origPath;	
		$data['Asset']['rel_path'] = $device_origPath;	
		$data['ProviderAccount']['provider_name'] = ThriftController::$session['ProviderAccount']['provider_name'];
		$data['ProviderAccount']['provider_key'] = ThriftController::$session['ProviderAccount']['provider_key'];
		$data['ProviderAccount']['display_name'] = ThriftController::$session['ProviderAccount']['display_name']; 
		/***************************************************************
		 * experimental: replace mode, replace existing with original
		 * 	pass to Asset::addIfNew()
		 ***************************************************************/ 
		 if (0 && $id->Type=='uo'){
		 	$data['Asset']['replace-preview-with-original'] = true;
		 };
		
		$isOriginal = ($isOriginal == 'ORIGINAL');
		$ret = true;
		$Import = loadComponent('Import', ThriftController::$controller);
		if (!isset(ThriftController::$controller->Asset)) ThriftController::$controller->Asset = ClassRegistry::init('Asset');
		
// establish Auth/Permissionable		
// Load/initialize PermissionableComponent, CakePhpHelper::after _loginFromAuthToken()
App::import('Component', 'Permissionable.Permissionable');
ThriftController::$controller->Permissionable = & new PermissionableComponent();
ThriftController::$controller->Permissionable->initialize(ThriftController::$controller);
// TODO: test detaching Permissionable	instead	
		// ThriftController::$controller->Asset->Behaviors->detach('Permissionable');
		/*
		 * create ProviderAccount, if missing
		 */
		$conditions = array(
			'provider_name'=>$data['ProviderAccount']['provider_name'], 
			'provider_key'=>$data['ProviderAccount']['provider_key'],
			'user_id'=>AppController::$userid
		);
		/*
		 * $providerKey == DeviceID
		 * - find pa with correct providerKey, 
		 */  
		 // TODO: do not use addIfNew(), pa should be valid by now
		 // TODO: add correct UNIQUE index for ProviderAccount, include provider key, 
		$paData = ThriftController::$controller->ProviderAccount->addIfNew($data['ProviderAccount'], $conditions,  $response);
debug($data);				
debug($paData);	
		/****************************************************
		 * setup data['Asset'] to create new Asset
		 */
		$profile = ThriftController::$controller->getProfile();		// defined in AppController
		if (isset($profile['Profile']['privacy_assets'])) {
			$data['Asset']['perms'] = $profile['Profile']['privacy_assets'];
		}	
		
// ThriftController::log("CakePh.pHelper::__importPhoto, paData=".print_r($paData, true), LOG_DEBUG);			
		$assetData = ThriftController::$controller->Asset->addIfNew($data['Asset'], $paData['ProviderAccount'], $basepath, $fullpath, $isOriginal, $response);
ThriftController::log("CakePhpHelper::__importPhoto, this->Asset->addIfNew(), response=".print_r($response, true), LOG_DEBUG);				
// ThriftController::log("CakePhpHelper::__importPhoto, this->Asset->addIfNew(), asset=".print_r($assetData, true), LOG_DEBUG);		

		$copyToStaging = true;
		if (isset($response['message']['DuplicateAssetFound'])) {
			// this is a duplicate file, if we are uploading original, replace JPG
			// TODO: add support for Task_Types here
			if ($response['message']['DuplicateAssetFound']=='FOUND duplicate Asset, Photo fields updated' 
					&& 0 && "TASK_TYPE=UPLOAD_ORIGINAL"
			) {
				// upload Original not yet implemented
			} else if ( $response['message']['DuplicateAssetFound']
				&& 1 && "TASK_TYPE=UPLOAD_RESIZED"
			) {
			 	// uploading duplicate preview, throw DuplicateFoundException 
				$copyToStaging = $ret = false;
				$response['message']['DuplicateFoundException'] = 1;
			} else {
			 	// throw OtherException, either 1) problem updated Asset.json_exiv in DB or 2) other error
			 	$response['message']['OtherException'] = 1;
			 	$copyToStaging = $ret = false;
			}
		}
ThriftController::log("&&&&&&&&&&&&&&&&&&&&&&&&& copyToStaging={$copyToStaging}", LOG_DEBUG); 		
		if ($copyToStaging) {
			/*
			 *  move original file to staging server, replace preview file,
			 * 	NOTE: for DUPLICATE files, 
			 * 		asset fields are updated in DB, see Asset::__updateAssetFields()
			 * 		files are COPIED only if larger
			 */   
			$src = json_decode($assetData['Asset']['json_src'], true);
			$stage=array('src'=>$fullpath, 'dest'=>$src['root']);
	ThriftController::log("CakePhpHelper::__importPhoto staging files, ".print_r($stage, true), LOG_DEBUG);		
		 	if ($ret3 = $Import->import2stage($stage['src'], $stage['dest'], null, $move = true)) {
		 		$response['message'][]="file staged successfully, dest={$stage['dest']}";
			} else $response['message'][]="Error staging file, src={$stage['src']} dest={$stage['dest']}";
		 	$ret = $ret && $ret3;
		}

	 	$response['success'] = $ret;
		$response['response'] = $assetData;
		return $response;
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
ThriftController::log("***   GetState", LOG_DEBUG);        	
        	$state = CakePhpHelper::_model_getTaskState($taskID);
			if (empty($state)) {
				// NOTE: InvalidAuth Exception thrown from CakePhpHelper::_loginFromAuthToken()
				$state['IsCancelled'] = true;
			} else {
ThriftController::log($state, LOG_DEBUG); 				
			}
        	$taskState = new snaphappi_api_URTaskState($state);
        	return $taskState;
        }
        	
		/**
		 * Return the list of folders to scan for images, exclude watch folders
		 * called by interactive upload task, type=UR
		 * 	scan=1 || watch=0
		 * @param $taskID snaphappi_api_TaskID
		 * @return array of Strings
		 */
        public function GetFolders($taskID) {
ThriftController::log("***   GetFolders", LOG_DEBUG);
			// get native desktop uploader state for thrift API
			$folders = CakePhpHelper::_model_getFolderState($taskID, false);

			if (empty($folders)) {
				// Cancel Task right away, GetWatchFolders() called from different taskID
				CakePhpHelper::_model_setTaskState($taskID, array('IsCancelled'=>1));
			}				
			$folders = Set::extract('{n}.folder_path',$folders);
ThriftController::log($folders, LOG_DEBUG);				
			return $folders;
        }
		
		/**
		 * Return the list of Watched folders to scan for images
		 * called by scheduled task, type=SW
		 * 	watch=1
		 * @param $taskID snaphappi_api_TaskID
		 * @return array of Strings
		 */
        public function GetWatchedFolders($taskID) {
ThriftController::log("***   GetWatchedFolders", LOG_DEBUG);    
// ThriftController::log("   taskID=".print_r($taskID, true), LOG_DEBUG);           	
			// get native desktop uploader state for thrift API
			$folders = CakePhpHelper::_model_getFolderState($taskID, true);
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
			$filtered = CakePhpHelper::_model_getFiles($taskID, $folderPath);
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
ThriftController::log("***   ReportFolderUploadComplete, folder={$folderPath}, taskID=".print_r($taskID, true), LOG_DEBUG);
			$data['ThriftFolder']['native_path']=$folderPath;
			$data['ThriftFolder']['is_scanned']=1;
			$ret = CakePhpHelper::_model_setFolderState($taskID, $data);
			// check GetFolders() to set TaskId->IsCancelled if empty
			$remaining_folders = $this->GetFolders($taskID);
			return $ret;
        }  		
		/**
		 * Sets the number of files expected to be uploaded from a folder.
		 * 	called by native uploader after folder scan complete
		 * @param $taskID snaphappi_api_TaskID
		 * @param $count int
		 */
        public function ReportFileCount($taskID, $folderPath, $count) {
ThriftController::log("***   ReportFileCount, folder={$folderPath}, count={$count}, taskID=".print_r($taskID, true), LOG_DEBUG);
			$data['ThriftFolder']['native_path']=$folderPath;
			$data['ThriftFolder']['count']=$count;
			$ret = CakePhpHelper::_model_setFolderState($taskID, $data);
			return $ret;
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
			$data = CakePhpHelper::_loginFromAuthToken($taskID);
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
        	$data = CakePhpHelper::_loginFromAuthToken($id); 
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
        public function UploadFile($id, $path, $filedata) {
ThriftController::log("###   UploadFile(), AuthToken={$id->AuthToken}, path={$path}", LOG_DEBUG);
debug("###   UploadFile(), AuthToken={$id->AuthToken}, path={$path}");
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($id);
			$session = & ThriftController::$session;

			// TODO: change to 'path.nativeUploader.folder_basepath'
			$os = Configure::read('Config.os');
        	$upload_basepath = cleanpath(Configure::read('path.fileUploader.folder_basepath').AppController::$userid, $os );
			// make relpath from path
			$relpath = str_replace(':','', $path);
			$fullpath = cleanpath($upload_basepath.DS.$relpath);
			try {
				if (!file_exists(dirname($fullpath))) {
					$old_umask = umask(0);
					$ret = mkdir(dirname($fullpath), 0777, true);
					umask($old_umask);
				}
				if (Configure::read('debug')) {
					if (!file_exists($fullpath)) {
debug("DEBUG CONFIG ERROR: to test UploadFile, file should already exist in upload folder, skipping actual file upload");					
					} 
				} else {
					$fp = fopen($fullpath,'w+b');
					if (!$fp) throw new Exception('URTaskUpload->UploadFile(): error opening file handle, fopen()');
					if (!fwrite($fp, $filedata)) throw new Exception('URTaskUpload->UploadFile(): error writing JPG data to file');
					if (!fclose($fp)) throw new Exception('URTaskUpload->UploadFile(): error fclose()');
				}

				/*
				 * at this point, the file is on the server at path=$fullpath
				 * now we need to add to DB and staging server
				 */
debug("UploadFile. Begin IMPORT to DB *********************");					
				$taskState = CakePhpHelper::_model_getTaskState($id);
				$providerKey = $session['ProviderAccount']['provider_key'];   // $id->DeviceID;
debug("CakePhpHelper::__importPhoto({$upload_basepath}, {$fullpath}, {$path}, {$providerKey}, {$taskState['BatchId']})");
				$response = CakePhpHelper::__importPhoto($upload_basepath, $fullpath, $path, $taskState['BatchId'], $isOriginal=false);	// autoRotate=false
debug("UploadFile. ******** IMPORT to DB COMPLETE *********************");	
debug($response);
ThriftController::log("************* UploadFile. IMPORT to DB complete ***********************", LOG_DEBUG);			 
ThriftController::log($response, LOG_DEBUG);			
				if ($response['success']) {
					/*
					 * update TaskID[FileUpdateCount] to Thrift, not to DB
					 */
					if ($id->Session) CakePhpHelper::_model_setTaskState($id, array('FileUpdateCount'=>1));  
					
					
				} else if (!empty($response['message']['DuplicateFoundException'])) {
					$thrift_exception['ErrorCode'] = ErrorCode::DataConflict;
					$thrift_exception['Information'] = "Duplicate File on upload";
					ThriftController::log("###   SystemException=".print_r($thrift_exception,true), LOG_DEBUG);
					$taskState = CakePhpHelper::_model_setTaskState($id, array('DuplicateFileException'=>1));
					// when threshold is passed, set TaskID->IsCancelled=1
					throw new snaphappi_api_SystemException($thrift_exception);					
				} else 
					throw new Exception("CakePhpHelper::__importPhoto(): Error importing to DB, response=".print_r($response, true));
			} catch (snaphappi_api_SystemException $e) {
					throw $e;
			} catch (Exception $e) {
				// log Thrift SystemException
				$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
				$thrift_exception['Information'] = $e->getMessage();
ThriftController::log("*****   SystemException from UploadFile(): ".print_r($thrift_exception,true), LOG_DEBUG);
debug("*****   SystemException from UploadFile(): ".print_r($thrift_exception,true));
				$taskState = CakePhpHelper::_model_setTaskState($id, array('OtherException'=>1));
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