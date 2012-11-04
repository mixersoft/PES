<?php 
class ThriftController extends AppController {
    public $name = 'Thrift';
	public $uses = array('User', 'ProviderAccount');
	
	public static $controller = null;
	 
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
		debug("==============  Thrift Boostrap Complete =====================");
		$authToken = 'b34f54557023cce43ab7213e0eb7da2a6b9d6b27';
		$deviceId = '5ec5006d-8dee-48ef-8c04-bac06c16d36e';
		$data['ProviderAccount'] = Array
	        (
	            'id' => '5093f5b3-477c-42a8-8db7-14c8f67883f5',
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
		$taskId = new snaphappi_api_TaskID(
			array(
			    'AuthToken' => $authToken,
			    'Session' => 'session-50941b84-d1d0-4f0e-a951-14c8f67883f5',
			    'DeviceID' => $deviceId,
			)
		);
		$path = 'C:\TEMP\May\2015.JPG';
		
		$Task = new snaphappi_api_TaskImpl();
		debug("****** AuthToken set for ProviderAccount.user_id=[manager] **************");
		$Task->debug_UploadFile($taskId, $path);
		
		
	}
}
?>
