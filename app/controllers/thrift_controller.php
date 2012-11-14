<?php 
class ThriftController extends AppController {
    public $name = 'Thrift';
	public $uses = array('User', 'ProviderAccount', 'ThriftSession');
	
	public static $controller = null;
	public static $session = null;
	 
    public $helpers = array(
		// 'Time',
		// 'Text',
		// 'Layout',
	);

	public $autoRender = false;			// return response over thrift transport
    public $layout = false;

	public $components = array(
		// add components here
	);	
    
	function __construct() {
		parent::__construct();
		ThriftController::$controller = $this;
	}
	
    function beforeFilter() {
    	parent::beforeFilter();
        $this->Auth->allow('*');
    }
   
	/**
	 * load thrift service, with prefix routing
	 */
	function service($service, $debug = 0) {
		try {
			Configure::write('debug', $debug);
			if (isset($this->passedArgs['api'])) {
				$version = $this->passedArgs['api'];
				$GLOBALS['THRIFT_SERVICE']['VERSION'] = $version; 
				App::import('Vendor', "thrift/{$version}/{$service}");
			} else {
				App::import('Vendor', "thrift/{$service}");
			}
		} catch(Exception $e) {
			$this->log("ERROR: Thrift service=app/vendors/thrift/{$version}/{$service}");
		}
		exit(0);		// return response over thrift transport
	}
	/*
	 * to test UploadFile, use http://snappi-dev/thrift/test/api:1-0/Task/1 on 2015.JPG
	 */
	function test($service, $debug = 0) {
		try {
			Configure::write('debug', $debug);
			if (isset($this->passedArgs['api'])) {
				$version = $this->passedArgs['api'];
				$GLOBALS['THRIFT_SERVICE']['VERSION'] = $version; 
				App::import('Vendor', "thrift/{$version}/{$service}");
			} else {
				App::import('Vendor', "thrift/{$service}");
			}
		} catch(Exception $e) {
			$this->log("ERROR: Thrift service=app/vendors/thrift/{$version}/{$service}");
		}
		
		/******************************************************
		 * 
		 * Skip Thrift API and call Task Class directly
		 * 	- for debugging with cakephp debug()
		 * 
		 ******************************************************/ 
		$Task = new snaphappi_api_TaskImpl();			
		
		debug("==============  Thrift Boostrap Complete =====================");
		$authToken = 'b34f54557023cce43ab7213e0eb7da2a6b9d6b27';
		$sessionId = '50a32a66-6680-4c29-a5b2-1644f67883f5';
		// see HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\Snaphappi
		$deviceId = "2738ebe4-95a1-4d4a-aefe-761d97881535"; //'5ec5006d-8dee-48ef-8c04-bac06c16d36e';
		// $deviceId = "2738ebe4-xxxx-4d4a-aefe-761d97881535";
		
		
		$taskId = new snaphappi_api_TaskID(
			array(
			    'AuthToken' => $authToken,
			    'Session' => $sessionId,
			    'DeviceID' => $deviceId,
			)
		);

		/*
		 * Test GetDeviceId
		 */
	// call async/on timer, wait until $deviceId is available
	try {
		$deviceId = $Task->GetDeviceID($authToken, $sessionId);
		debug ("<br>GetDeviceID()={$deviceId}");
	} catch (snaphappi_api_SystemException $e) {
		debug ("   Thrift Exception, msg=".$e->Information);
	} catch (Exception $e) {
		debug ("   Exception, msg=".$e->getMessage());
	}
		
$nativePath = "C:\\TEMP\\added from Thrift AddFolder";
	if (isset($_GET['reset'])) {
		print "<BR />*************************************";
		print "<BR />     reset folders for testing";
		print "<BR />*************************************";
		$ret = $Task->RemoveFolder($taskId, $nativePath);
	}
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
	
		
		/*
		 * Test GetState
		 */
		$state = $Task->GetState($taskId);
		debug("GetState() result=".print_r($state,true));
		/*
		 * Test GetFolders
		 */
		$folders = $Task->GetFolders($taskId);
		debug("GetFolders() result=".print_r($folders,true));
		if (count($folders)) {
			/*
			 * Test GetFiles
			 */
			$files = $Task->GetFiles($taskId, $folders[0]);
			debug("GetFiles() result=".print_r($files,true));
		} else {
			CakePhpHelper::_model_setTaskState($taskId, array('IsCancelled'=>0));
		}	
$this->render('/elements/sql_dump');
return;		
		/*
		 * Test ReportFileCount
		 */
debug($folders);		
		$changed = $Task->ReportFileCount($taskId, $folders[0], 21);
		debug("ReportFileCount() result=".print_r($changed,true));	
			
		/*
		 * Test ReportFolderUploadComplete
		 */
		$folder = $Task->ReportFolderUploadComplete($taskId, $folders[0]);
		debug("ReportFolderUploadComplete() result=".print_r($folder,true));		
	
		/*
		 * Test UploadFiles
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
