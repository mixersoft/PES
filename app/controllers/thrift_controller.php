<?php 
class ThriftController extends AppController {
    public $name = 'Thrift';
	public $uses = array('User', 'ProviderAccount', 'ThriftSession');
	
	public static $controller = null;
	public static $session = null;
	public static $current_version = '1-0';
	 
    public $helpers = array(
		// 'Time',
		// 'Text',
		// 'Layout',
	);

	public $autoRender = false;			// return response over thrift transport
    public $layout = false;
	
	public $USE_AuthToken_NOT_AUTH = array('test', 'service');
    
	function __construct() {
		// session_id('discard');  // TODO: randomize to avoid locking issues?
		// $this->_use_custom_thrift_session();
		parent::__construct();
		ThriftController::$controller = $this;
	}
	
    function beforeFilter() {
		if (in_array($this->action, $this->USE_AuthToken_NOT_AUTH)) {
			$debug = (isset($this->passedArgs[1])) ? $this->passedArgs[1] : 0;
			Configure::write('debug', $debug);
			// skip AppController::beforeFilter to avoid unnecessary Auth and Session stuff
		} else if ($this->action == 'task_helper') {
			$debug = (isset($this->passedArgs[0])) ? $this->passedArgs[0] : 0;
			Configure::write('debug', $debug);
			// transfer TaskID from CAKEPHP SESSION TO THRIFT SESSION
debug(Session::read('thrift-task'));	// CAKEPHP SESSION		
			$taskID = Session::read('thrift-task');
			$this->load_CakePhpThriftSessionFromTaskID($taskID);
			Session::write('thrift-task', $taskID);	
debug(Session::read());	// THRIFT SESSION				
		} else {
			parent::beforeFilter();
		    $this->Auth->allow('*');
		}
    }
	
	function _use_custom_thrift_session(){
		Configure::write('Session.save', 'thrift_session_handler');
	}
	function load_CakePhpThriftSessionFromTaskID($taskID){
		
		if (!$taskID) debug('load_CakePhpThriftSessionFromTaskID: taskID is empty');
		if (is_object($taskID) && get_class($taskID)=='snaphappi_api_TaskID') $taskID = (array)$taskID;
		return $this->load_CakePhpThriftSession($taskID['AuthToken'], $taskID['DeviceID']);
	}
	function load_CakePhpThriftSession($authToken, $deviceId){
		if (empty($deviceId)) {
			$trace = Debugger::trace();
			$this->log("Error: load_CakePhpThriftSession() deviceID is empty",LOG_DEBUG);
			$this->log($trace,LOG_DEBUG);
		}
		// load AuthComponent
		// loadComponent('Auth', $this);
		if (!isset($this->Auth)) {
			App::import('Component', 'Auth');
			$this->Auth = new AuthComponent();
			$this->Auth->Session = ThriftController::$controller->Session;
			$this->Auth->allow('service','task_helper','test');
		}
		$new_session_key = "{$authToken}-{$deviceId}";
		// $new_session_key = md5($new_session_key . Configure::read('Security.salt'));
		if (session_id() != $new_session_key) {
// debug(">>>>>>>>>>>>>>>>>> OLD session id=".session_id() )	;			
// $this->log(">>>>>>>>>>>>>>>>>> OLD session id=".session_id(), LOG_DEBUG);			
// debug(">>> NEW CAKEPHP session key={$new_session_key}")	;	
// $this->log(">>> NEW CAKEPHP session key={$new_session_key}", LOG_DEBUG);
			$session_name = 'ThriftSession';		
			Configure::write('Session.cookie', $session_name);
			$_COOKIE[$session_name] = $new_session_key;
			
			// close or destroy?
			if (in_array($this->action, $this->USE_AuthToken_NOT_AUTH)){
				session_destroy();
// debug(">>>>>>>  DESTROYED OLD session id=".session_id() )	;			
// $this->log(">>>>  DESTROYED OLD session id=".session_id(), LOG_DEBUG);					
			} else session_write_close();		
			
			ini_set("session.use_cookies",0);
			ini_set("session.use_only_cookies",0);
			
			session_name($session_name);
			session_id($new_session_key);
			// session_start(); session_destroy(); // resets custom Thrift Session
			session_start();
// $this->log(" @@@ using custom thrift session handler ". session_name().", ".session_id(), LOG_DEBUG);			
// debug(" @@@  using custom thrift session handler ". session_name().", ".session_id());				
// $this->Session->write('time.'.time(), time());			
		}
	}
	
	function _bootstrap_ThriftAPI($service) {
		try {
			if (isset($this->passedArgs['api'])) {
				$version = $this->passedArgs['api'];
			} else $version = ThriftController::$current_version;
			
			$GLOBALS['THRIFT_SERVICE']['VERSION'] = $version; 
			App::import('Vendor', "thrift/{$version}/{$service}");
		} catch(Exception $e) {
			$this->log("ERROR: Thrift service=app/vendors/thrift/{$version}/{$service}");
		}		
	}
	
   
	/**
	 * load thrift service, with prefix routing
	 */
	function service($service, $debug = 0) {
		Configure::write('debug', $debug);
		$this->_bootstrap_ThriftAPI($service);
		exit(0);		// return response over thrift transport
	}
	
	
	/*
	 * hepler methods for direct access to Thrift Task API from cakephp
	 * requires a valid TaskID saved to Session::read('thrift-task');
	 */ 
	function task_helper($debug=0) {
		$forceXHR = setXHRDebug($this, $debug);
		// json requests only
		$success = true; 
		$message = $response = array(); 

		// bootstrap ThriftTask and call method
		try {
			if (!$this->RequestHandler->isAjax() && !$forceXHR) 
				throw new Exception("ERROR: This action is only available by XHR");
			if ($this->RequestHandler->ext !== 'json' && !$forceXHR) 
				throw new Exception("Error: this API method is only availble for JSON requests");
				
			$task_data = Session::read('thrift-task');
// TODO: how do I get the TaskID here, can't use session, right?  ?????????????????????			
// POST data[authToken], data[deviceID]
//$this->load_CakePhpThriftSession(), // security problem??
			$method = $this->passedArgs['fn'];	
			if (empty($task_data['DeviceID'])) 
				throw new Exception("Error: invalid or missing DeviceID, check Session::read(thrift-task) ");
			if (empty($method)) 
				throw new Exception("Error: no method provided");
			
			$this->_bootstrap_ThriftAPI("Task");	// hardcoded to service=Task
			$Task = new snaphappi_api_TaskImpl();
			$TaskID = new snaphappi_api_TaskID( $task_data );					
			switch ($method) {
				case 'GetState':
					$state = $Task->GetState($TaskID);
					$response[$method] = (array)$state;
					break;
				case 'GetFolders':
					$folders = $Task->GetFolders($TaskID);
					$response[$method] = (array)$folders;
					break;
				case 'PauseUpload':
					try {
						$pause = $this->data['pause'];
						$ret = $Task->SetTaskState($TaskID, $pause);
						$response[$method] = $ret;
						if (!$ret) throw new Exception("Error: there was a problem sending pause message to the Uploader, pause={$this->data['pause']}");
					} catch (Exception $ex) {
						$success = false;
						$message = $ex->getMessage();
					}					
					break;
				case 'RemoveFolder':
					$hash = $this->data['hash'];
					try {
						$ret = $Task->RemoveFolder($TaskID, $hash);
						$response[$method] = compact('hash');
						if (!$ret) throw new Exception("Error: there was a problem removing this folder");
					} catch (Exception $ex) {
						$success = false;
						$message = $ex->getMessage();
					}
					break;					
				case 'SetWatchedFolder':
					$hash = $this->data['hash'];
					$watched = !empty($this->data['watch']);
					try {
						$ret = $Task->SetWatchedFolder($TaskID, $hash, $watched);
						$response[$method] = compact('hash','watched');
						if (!$ret) throw new Exception("Error: there was a problem setting the Watched status for this folder");
					} catch (Exception $ex) {
						$success = false;
						$message = $ex->getMessage();
					}
					break;	
			}
		} catch (Exception $ex) {
			$message = $ex->getMessage();
			$success = false;
		}
		$this->viewVars['jsonData'] = compact('success', 'message','response');
		$done = $this->renderXHRByRequest('json', null, null , 0);
	}
	/*
	 * to test UploadFile, for testing:
	 * 		http://snappi-dev/thrift/task_helper/fn:PauseUpload/.json?forcexhr=1
	 *  	http://snappi-dev/thrift/test/api:1-0/Task/1 
	 * 		http://snappi-dev/thrift/test/api:1-0/Task/1?device=1&reset=1
	 */
	function test($service, $debug = 0) {
		Configure::write('debug', $debug);
		$this->_bootstrap_ThriftAPI($service);
		/******************************************************
		 * 
		 * Skip Thrift API and call Task Class directly
		 * 	- for debugging with cakephp debug()
		 * 
		 ******************************************************/ 
		$Task = new snaphappi_api_TaskImpl();			
		
		debug("==============  Thrift Boostrap Complete =====================");
		// see HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\Snaphappi for DeviceID
		// use for testing fixed authToken/Session
		$DEVICE[1] = array(		// manager
			'auth_token'=>'b34f54557023cce43ab7213e0eb7da2a6b9d6b27',
			'device_id'=>1,
			'device_UUID'=>'2738ebe4-95a1-4d4a-aefe-761d97881535', 
			'session_id'=>'50a3fb31-7514-4db3-b730-1644f67883f5',
		);
		$DEVICE[2] = array(
			'auth_token'=>'b34f54557023cce43ab7213e0eb7da2a6b9d6b27',
			'device_id'=>2,
			'device_UUID'=>'2738ebe4-XXXX-4d4a-aefe-761d97881535', 
			'session_id'=>'509d820e-b990-4822-bb9c-11d0f67883f5'
		);
		$DEVICE[3] = array(
			'auth_token'=>'08e89e9bba58544fe3a0dcab8ac102d158ecd42f',
			'device_id'=>3,		// alexey
			'provider_account_id'=>'50a680d5-d460-4971-90fc-7f180afc6d44',
			'device_UUID'=>'b6673a7f-c151-4eff-91f3-9c45a61d6f36', 
			'session_id'=>'50a4fd3b-034c-48c7-9f87-1644f67883f5'
		);
		$DEVICE[4] = array(		// userid=5156004b-b9d8-475b-ab4b-1e880afc6d44,
			'auth_token'=>'240a222d959b3b368b8d916767fb4115928a92ec',
			'device_id'=>7,		// adoria_test1,  
			'provider_account_id'=>'5156062e-6e60-4aaf-8512-21550afc6d44',
			'device_UUID'=>'5156004b-b9d8-475b-0000-1e880afc6d44', 
			'session_id'=>'5156062e-ae3c-4962-a2d6-21550afc6d44'
		);
		/*
		 * for testing only
		 * // choose Device 1 or 2, or 0 to get a new session
		 */ 
		if (!empty($this->params['url']['device'])) {
			$attach_fixed_session = $this->params['url']['device'];
		} else 	
			$attach_fixed_session = 1;		// testing for this device
		$task_data = array(
			'AuthToken'=>$DEVICE[$attach_fixed_session]['auth_token'],
			'Session'=>$DEVICE[$attach_fixed_session]['session_id'],
			'DeviceID'=>$DEVICE[$attach_fixed_session]['device_UUID'],	// hack: get from GetDeviceID()
		);
		
		$taskId = new snaphappi_api_TaskID(
			array(
			    'AuthToken' => $task_data['AuthToken'],
			    'Session' => $task_data['Session'],
			    'DeviceID' => $task_data['DeviceID'],
			)
		);
		$this->load_CakePhpThriftSessionFromTaskID($taskId);
		
		
		if ($attach_fixed_session) {
			$session = $this->ThriftSession->newSession($DEVICE[$attach_fixed_session]);
debug(Session::read());			
		}		
		/*
		 * Test GetDeviceId
		 */
	// call async/on timer, wait until $deviceId is available
	try {
		$deviceId = $Task->GetDeviceID($task_data['AuthToken'], $task_data['Session']);
		debug ("<br>GetDeviceID()={$task_data['DeviceID']}");
	} catch (snaphappi_api_SystemException $e) {
		debug ("   Thrift Exception, msg=".$e->Information);
	} catch (Exception $e) {
		debug ("   Exception, msg=".$e->getMessage());
	}
// $this->render('/elements/sql_dump');
// return;	
		
	if (isset($_GET['reset'])) {
		$nativePath = "C:\\TEMP\\added from Thrift AddFolder";
		print "<BR />*************************************";
		print "<BR />     reset folders for testing";
		print "<BR />*************************************";
		$ret = $Task->RemoveFolder($taskId, $nativePath);
		/*
		 * Test AddFolder
		 */
		try {
			// adding folder, use flash.File->native_path	
			$nativePath = "C:\\TEMP\\added from Thrift AddFolder";
			// ALTER TABLE `snappi`.`thrift_folders` MODIFY COLUMN `native_path_hash` BIGINT(20) UNSIGNED NOT NULL COMMENT 'use a simple CRC32()';
			debug("nativePath hashed=".sprintf('%u', crc32($nativePath)));
			$ret = $Task->AddFolder($taskId, $nativePath);
			debug ("<br>AddFolder() OK, native_path={$nativePath}");
		} catch (snaphappi_api_SystemException $e) {
			debug ("   Thrift Exception, msg=".$e->Information);
		} catch (Exception $e) {
			debug ("   Exception, msg=".$e->getMessage());
		}
	}
		
		/*
		 * Test GetState
		 */
		$state = $Task->GetState($taskId);
		debug("GetState() result=".print_r($state,true));
// debug(Session::read());		
// $this->render('/elements/sql_dump');
// return;			
		/*
		 * Test GetFolders
		 */
		$folders = $Task->GetFolders($taskId);
		debug("GetFolders() result=".print_r($folders,true));
$this->render('/elements/sql_dump');
return;			
		if (count($folders)) {
			/*
			 * Test GetFiles
			 */
			$files = $Task->GetFiles($taskId, $folders[0]);
			debug("GetFiles() result=".print_r($files,true));
// $this->render('/elements/sql_dump');
// return;				
		} else {
			debug("WARNING: NO FOLDERS FOUND, ARE YOU SIGNED IN AS 'manager' TO TEST?");
			CakePhpHelper::_setTaskState($taskId, array('IsCancelled'=>0));
		}	
		
			$files = $Task->GetFilesToUpload($taskId);
			debug("GetFiles() result=".print_r($files,true));
// $this->render('/elements/sql_dump');
// return;	
		
		/*
		 * Test ReportFileCount
		 */
// debug($folders);		
		$changed = $Task->ReportFileCount($taskId, $folders[0],765);
		debug("ReportFileCount() result=".print_r($changed,true));	
		
// $this->render('/elements/sql_dump');
// return;			
		/*
		 * Test GetWatchedFolders
		 */
		$folders = $Task->GetWatchedFolders($taskId);
		debug("GetWatchedFolders() result=".print_r($folders,true));
		if (count($folders)) {
			/*
			 * Test GetFiles
			 */
			$files = $Task->GetFiles($taskId, $folders[0]);
			debug("GetFiles() folders[0]={$folders[0]}, result=".print_r($files,true));
		} else {
			CakePhpHelper::_setTaskState($taskId, array('IsCancelled'=>0));
		}			
	
		
		/*
		 * Test ReportFolderUploadComplete
		 */
		$folder = $Task->ReportFolderUploadComplete($taskId, $folders[0]);
		debug("ReportFolderUploadComplete() result=".print_r($folder,true));		
// $this->render('/elements/sql_dump');
// return;	
		/*
		 * Test UploadFiles, UO ORIGINAL
		 */
		 
		Stagehand::$stage_basepath = Configure::read('path.stageroot.basepath');
		$path = 'C\TEMP\big-test\events\NYC\P1010445.JPG';
		$data['ProviderAccount'] = Array
	        (
	            'id' => '50996b75-425c-4261-a0ee-14c8f67883f5',
	            'user_id' => '5013ddf3-069c-41f0-b71e-20160afc480d',
	            'provider_name' => 'native-uploader',
	            'provider_key' => $deviceId,
	            'display_name' => 'manager',
	            'baseurl' => null,
	            'auth_token' => $taskId->AuthToken,
	            'created' => '2012-11-02 16:32:51',
	            'modified' => '2012-11-02 19:15:15',
	        );
		$data['Asset'] = Array
		(
			'id' => '50eda502-b3f8-4f85-9e8e-1684f67883f5',
			'owner_id' => '5013ddf3-069c-41f0-b71e-20160afc480d',
		    'provider_account_id' => '509e525d-d740-4aa2-8dca-63900afc6d44',
		    'provider_name' => 'native-uploader',
		    'batchId' => '1358265743',
		    'uploadId' => '1358268241',
		    'asset_hash' => 'fffea6bece055e8834c78afaef1ba88b',
		    'json_exif' => '{"Make":"Panasonic","Model":"DMC-TS3","Orientation":1,"ExposureTime":"10\/600","FNumber":"33\/10","ISOSpeedRatings":400,"ExifVersion":"0230","DateTimeOriginal":"2012:07:21 12:56:22","Flash":25,"ColorSpace":1,"ExifImageWidth":4000,"ExifImageLength":2672,"GPSVersion":"\u0002\u0003\u0000\u0000","GPSLatitudeRef":"N","GPSLatitude":["38\/1","54\/1","4028\/100"],"GPSLongitudeRef":"W","GPSLongitude":["77\/1","2\/1","4117\/100"],"GPSTimeStamp":["21\/1","48\/1","2\/1"],"GPSAreaInformation":"UNICODE\u0000T\u0000H\u0000O\u0000M\u0000A\u0000S\u0000 \u0000T\u0000 \u0000G\u0000A\u0000F\u0000F\u0000 \u0000H\u0000O\u0000U\u0000S\u0000E\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000","GPSDateStamp":"2012:07:15","InterOperabilityIndex":"R98","InterOperabilityVersion":"0100","ApertureFNumber":"f\/3.3","isFlash":1,"root":{"imageWidth":4000,"imageHeight":2672,"isRGB":true}}',
		    'dateTaken' => '2012:07:21 12:56:22',
		    'isFlash' => 1,
		    'isRGB' => 1,
		    'caption' => 'P1010445',
		    'isThriftAPI' => 1,
		    'provider_key' => '50eda502-b3f8-4f85-9e8e-1684f67883f5',
		    'src_thumbnail' => 'stage6/tn~50eda502-b3f8-4f85-9e8e-1684f67883f5.jpg',
		    'json_src' => '{"root":"stage6\/50eda502-b3f8-4f85-9e8e-1684f67883f5.jpg","thumb":"stage6\/tn~50eda502-b3f8-4f85-9e8e-1684f67883f5.jpg","orig":"C:\\\\TEMP\\\\big-test\\\\events\\\\NYC\\\\P1010445.JPG"}',
		    'replace-preview-by-native-path' => str_replace('\\', '\\\\', $path),
		);
		
		/*
		 * setup upload
		 */ 
		$src = json_decode($data['Asset']['json_src'], true);
		$source = Stagehand::$stage_basepath.DS.$src['root'];
		$dest = Stagehand::$stage_basepath . "/../upload/{$data['Asset']['owner_id']}/" . $path;
		debug("COPYING: $source  => $dest");
		copy($source, $dest);
		$Asset = ClassRegistry::init('Asset'); 
		$Asset->id = '50eda502-b3f8-4f85-9e8e-1684f67883f5';
		$Asset->Behaviors->detach('Permissionable');
		$Asset->saveField('isOriginal', 'q'); // queued
		/*
		 * END setup upload
		 */ 
		 
		// TODO: this script is not detecting duplicates correctly, could be escape problem on native_path 
		 
		debug($data);	
		debug("****** AuthToken set for ProviderAccount.user_id=[manager] **************");
		$UploadInfo = new snaphappi_api_UploadInfo(array(
			'UploadType'=>UploadType::Original, 
			'imageID'=>$data['Asset']['id'],
		));
		$Task->UploadFile($taskId, $path, true, $UploadInfo);
		
$this->render('/elements/sql_dump');
return;		


		/*
		 * Test UploadFiles, UR
		 */
		
		$path = 'C:\TEMP\May\2015.JPG';
		$data['ProviderAccount'] = Array
	        (
	            'id' => '50996b75-425c-4261-a0ee-14c8f67883f5',
	            'user_id' => '5013ddf3-069c-41f0-b71e-20160afc480d',
	            'provider_name' => 'native-uploader',
	            'provider_key' => $deviceId,
	            'display_name' => 'manager',
	            'baseurl' => null,
	            'auth_token' => $authToken,
	            'created' => '2012-11-02 16:32:51',
	            'modified' => '2012-11-02 19:15:15',
	        );
		$data['Asset'] = Array
		(
		    'owner_id' => '5013ddf3-069c-41f0-b71e-20160afc480d',
		    'provider_account_id' => '5093f5b3-477c-42a8-8db7-14c8f67883f5',
		    'provider_name' => 'native-uploader',
		    'batchId' => '1351883673',
		    'uploadId' => '1351883842',
		    'asset_hash' => '1c917307a3aacd8e45e14b009d87ffd2',
		    'json_exif' => '{"Make":"Panasonic","Model":"DMC-TS3","Orientation":1,"ExposureTime":"10\/1250","FNumber":"33\/10","ISOSpeedRatings":1000,"ExifVersion":"0230","DateTimeOriginal":"2012:03:25 14:18:19","Flash":25,"ColorSpace":1,"ExifImageWidth":4000,"ExifImageLength":3000,"InterOperabilityVersion":"0100","ApertureFNumber":"f\/3.3","isFlash":1,"root":{"imageWidth":640,"imageHeight":480,"isRGB":true}}',
		    'dateTaken' => '2012:03:25 14:18:19',
		    'isFlash' => '1',
		    'isRGB' => null,
		    'caption' => '2015',
		    'id' => '50941c42-2f70-42f9-a837-14c8f67883f5',
		    'provider_key' => '50941c42-2f70-42f9-a837-14c8f67883f5',
		    'src_thumbnail' => 'stage5/tn~50941c42-2f70-42f9-a837-14c8f67883f5.jpg',
		    'json_src' => '{"root":"stage5\/50941c42-2f70-42f9-a837-14c8f67883f5.jpg","thumb":"stage5\/tn~50941c42-2f70-42f9-a837-14c8f67883f5.jpg","orig":"C:\\TEMP\\May\\2015.JPG"}',
		);
		debug($data);	
		debug("****** AuthToken set for ProviderAccount.user_id=[manager] **************");
		$extras = new snaphappi_api_UploadInfo(array('UploadType'=>UploadType::Preview));
		$Task->UploadFile($taskId, $path, false, $extras);
		
		
	}
}
?>
