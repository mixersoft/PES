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
	public function stripDeviceIdFromNativePath($data) {
		$deviceAndFilepath = $data['Asset']['native_path'];
		$data['Asset']['native_path'] = substr($deviceAndFilepath, strpos($deviceAndFilepath,'~')+1);
		return $data;
	}
}

class CakePhpHelper {
		public static $DUPLICATE_FILE_EXCEPTION_THRESHOLD = 4;
		public static $FILENOTFOUND_EXCEPTION_THRESHOLD = 4;
		public static $UPLOADFAILED_EXCEPTION_THRESHOLD = 4;
		public static $OTHER_EXCEPTION_THRESHOLD = 4;
		
		public static function _login($userData) {
			$authenticated = ThriftController::$controller->Auth->login(array('User'=>$userData));
			if (!$authenticated) return false;
			// Session::write('Auth.user', ThriftController::$controller->Auth->user());				
			AppController::$userid = ThriftController::$controller->Auth->user('id');
			$role = array_search($userData['primary_group_id'], Configure::read('lookup.roles'), true);
			AppController::$role = $role;
			return true;
		}
		
		/**
		 * authenticate the User account from ProviderAccount.auth_token embedded in $taskID
		 * 		AppController::$userid, AppController::$role will be set here
		 * 	will automatically bind TaskID->DeviceID to TaskID->Session
		 * @param $taskID, from $taskID
		 * @return aa $data[ProviderAccount,Owner,ThriftSession,ThriftDevice] 
		 */
		public static function _loginFromAuthToken($taskID) {
			// start ThriftSession
			ThriftController::$controller->load_CakePhpThriftSessionFromTaskID($taskID);
			try {
				/*
				 * - finds pa with authToken, returns data[ProviderAccount], data[Owner]
				 */  
				$data = ThriftController::$controller->ProviderAccount->thrift_findByAuthToken($taskID->AuthToken);
				if (!$data) throw new Exception("Error: authToken not found, authToken={$taskID->AuthToken}");	
				$authenticated = CakePhpHelper::_login($data['Owner']);
				if (!$authenticated) throw new Exception("Error: authToken invalid, authToken={$taskID->AuthToken}");
				
				// attach to Session, bind DeviceID if necessary
				$sessionId = $taskID->Session;
				$session = ThriftController::$controller->ThriftSession->checkDevice($sessionId, $taskID->DeviceID);
				if (!$session) $session = ThriftController::$controller->ThriftSession->bindDeviceToSession($sessionId, $taskID->AuthToken, $taskID->DeviceID);
				$data = array_merge($data, $session);
				ThriftController::$session = $data;
				return $data;
			} catch (Exception $e) {
				ThriftController::log("==============   ".$e->getMessage(), LOG_DEBUG);
				$thrift_exception['ErrorCode'] = ErrorCode::InvalidAuth;
				$thrift_exception['Information'] = $e->getMessage();
debug($thrift_exception);				
				//TODO: UI should notify the user to relaunch HelperApp with new token, or reschedule
ThriftController::log("*****   SystemException from _loginFromAuthToken(): ".print_r($thrift_exception,true), LOG_DEBUG);				
				throw new snaphappi_api_SystemException($thrift_exception);	
			}
		}

		/**
		 * Get DeviceID for provided session
		 * NOTE: manually login by authToken
		 * @param $authToken, string, same as TaskID->AuthToken 
		 * @param $sessionId, string, same as TaskID->Session
		 * @return the device ID associated with this session or empty string
		 */ 
		public static function _getDeviceId($authToken, $sessionId) {
			$data = ThriftController::$controller->ProviderAccount->thrift_findByAuthToken($authToken);
			if (!$data) throw new Exception("Error: authToken not found, authToken={$authToken}");	
			$device = ThriftController::$controller->ThriftSession->findDevice($sessionId);
			if (empty($device)) 
				throw new Exception("Error: sessionId invalid, sessionId={$sessionId}");
			else if (empty($device['ThriftDevice'])) return '';
			
			// $authenticated = CakePhpHelper::_login($data['Owner']);
			// if (!$authenticated) throw new Exception("Error: authToken invalid, authToken={$authToken}");
			
			$taskID = new snaphappi_api_TaskID(
				array(
				    'AuthToken' => $authToken,
				    'Session' => $sessionId,
				    'DeviceID' => $device['ThriftDevice']['device_UUID'],
				)
			);
			CakePhpHelper::_loginFromAuthToken($taskID);
			return $device['ThriftDevice']['device_UUID'];
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
		public static function _getFolderState($taskID, $isWatched = null) {
// ThriftController::log("_getFolderState taskID=".print_r((array)$taskID, true), LOG_DEBUG);			
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			$thrift_device_id = $session['ThriftDevice']['id'];
			$thrift_GetFolders = ThriftController::$controller->ThriftFolder->findByThriftDeviceId(
				$thrift_device_id, $isWatched
			);
// ThriftController::log("_getFolderState, session=".print_r($session, true), LOG_DEBUG);			
// ThriftController::log("_getFolderState, thrift_GetFolders=".print_r($thrift_GetFolders, true), LOG_DEBUG);	
			$folders = array();
			foreach ($thrift_GetFolders as $i=>$row){
				if ($row['ThriftFolder']['is_not_found']) {
					// skip if not found was marked in this session
					if (strtotime($row['ThriftFolder']['modified']) > strtotime($session['ThriftSession']['modified'])) {
						continue;		// skip notFound folders after session update
					} else 
						ThriftController::log("row['ThriftFolder']['is_not_found'], path={$row['ThriftFolder']['native_path']}", LOG_DEBUG);
				}
				$folder =  array(
					'folder_path'=> $row['ThriftFolder']['native_path'],
					'is_scanned' => $row['ThriftFolder']['is_scanned'],
            		'is_watched' => $row['ThriftFolder']['is_watched'],
					'is_not_found' => $row['ThriftFolder']['is_not_found'],            		
            		'count' => $row['ThriftFolder']['count'],
				);
				$folders[] = $folder;
			}
			return $folders;
		}

		/**
		 * update ThriftFolder, using native_path as key 
		 * @param $taskID
		 * @param $data, data[ThriftFolder]
		 */ 
		public static function _setFolderState($taskID, $data) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			$thrift_device_id = $session['ThriftDevice']['id'];
			$ret = ThriftController::$controller->ThriftFolder->updateFolder($thrift_device_id, $data);
ThriftController::log("############################## thrift_SetFolders=".print_r($ret, true), LOG_DEBUG);				
			return $ret;
		}

		/**
		 * add folder
		 * @param $taskID, from $taskID
		 * @param $nativePath String, 
		 * 		or for $options['delete']=1: $hash int crc32($nativePath)
		 * @param $options array, $options['is_scanned], $options['is_watched]=1 adds as watched folder
		 * @throws Exception "Error: Folder already exists" on duplicate key
		 */
		public static function _addFolder($taskID, $nativePath, $options=array()) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
// ThriftController::log("*****   _addFolder(): cakephp session=".print_r($session, true), LOG_DEBUG);			
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			$thrift_device_id = $session['ThriftDevice']['id'];
			if (!empty($options['delete'])) {
				$ret = ThriftController::$controller->ThriftFolder->deleteFolder(
					$thrift_device_id, $nativePath
				);
			} else {
ThriftController::log("*****   _addFolder(): deviceId:{$thrift_device_id} session:{$taskID->Session} path:{$nativePath}", LOG_DEBUG); 				
				$ret = ThriftController::$controller->ThriftFolder->addFolder(
					$thrift_device_id, $nativePath, $options
				);
			}
			if ($ret) {
				// bump TaskState.FolderUpdateCount to uploader app catches it
// ThriftController::log("*************** _addFolder to update folder count", LOG_DEBUG);				
				$options = array(
					'FolderUpdateCount'=>1, 
					'IsCancelled'=>0,		// set TaskState active after change
				);
				CakePhpHelper::_setTaskState($taskID, $options);
			}
			return $ret;
		}
		
		/**
		 * get Task state for thrift api
		 * 		called by GetFolders, GetWatchedFolders
		 * WARNING: the task does NOT know the deviceId, must be posted by client
		 * 
		 * TODO: move to Model/DB table?
		 * TODO: should method be moved to the Model under the native desktop uploader
		 * 
		 * @return aa 
		 */
		public static function _getTaskState($taskID) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			$data = ThriftController::$controller->ThriftSession->getTaskState($session['ThriftSession']['id']);
			ThriftController::$session['ThriftSession'] = $data['ThriftSession'];
			$thrift_GetTask = array_filter_keys(ThriftController::$session['ThriftSession'],ThriftSession::$ALLOWED_UPDATE_KEYS);
// ThriftController::log("_getTaskState() state=".print_r($thrift_GetTask, true), LOG_DEBUG);			
			return $thrift_GetTask; 
		}
		public static function _setTaskState($taskID, $options) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			$thrift_GetTask = array();
			$thrift_GetTask = array_intersect_key(CakePhpHelper::_getTaskState($taskID), $options);
			// save to DB
			if (isset($options['FileUpdateCount']) && $thrift_GetTask['FileUpdateCount']) {
				$thrift_GetTask['FileUpdateCount'] += 1;
				unset($options['FileUpdateCount']);
			} 
			if (isset($options['FolderUpdateCount']) && $thrift_GetTask['FolderUpdateCount']) {
				$thrift_GetTask['FolderUpdateCount'] += 1;
				unset($options['FolderUpdateCount']);
			} 
			
			$exceptions = array('DuplicateFileException', 'FileNotFoundException', 'UploadFailedException', 'OtherException');
			foreach ($exceptions as $exception) {
				if (isset($options[$exception])) {
					if (!isset($thrift_GetTask[$exception])) $thrift_GetTask[$exception] = 1;
					else $thrift_GetTask[$exception] += 1;
	ThriftController::log("**** WARNING: {$exception} count={$thrift_GetTask[$exception]}", LOG_DEBUG);				
					unset($options[$exception]);
				} 
			}
			
			
			$thrift_GetTask = array_merge($thrift_GetTask, $options);
			// handle Exception thrisholds, or save to DB
			$shutdown = false;
			if (isset($thrift_GetTask['DuplicateFileException']) && $thrift_GetTask['DuplicateFileException'] > CakePhpHelper::$DUPLICATE_FILE_EXCEPTION_THRESHOLD) {
				$message = "DuplicateFileException exceeds threshold, count=".$thrift_GetTask['DuplicateFileException'];
				/*
				 * TODO: instead of shutdown, should we just skip the folder and try the next one, 
				 * if any. at least just for this session. see GetFolderState for isNotFound
				 */ 				
				$shutdown = true;
			}
			if (isset($thrift_GetTask['FileNotFoundException']) && $thrift_GetTask['FileNotFoundException'] > CakePhpHelper::$FILENOTFOUND_EXCEPTION_THRESHOLD) {
				$message = "FileNotFoundException exceeds threshold, count=".$thrift_GetTask['FileNotFoundException'];
				$shutdown = true;
			}
			if (isset($thrift_GetTask['UploadFailedException']) && $thrift_GetTask['UploadFailedException'] > CakePhpHelper::$UPLOADFAILED_EXCEPTION_THRESHOLD) {
				$message = "UploadFailedException exceeds threshold, count=".$thrift_GetTask['UploadFailedException'];
				$shutdown = true;
			}
			if (isset($thrift_GetTask['OtherException']) && $thrift_GetTask['OtherException'] > CakePhpHelper::$OTHER_EXCEPTION_THRESHOLD) {
				$message = "OtherException exceeds threshold, count=".$thrift_GetTask['OtherException'];
				$shutdown = true;
			}			
			// save to db
			$thrift_GetTask = ThriftController::$controller->ThriftSession->saveTaskState(
				$session['ThriftSession']['id'], $thrift_GetTask
			); 
if (!isset($thrift_GetTask['FileUpdateCount']))			
	ThriftController::log("_setTaskState() state=".print_r($thrift_GetTask, true), LOG_DEBUG);				
			if ($shutdown) CakePhpHelper::_shutdown_client($taskID, $message);
			return $thrift_GetTask;
		}


		public static function _deleteTask($taskID) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftSession->delete($session['ThriftSession']['id']);
		}	
		
		public static function _getFiles($taskID, $folderPath) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			
			$device_files = ThriftController::$controller->ThriftFolder->getFiles(
				$session['ThriftDevice'], 
				$folderPath
			);
// ThriftController::log("_getFiles() BEFORE FILTER, count=".count($device_files), LOG_DEBUG);			
			if (!empty($folderPath)) {
				FolderContains::$folderPath = $folderPath; 
				FolderContains::$case_sensitive = strpos($folderPath,':\\') !== false;
// ThriftController::log("_getFiles() case sensitive=".FolderContains::$case_sensitive." BEFORE filter=".print_r($device_files, true), LOG_DEBUG);				

				$device_files = array_map('FolderContains::stripDeviceId', $device_files);
				/*
				 * Skip if we are filtering in the DB using LIKE, see ThriftFolder::getFiles()
				 */ 
				// $device_files = array_filter($device_files, 'FolderContains::asset');	
			} else {
				$device_files = array_filter($device_files);
				$device_files = array_map('FolderContains::stripDeviceId', $device_files);
			}
			// filter for files in folderPath, or use SQL LIKE clause
// ThriftController::log("_getFiles() AFTER FILTER, count=".count($device_files), LOG_DEBUG);				
// ThriftController::log("_getFiles() AFTER FILTER=".print_r($device_files, true), LOG_DEBUG);			
			return $device_files;
		}		

		/**
		 * for UO Tasks
		 */
		public static function _getOriginalFilesToUpload($taskID) {
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
			$session = & ThriftController::$session;
			ThriftController::$controller->ThriftFolder = ThriftController::$controller->ThriftSession->ThriftDevice->ThriftFolder;
			
			$data = ThriftController::$controller->ThriftFolder->getOriginalFiles(
				$session['ThriftDevice'] 
			);
			if ($data){
				$nativePath = $data[0]['Asset']['native_path'];
				FolderContains::$case_sensitive = strpos($nativePath,':\\') !== false;
	// ThriftController::log("_getFiles() case sensitive=".FolderContains::$case_sensitive." BEFORE filter=".print_r($device_files, true), LOG_DEBUG);				
				
	// ThriftController::log("_getFiles() BEFORE FILTER, count=".count($device_files), LOG_DEBUG);			
				$data = array_map('FolderContains::stripDeviceIdFromNativePath', $data);
				// filter for files in folderPath, or use SQL LIKE clause
	// ThriftController::log("_getFiles() AFTER FILTER, count=".count($device_files), LOG_DEBUG);				
	// ThriftController::log("_getFiles() AFTER FILTER=".print_r($device_files, true), LOG_DEBUG);			
			}
			return $data;
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
				if (ThriftController::$session['ThriftSession']['IsCancelled']) return;
				
				ThriftController::log("*** using IsCancelled", LOG_DEBUG);
				// UR task, use IsCancelled
				CakePhpHelper::_setTaskState($taskID, array('IsCancelled'=>1));
				return;
				// // when called from _setTaskState(), make sure TaskState is not overwritten with a cached copy of $options
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
	 * @param mixed, 
	 * 		$origPath String, the native path on the device where the uploaded file was found
	 * 		$options Array, $options[id] = uuid, $options[origPath] = $origPath 
	 * @param $batchId int, unixtime(), used to find files uploaded at the same time
	 * @param $isOriginal boolean, the uploaded file is the original file from device, not resampled to preview size
	 */
	public static function __importPhoto( $basepath, $fullpath, $origPath, $batchId, $isOriginal=false){
		if (empty( ThriftController::$session)) throw new Exception("ERROR: CakePhpHelper::__importPhoto - Thrift Session is not defined");
		if (is_array($origPath)) {
			$asset_id = $origPath['id'];
			$origPath = $origPath['origPath'];
		} else $asset_id = null;
		// setup meta data
		$BATCH_ID = $batchId; // WARNING: session not preseved between calls
		$PROVIDER_NAME = 'native-uploader';
		$data = array();
		$data['Asset']['id'] = $asset_id;				// not null for UO task
		$data['Asset']['batchId'] = $BATCH_ID;
		$devicePrefix = ThriftController::$session['ThriftDevice']['id'].ThriftFolder::$DEVICE_SEPARATOR;
		$device_origPath = $devicePrefix . $origPath;
		$data['Asset']['isThriftAPI'] = 1;			// this is used for getAssetHash()
		$data['Asset']['origPath'] = $origPath;		// this is used for getAssetHash()
		$data['Asset']['rel_path'] = $device_origPath;	
		$data['ProviderAccount']['provider_name'] = ThriftController::$session['ProviderAccount']['provider_name'];
		$data['ProviderAccount']['provider_key'] = ThriftController::$session['ProviderAccount']['provider_key'];
		$data['ProviderAccount']['display_name'] = ThriftController::$session['ProviderAccount']['display_name']; 
		/***************************************************************
		 * experimental: replace mode, replace existing with original
		 * 	pass to Asset::addIfNew()
		 ***************************************************************/ 
		$data['Asset']['replace-preview-with-original'] = $isOriginal;
		if (empty($data['Asset']['id'])) {
			$data['Asset']['replace-preview-by-native-path'] = $device_origPath;
		}
// debug($data['Asset']);		
		$ret = true;
		$Import = loadComponent('Import', ThriftController::$controller);
		if (!isset(ThriftController::$controller->Asset)) ThriftController::$controller->Asset = ClassRegistry::init('Asset');
		
		/*
		 * Load/initialize PermissionableComponent, AFTER PermissionableBehavior attached
		 * IMPORTANT! Permissionable REQUIRED on Import so that the AssetPermission is also created
		 */ 		
		if (!ThriftController::$controller->Asset->Behaviors->enabled('Permissionable')) {
			ThriftController::$controller->Asset->Behaviors->enable('Permissionable');
		}
		if (!class_exists('Permissionable') || (Permissionable::$user_id != AppController::$userid)) {
			App::import('Component', 'Permissionable.Permissionable');
			ThriftController::$controller->Permissionable = & new PermissionableComponent();
			ThriftController::$controller->Permissionable->initialize(ThriftController::$controller);
ThriftController::log(" *** ThriftController->PermissionableComponent initialized 	",LOG_DEBUG);
		}
		
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
// debug($data);				
// debug($paData);	
		/****************************************************
		 * setup data['Asset'] to create new Asset
		 */
		$profile = ThriftController::$controller->getProfile();		// defined in AppController
		if (isset($profile['Profile']['privacy_assets'])) {
			$data['Asset']['perms'] = $profile['Profile']['privacy_assets'];
		}	
		
// ThriftController::log("CakePhpHelper::__importPhoto, paData=".print_r($paData, true), LOG_DEBUG);			



		$assetData = ThriftController::$controller->Asset->addIfNew($data['Asset'], $paData['ProviderAccount'], $basepath, $fullpath, $isOriginal, $response);
// ThriftController::log("CakePhpHelper::__importPhoto, this->Asset->addIfNew(), response=".print_r($response, true), LOG_DEBUG);				
// ThriftController::log("CakePhpHelper::__importPhoto, this->Asset->addIfNew(), asset=".print_r($assetData, true), LOG_DEBUG);		

		$copyToStaging = true;
		if (isset($response['message']['DuplicateAssetFound'])) {
			// this is a duplicate file, if we are uploading original, replace JPG
			if ($response['message']['DuplicateAssetFound']=='FOUND duplicate Asset, Photo fields updated' 
					&& $isOriginal
			) {
				// upload Original 
				$response['message']['DuplicateAssetFound'] = "Replaced existing Asset file with Original file";
			} else if ( $response['message']['DuplicateAssetFound'] == "ERROR updating fields of duplicate Asset"
				&& $isOriginal
			) {
				// upload Original: error updating DB when uploading Original from UO task
				$copyToStaging = false;
				$response['message']['NoPreviewFoundException'] = 1;
				$response['message']['OtherException'] = 1;
				$ret = false;
			} else if ( $response['message']['DuplicateAssetFound'] == 'FOUND duplicate Asset, converting provider from desktop to native-uploader'
				&& !$isOriginal
			) {
				$copyToStaging = false;	
			} else if ( $response['message']['DuplicateAssetFound']
				&& !$isOriginal
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
		if ($copyToStaging) {
			/*
			 *  move original file to staging server, replace preview file,
			 * 	NOTE: for DUPLICATE files, 
			 * 		asset fields are updated in DB, see Asset::__updateAssetFields()
			 * 		files are COPIED only if larger
			 */   
			$src = json_decode($assetData['Asset']['json_src'], true);
			$stage=array('src'=>$fullpath, 'dest'=>$src['root']);
	// ThriftController::log("CakePhpHelper::__importPhoto staging files, ".print_r($stage, true), LOG_DEBUG);		
		 	if ($ret3 = $Import->import2stage($stage['src'], $stage['dest'], null, $move = true)) {
		 		$response['message'][]="file staged successfully, dest={$stage['dest']}";
			} else $response['message'][]="Error staging file, src={$stage['src']} dest={$stage['dest']}";
		 	$ret = $ret && $ret3;
		} else {
			unlink($fullpath);
		}

	 	$response['success'] = $ret;
		$response['response'] = $assetData;
		return $response;
	}		
	
	function _getImageHash($taskID, $asset_id) {
		if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($taskID);
		$session = & ThriftController::$session;
		if (!isset(ThriftController::$controller->Asset)) ThriftController::$controller->Asset = ClassRegistry::init('Asset');
		$options = array(
			'conditions'=>array(
				'Asset.id'=>$asset_id,
				'Asset.provider_account_id'=>$session['ThriftDevice']['provider_account_id'],
		));
		
		/*
		 * Load/initialize PermissionableComponent, AFTER PermissionableBehavior attached
		 * IMPORTANT! Permissionable REQUIRED on Import so that the AssetPermission is also created
		 */ 		
		if (!ThriftController::$controller->Asset->Behaviors->enabled('Permissionable')) {
			ThriftController::$controller->Asset->Behaviors->enable('Permissionable');
		}
		if (!class_exists('Permissionable') || (Permissionable::$user_id != AppController::$userid)) {
			App::import('Component', 'Permissionable.Permissionable');
			ThriftController::$controller->Permissionable = & new PermissionableComponent();
			ThriftController::$controller->Permissionable->initialize(ThriftController::$controller);
ThriftController::log(" *** ThriftController->PermissionableComponent initialized 	",LOG_DEBUG);
		}		
		
		$data = ThriftController::$controller->Asset->find('first', $options);
		if (!empty($data['Asset']['asset_hash'])) {
			$image_hash = $data['Asset']['asset_hash'];  
		} else if ($data) {
			App::import('Component', 'Gist');
			ThriftController::$controller->Gist = & new GistComponent();
			ThriftController::$controller->Gist->initialize(ThriftController::$controller);
			$image_hash =  ThriftController::$controller->Gist->getImageHash($asset_id);
			ThriftController::$controller->Asset->id = $asset_id;
			ThriftController::$controller->Asset->saveField('asset_hash', $image_hash);
			
		} else {
			throw new Exception("ERROR: _getImageHash() asset_id not found, id={$asset_id}");
		}
		return $image_hash;
	}
}

class snaphappi_api_TaskImpl implements snaphappi_api_TaskIf {
	
        /**
		 * @param $taskID TaskID
		 * @return TaskState, 			 
		 * 	TaskState->IsCancelled Boolean (optional)
		 *  TaskState->FolderUpdateCount Int (optional), unique id for Folder state
		 *  TaskState->FileUpdateCount Int (optional), unique id for File state
		 *  TaskState->DeviceId UUID (optional), unique id for desktop device
		 */
        public function GetState($taskID) {
// ThriftController::log("***  API  GetState, deviceID=".print_r((array)$taskID, true), LOG_DEBUG);    
        	$state = CakePhpHelper::_getTaskState($taskID);
			if (empty($state)) {
				// NOTE: InvalidAuth Exception thrown from CakePhpHelper::_loginFromAuthToken()
				// $state['IsCancelled'] = true;
// ThriftController::log("***   GetState, state=".print_r($state,true), LOG_DEBUG);    				
			} else {
// ThriftController::log($state, LOG_DEBUG); 				
			}
        	$taskState = new snaphappi_api_TaskState($state);
        	return $taskState;
        }
        	
		/**
		 * Return the list of folders to scan for images, exclude watch folders
		 * called by interactive upload task, type=UR
		 * 	scan=1 || watch=0 || 
		 * @param $taskID snaphappi_api_TaskID
		 * @param $all, 
		 * 		if true, return only watched folders, 
		 *		if false return only unwatched folders, 
		 * 		if null return all folders,
		 * @return array of Strings
		 */
        public function GetFolders($taskID, $isWatch=false) {
ThriftController::log("***  API  GetFolders, taskID, isWatch={$isWatch}", LOG_DEBUG);
			// get native desktop uploader state for thrift API
			$folders = CakePhpHelper::_getFolderState($taskID, $isWatch);

			if (empty($folders)) {
ThriftController::log("***   GetFolders: NO NEW FOLDERS TO SCAN", LOG_DEBUG);					
				// Cancel Task right away, if all folders are is_scanned, 
				// but let Task run if there are no added folders
				$all_folders = CakePhpHelper::_getFolderState($taskID, null);
				if (1 && !empty($all_folders)) {
					// GetWatchFolders() called from different taskID
					CakePhpHelper::_setTaskState($taskID, array('IsCancelled'=>1));					
					
				}
			}				
			$folders = Set::extract('{n}.folder_path',$folders);
// ThriftController::log($folders, LOG_DEBUG);				
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
ThriftController::log("***  API  GetWatchedFolders, taskID", LOG_DEBUG);
			// get native-uploader state for thrift API
			// create a new Session for scheduled Tasks
			$options['session_id'] = $taskID->Session;
			$session = ThriftController::$controller->ThriftSession->newSession($options);
			$folders = CakePhpHelper::_getFolderState($taskID, true);
			$folders = Set::extract('{n}.folder_path',$folders);
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
ThriftController::log("***  API  GetFiles, taskID, folderPath={$folderPath}", LOG_DEBUG);
			$filtered = CakePhpHelper::_getFiles($taskID, $folderPath);
        	return $filtered;
        }                
		/**
		 * Report that a folder could not be searched. and prepare for restart/retry
		 * update UX, prompt for moved TopLevelFolder? 
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String
		 */
        public function ReportFolderNotFound($taskID, $folderPath) {
ThriftController::log("***  API  ReportFolderNotFound, taskID, folderPath={$folderPath}", LOG_DEBUG);
			$data['ThriftFolder']['native_path']=$folderPath;
			$data['ThriftFolder']['is_not_found']=1;
			$ret = CakePhpHelper::_setFolderState($taskID, $data);
			// check GetFolders() to set TaskId->IsCancelled if none, 
			// NOTE: notFound folders will be hidden for the current sessionId based on session modified time
			$remaining_folders = $this->GetFolders($taskID);
        	return;
        }
		
		/**
		 * Report that all files in a folder have been uploaded.
		 * 	for watched=0, mark folder scan=0
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String
		 */
        public function ReportFolderUploadComplete($taskID, $folderPath) {
ThriftController::log("***  API  ReportFolderUploadComplete, taskID, folderPath={$folderPath}", LOG_DEBUG);
			$data['ThriftFolder']['native_path']=$folderPath;
			$data['ThriftFolder']['is_scanned']=1;
			$ret = CakePhpHelper::_setFolderState($taskID, $data);
			// check GetFolders() to set TaskId->IsCancelled if empty
			$remaining_folders = $this->GetFolders($taskID);
			return; 
        }  		
		/**
		 * Sets the number of files expected to be uploaded from a folder.
		 * 	called by native uploader after folder scan complete
		 * @param $taskID snaphappi_api_TaskID
		 * @param $count int
		 */
        public function ReportFileCount($taskID, $folderPath, $count) {
ThriftController::log("***  API  ReportFileCount, taskID, folderPath={$folderPath}, count={$count}", LOG_DEBUG);
			$data['ThriftFolder']['native_path']=$folderPath;
			$data['ThriftFolder']['count']=$count;
			$data['ThriftFolder']['is_not_found']=0;
			$ret = CakePhpHelper::_setFolderState($taskID, $data);
			return $ret;
        }
		/**
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
        public function GetDeviceID($authToken, $sessionId) {
ThriftController::log("***  API  GetDeviceID, authToken={$authToken} , sessionId={$sessionId}", LOG_DEBUG);
			try {
	        	$deviceId = CakePhpHelper::_getDeviceId($authToken, $sessionId);
				if (!$deviceId) throw new Exception('Error: deviceId is not available yet');
ThriftController::log(">>>    GetDeviceID() DeviceID={$deviceId} ", LOG_DEBUG);
				return $deviceId;
			} catch (Exception $e) {
				$msg = explode(',', $e->getMessage());
				switch($msg[0]) {
					case 'Error: authToken not found':
					case 'Error: authToken invalid':
					case 'Error: sessionId invalid':
						$thrift_exception['ErrorCode'] = ErrorCode::InvalidAuth;
						break;
					case 'Error: deviceId is not available yet':
						$thrift_exception['ErrorCode'] = ErrorCode::DataConflict;
						break;						
					default:
						$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
						break;
				}
				$thrift_exception['Information'] = $e->getMessage();
ThriftController::log("*****   SystemException from GetDeviceID(): ".print_r($thrift_exception,true), LOG_DEBUG);				
				throw new snaphappi_api_SystemException($thrift_exception);		
			}
        }		 
		/**
		 * Return the number of files expected to be uploaded from a folder.
		 * called by UI for progress bar stats
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String 
		 */
        public function GetFileCount($taskID, $folderPath) {
ThriftController::log("***  API  GetFileCount, taskID, folderPath={$folderPath}", LOG_DEBUG);        	
			$data = CakePhpHelper::_loginFromAuthToken($taskID);
			$thrift_GetFolders = CakePhpHelper::_getFolderState();
			foreach ($thrift_GetFolders as $i=>$folder) {
				if ($folder['folder_path'] == $folderPath) {
					return $folder['count'];	 
				};
			}
			throw new Exception("GetFileCount(): folder not found, path={$folderPath}");
        	return;
        }  		 
		/**
		 * Add a top level folder to scan
		 * @param $taskID snaphappi_api_TaskID,
		 * @param $path String,
		 * @throws  snaphappi_api_SystemException(), 
		 * 		ErrorCode::DataConflict if folder already exists for the current DeviceID
		 * 		ErrorCode::Unknown for everything else
		 */
        public function AddFolder($taskID, $path) {
ThriftController::log("***  API  AddFolder, taskID, path={$path}", LOG_DEBUG);        	
        	try {
        		$data = CakePhpHelper::_addFolder($taskID, $path); 
				return;
			} catch (Exception $e) {
				$msg = explode(',', $e->getMessage());
				switch($msg[0]) {
					case 'Error: Folder already exists':
						$thrift_exception['ErrorCode'] = ErrorCode::DataConflict;
						break;
					default:
						$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
						break;
				}
				$thrift_exception['Information'] = $e->getMessage();
ThriftController::log("*****   SystemException from AddFolder(): ".print_r($thrift_exception,true), LOG_DEBUG);				
				throw new snaphappi_api_SystemException($thrift_exception);					
			} catch (snaphappi_api_SystemException $e) {
					throw $e;
			}
        	
        }
		
        
		/**
		 * TODO: add to Thrift file, updates schema directly 
		 * allows pause/resume from UI by setting TaskState->IsCancelled
		 * @param $taskID snaphappi_api_TaskID,
		 * @param $cancel String,
		 * @throws  snaphappi_api_SystemException(), 
		 * 		ErrorCode::Unknown for everything else
		 */
        public function SetTaskState($taskID, $pause=true) {
ThriftController::log("***  API  SetTaskState, taskID, pause={$pause}", LOG_DEBUG);        	
        	try {
				if ($pause) {
					$ret = CakePhpHelper::_setTaskState($taskID, array('IsCancelled'=>1));
				} else {
					$ret = CakePhpHelper::_setTaskState($taskID, array('IsCancelled'=>0));
				}			
				return array('IsCancelled'=> $ret['ThriftSession']['IsCancelled']);
			} catch (Exception $e) {
				$msg = explode(',', $e->getMessage());
				switch($msg[0]) {
					default:
						$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
						break;
				}
				$thrift_exception['Information'] = $e->getMessage();
ThriftController::log("*****   SystemException from SetTaskState(): ".print_r($thrift_exception,true), LOG_DEBUG);				
				throw new snaphappi_api_SystemException($thrift_exception);					
			} catch (snaphappi_api_SystemException $e) {
					throw $e;
			}
        }      		
        
		/**
		 * TODO: add to Thrift file 
		 * Set top level folder to watched
		 * @param $taskID snaphappi_api_TaskID,
		 * @param $path String, or $hash int crc32($path)
		 * @param $watched Boolean, set is_watched.		 * 
		 * @throws  snaphappi_api_SystemException(), 
		 * 		ErrorCode::Unknown for everything else
		 */
        public function SetWatchedFolder($taskID, $path, $watched) {
ThriftController::log("***  API  SetWatchedFolder, taskID, path={$path}, watched={$watched}", LOG_DEBUG);        	
        	try {
        		if (is_numeric($path)) $data['ThriftFolder']['native_path_hash'] = $path;
				else $data['ThriftFolder']['native_path'] = $path;
				$data['ThriftFolder']['is_watched'] = $watched; 
        		$data = CakePhpHelper::_setFolderState($taskID, $data); 
				return $data;
			} catch (Exception $e) {
				$msg = explode(',', $e->getMessage());
				switch($msg[0]) {
					default:
						$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
						break;
				}
				$thrift_exception['Information'] = $e->getMessage();
ThriftController::log("*****   SystemException from SetWatchedFolder(): ".print_r($thrift_exception,true), LOG_DEBUG);				
				throw new snaphappi_api_SystemException($thrift_exception);					
			} catch (snaphappi_api_SystemException $e) {
					throw $e;
			}
        }        
		
		/**
		 * Remove a top level folder to scan
		 * @param $taskID snaphappi_api_TaskID,
		 * @param $path String, or $hash int crc32($path)
		 * @throws  snaphappi_api_SystemException(), 
		 * 		ErrorCode::DataConflict if folder already exists for the current DeviceID
		 * 		ErrorCode::Unknown for everything else
		 */
        public function RemoveFolder($taskID, $path) {
ThriftController::log("***  API  RemoveFolder, taskID, path={$path}", LOG_DEBUG);        	
        	try { 
        		$data = CakePhpHelper::_addFolder($taskID, $path, array('delete'=>1)); 
				return $data;
			} catch (Exception $e) {
				$msg = explode(',', $e->getMessage());
				switch($msg[0]) {
					case 'Error: Folder already exists':
						$thrift_exception['ErrorCode'] = ErrorCode::DataConflict;
						break;
					default:
						$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
						break;
				}
				$thrift_exception['Information'] = $e->getMessage();
ThriftController::log("*****   SystemException from RemoveFolder(): ".print_r($thrift_exception,true), LOG_DEBUG);				
				throw new snaphappi_api_SystemException($thrift_exception);					
			} catch (snaphappi_api_SystemException $e) {
					throw $e;
			}
        	
        }
		
			
		/**
		 * save binary data from uploaded, *resampled* JPG file
		 * throw Exception on error
		 *
		 * @param $id TaskID,
		 * @param $path String, path on client, i.e. nativePath
		 * @param $data binary,
		 * @param $UploadInfo, snaphappi_api_UploadInfo
		 * @return void 
		 */
        public function UploadFile($id, $path, $filedata, $UploadInfo) {
ThriftController::log("***  API  UploadFile, taskID, path={$path}, UploadInfo=".print_r((array)$UploadInfo, true), LOG_DEBUG);        	
// debug("###   UploadFile(), AuthToken={$id->AuthToken}, path={$path}");
			if (!ThriftController::$session) CakePhpHelper::_loginFromAuthToken($id);
			$session = & ThriftController::$session;
// debug(ThriftController::$controller->Asset->Behaviors->attached());
		
			// TODO: change to 'path.nativeUploader.folder_basepath'
			$os = Configure::read('Config.os');
        	$upload_basepath = cleanpath(Configure::read('path.fileUploader.folder_basepath').AppController::$userid, $os );
			// make relpath from path
			$relpath = str_replace(':','', $path);
			$uploadpath = cleanpath($upload_basepath.DS.$relpath);
			
			try {
				// save filedata in upload folder_basepath, still have to move to STAGING
				if (!file_exists(dirname($uploadpath))) {
					$old_umask = umask(0);
					$ret = mkdir(dirname($uploadpath), 0777, true);
					umask($old_umask);
				}
				if (Configure::read('debug')) {
					if (!file_exists($uploadpath)) {
debug("DEBUG CONFIG ERROR: to test UploadFile, file should already exist in upload folder, skipping actual file upload");					
					} 
				} else {
					$fp = fopen($uploadpath,'w+b');
					if (!$fp) throw new Exception('URTaskUpload->UploadFile(): error opening file handle, fopen()');
					if (!fwrite($fp, $filedata)) throw new Exception('URTaskUpload->UploadFile(): error writing JPG data to file');
					if (!fclose($fp)) throw new Exception('URTaskUpload->UploadFile(): error fclose()');
				}

				/*
				 * at this point, the file is on the server at path=$uploadpath
				 * now we need to add to DB and staging server
				 */
				
				$taskState = CakePhpHelper::_getTaskState($id);
				$providerKey = $session['ProviderAccount']['provider_key'];   // $id->DeviceID;
				
				
				$isOriginal = $UploadInfo->UploadType === UploadType::Original;
				if ($isOriginal) {  // UploadType::Original
					$options = array(
						'id'=>$UploadInfo->ImageID,
						'origPath'=>$path
					);
ThriftController::log("check for original at path={$uploadpath}", LOG_DEBUG);					
	debug("UploadFile ORIGINAL. Begin UPDATE to DB *********************");					
	debug("CakePhpHelper::__importPhoto({$upload_basepath}, {$uploadpath}, {$path}, {$providerKey}, {$taskState['BatchId']})");
					$response = CakePhpHelper::__importPhoto($upload_basepath, $uploadpath, $options, $taskState['BatchId'], $isOriginal);	// autoRotate=false
	debug("UploadFile. ******** UploadFile ORIGINAL, DB update complete *********************");	
	debug($response);
	ThriftController::log("************* UploadFile ORIGINAL, DB update complete ***********************", LOG_DEBUG);			 
	ThriftController::log($response['message'], LOG_DEBUG);			
					if ($response['success']) {
						/*
						 * update TaskID[FileUpdateCount] to Thrift, not to DB
						 */
						if ($id->Session) CakePhpHelper::_setTaskState($id, array('FileUpdateCount'=>1));  
						
						
					} else if (!empty($response['message']['NoPreviewFoundException'])) {
						$thrift_exception['ErrorCode'] = ErrorCode::DataConflict;
						$thrift_exception['Information'] = "Preview file was not found";
						$taskState = CakePhpHelper::_setTaskState($id, array('OtherException'=>1));
						// when threshold is passed, set TaskID->IsCancelled=1
	ThriftController::log("*****   SystemException from UploadFile(): ".print_r($thrift_exception,true), LOG_DEBUG);
						throw new snaphappi_api_SystemException($thrift_exception);					
					} else 
						throw new Exception("CakePhpHelper::__importPhoto(): Error uploading ORIGINAL to DB, response=".print_r($response, true));
						
					
				} else { // UploadType::Preview	
	debug("UploadFile. Begin IMPORT to DB *********************");					
	debug("CakePhpHelper::__importPhoto({$upload_basepath}, {$uploadpath}, {$path}, {$providerKey}, {$taskState['BatchId']})");
					$response = CakePhpHelper::__importPhoto($upload_basepath, $uploadpath, $path, $taskState['BatchId'], $isOriginal);	// autoRotate=false
	debug("UploadFile. ******** IMPORT to DB COMPLETE *********************");	
	debug($response);
	ThriftController::log("************* UploadFile. IMPORT to DB complete ***********************", LOG_DEBUG);			 
	ThriftController::log($response['message'], LOG_DEBUG);			
					if ($response['success']) {
						/*
						 * update TaskID[FileUpdateCount] to Thrift, not to DB
						 */
						if ($id->Session) CakePhpHelper::_setTaskState($id, array('FileUpdateCount'=>1));  
						
						
					} else if (!empty($response['message']['DuplicateFoundException'])) {
						$thrift_exception['ErrorCode'] = ErrorCode::DataConflict;
						$thrift_exception['Information'] = "Duplicate File on upload";
						$taskState = CakePhpHelper::_setTaskState($id, array('DuplicateFileException'=>1));
						// when threshold is passed, set TaskID->IsCancelled=1
	// debug("UploadFile DuplicateFoundException");						
	ThriftController::log("*****   SystemException from UploadFile(): ".print_r($thrift_exception,true), LOG_DEBUG);
						throw new snaphappi_api_SystemException($thrift_exception);					
					} else 
						throw new Exception("CakePhpHelper::__importPhoto(): Error importing to DB, response=".print_r($response, true));
				}
			} catch (snaphappi_api_SystemException $e) {
					throw $e;
			} catch (Exception $e) {
				// log Thrift SystemException
				$thrift_exception['ErrorCode'] = ErrorCode::Unknown;
				$thrift_exception['Information'] = $e->getMessage();
ThriftController::log("*****   SystemException from UploadFile(): ".print_r($thrift_exception,true), LOG_DEBUG);
				$taskState = CakePhpHelper::_setTaskState($id, array('OtherException'=>1));
			}
        	return;
        }


		/*
		 * Upload Original (UO) Task methods
		 */ 
		/**
		 * Return the list of all photos which are queued for uploading original JPG files
	 	 * for the given task.
		 * @param $taskID snaphappi_api_TaskID
		 * @return array of Strings
		 */
        public function GetFilesToUpload($taskID) {
ThriftController::log("***  API  GetFilesToUpload, taskID", LOG_DEBUG);        	
			$data = CakePhpHelper::_getOriginalFilesToUpload($taskID);
			$targets = array();
			$options = array('FilePath'=>null, 'Timestamp'=>123456789, 'Hash'=>0, 'FolderPath'=>'none');
			foreach ($data as $row ) {
				$options['FilePath'] = $row['Asset']['native_path'];
				// TODO: add drive separator to FilePath, i.e. C:
				$options['ExifOriginalTimestamp'] = $row[0]['DateTimeOriginal'];
				$options['DateTimeOriginal'] = $row[0]['DateTimeOriginal'];
				$options['ImageID'] = $row['Asset']['id'];
				$targets[] = new snaphappi_api_UploadTarget($options);
			}
			if (empty($targets)) {
				ThriftController::log("***   GetFilesToUpload: NO NEW FILE TO UPLOAD", LOG_DEBUG);					
				CakePhpHelper::_setTaskState($taskID, array('IsCancelled'=>1));					
			}
        	return $targets;
        }   
		/**
		 * Get an ImageHash from DB, 
		 *  check Asset.asset_hash, if null then
		 * 	call GistComponent::getImageHash [fullpath] see
		 * @param $taskID snaphappi_api_TaskID
		 * @param ImageID, $ImageID, type ImageID
		 * @return int(32), type ImageHash 
		 * @throws (1: SystemException systemException);
		 */
		public function GetImageHash ($taskID, $imageID) {
ThriftController::log("***  API  GetImageHash, taskID, imageID=".print_r((array)$imageID, true), LOG_DEBUG);			
			return CakePhpHelper::_getImageHash($taskID, $imageID);
		}
		/**
		 * ReportUploadFailedByID
		 * @param $taskID snaphappi_api_TaskID
		 * @param ImageID, $ImageID, type ImageID
		 * @return void
		 * @throws (1: SystemException systemException);
		 */
		public function ReportUploadFailedByID ($taskID, $imageID) {
ThriftController::log("***  API  ReportUploadFailedByID, taskID, imageID=".print_r((array)$imageID, true), LOG_DEBUG);			
			$taskState = CakePhpHelper::_setTaskState($id, array('UploadFailedException'=>1));
			return;
		}
		/**
		 * Report a failed upload.
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String
		 * @param $filePath String
		 * throws SystemException
		 */
        public function ReportUploadFailed($taskID, $folderPath, $filePath) {
ThriftController::log("***  API  ReportUploadFailed, taskID, folderPath={$folderPath}, path={$path}", LOG_DEBUG);        	
			$taskState = CakePhpHelper::_setTaskState($id, array('UploadFailedException'=>1));
        	return;
        }  		
		/**
		 * ReportFileNotFoundByID
		 * @param $taskID snaphappi_api_TaskID
		 * @param ImageID, $ImageID, type ImageID
		 * @return void 
		 * @throws (1: SystemException systemException);
		 */
		public function ReportFileNotFoundByID ($taskID, $imageID) {
ThriftController::log("***  API  ReportFileNotFoundByID, taskID, imageID=".print_r((array)$imageID, true), LOG_DEBUG);			
			$taskState = CakePhpHelper::_setTaskState($id, array('FileNotFoundException'=>1));
		}
		/**
		 * Report that a file could not be found. 
		 * @param $taskID snaphappi_api_TaskID
		 * @param $folderPath String
		 * @param $path String
		 */
        public function ReportFileNotFound($taskID, $folderPath, $path) {
ThriftController::log("***  API  ReportFileNotFound, taskID, folderPath={$folderPath}, path={$path}", LOG_DEBUG);        	
			$taskState = CakePhpHelper::_setTaskState($id, array('FileNotFoundException'=>1));
        	return;
        }		

}


/*
 * 4. process the Thrift service request
 */
process_THRIFT_SERVICE_REQUEST();



// error_log("Thrift Server ready, Service=".print_r($GLOBALS['THRIFT_SERVICE'], true));


?>