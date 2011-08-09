<?php

/*
 * My controller
 * 
 * 	- special controller for current user only
 *  - overloads UsersController but passes current $userid in $this->passedArgs
 */
App::import('Controller', 'Person');
class MyController extends PersonController {
	public $name = 'Users';		// paginate/context processing unchanged if we use Groups here, vs. Events
	public $modelClass = 'User';
	public $modelKey = 'users';
	public $viewPath = 'person';	
	
	public $titleName = 'Me';
	public $displayName = 'Me';	// section header
	
	public $layout = 'snappi-aui-960';
	
	
	public static $userid = null;
	
	function __construct() {
		parent::__construct();
	}
	function beforeFilter() {
		if (Session::check('Auth.User')) {
			MyController::$userid = Session::read('Auth.User.id');
			$this->passedArgs[0] = MyController::$userid;	// emulate request with UUID in the right position
		}
		parent::beforeFilter();
		$myAllowedActions = array( 
			/*
			 * main
			 */
			// add to ACLs
			'upload', 'lightbox', 
			/*
			 * experimental
			 */
			'pagemaker'
		);
		$this->Auth->allow( array_merge($this->Auth->allowedActions , $myAllowedActions));
	
		// else auth redirect
	}
	
	function home() {
		parent::home(MyController::$userid );
	}
	function photos() {
		parent::photos(MyController::$userid );
	}
	function photostreams() {
		parent::photostreams(MyController::$userid );
	}
	function groups() {
		parent::groups(MyController::$userid );
	}		
	function trends() {
		parent::trends(MyController::$userid );
	}	
	function search() {
		parent::search(MyController::$userid );
	}					
	function edit() {
		parent::edit(MyController::$userid );
	}	
	function settings() {
		parent::settings(MyController::$userid );
	}

	function __importPhoto($data, $baseurl, $photoPath){
		$ret = true;
		$Import = loadComponent('Import', $this);
		if (!isset($this->ProviderAccount)) $this->ProviderAccount = ClassRegistry::init('ProviderAccount');
		if (!isset($this->Asset)) $this->Asset = ClassRegistry::init('Asset');
		
		/*
		 * create ProviderAccount, if missing
		 */
		$conditions = array('id'=>$data['ProviderAccount']['id'],'provider_name'=>$data['ProviderAccount']['provider_name'], 'user_id'=>AppController::$userid);
		$paData = $this->ProviderAccount->addIfNew($data['ProviderAccount'], $conditions,  $response);

		/****************************************************
		 * setup data['Asset'] to create new Asset
		 */
		$profile = $this->getProfile();
		if (isset($profile['Profile']['privacy_assets'])) {
			$data['Asset']['perms'] = $profile['Profile']['privacy_assets'];
		}	
		$assetData = $this->Asset->addIfNew($data['Asset'], $paData['ProviderAccount'], $baseurl, $photoPath, $response);		
//$this->log("MyController::__importPhoto, asset=".print_r($assetData, true), LOG_DEBUG);		
		// move file to staging server 
		$src = json_decode($assetData['Asset']['json_src'], true);
		$stage=array('src'=>$photoPath, 'dest'=>$src['root']);
//$this->log("MyController::__importPhoto, ".print_r($stage, true), LOG_DEBUG);		
	 	if ($ret3 = $Import->import2stage($stage['src'], $stage['dest'], null, $move = true)) {
	 		$response['message'][]="file staged successfully, dest={$stage['dest']}";
		} else $response['message'][]="Error staging file, src={$stage['src']}";
	 	$ret = $ret && $ret3;

	 	$response['success'] = $ret ? 'true' : 'false';
		$response['response'] = json_encode($assetData);
		return $response;
	}
	/**
	 * upload and import files in DB using the valums javascript uploader
	 * @param $userid
	 * @return unknown_type
	 */
	function __upload_javascript($userid){
		// this request is a valums fileUploader POST, so route request to the vendor server file
		$fileUploader_path = Configure::read('path.fileUploader');
	    // set upload folder
	    $UPLOAD_FOLDER = $fileUploader_path['folder_basepath'].$userid.DS;
		if ($this->Session->check('fileUploader.uploadFolder') == null) {
			Session::write('fileUploader.uploadFolder', $UPLOAD_FOLDER);
		}
		// by importing the vendor file, 
		// we will run the php script file and process the request
		App::import('Vendor', 'fileUploader', array('file'=>$fileUploader_path['vendorpath'].DS.'server'.DS.'php.php'));
		
		// list of valid extensions, ex. array("jpeg", "xml", "bmp")
		$allowedExtensions = array("jpeg", "jpg");
		// max file size in bytes
		$sizeLimit = 8 * 1024 * 1024;
		
		$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
		$dest = $uploader->handleUpload($UPLOAD_FOLDER, false);
		$photoPath = $dest;
		
		// autorotate
		$Jhead = loadComponent('Jhead', $this);
		$Jhead->autoRotate($photoPath);
		
		// setup meta data
		$BATCH_ID = $_GET['batchId'];
		$PROVIDER_NAME = 'snappi';
		$Import = loadComponent('Import', $this);
		$meta = $Import->getMeta($photoPath);
		$data = array();
		$data['Asset']['id'] = null;
		$data['Asset']['asset_hash'] = null;
		$data['Asset']['json_exif'] = $meta['exif'];
		$data['Asset']['iptc_exif'] = $meta['iptc'];
		$data['Asset']['batchId'] = $BATCH_ID;
		$data['Asset']['rel_path'] = '';
		$data['ProviderAccount']['provider_name']=$PROVIDER_NAME;
		/*
		 * import into DB
		 */
//$this->log("MyController::__upload_javascript() BEGIN VALUMS/JAVASCRIPT IMPORT", LOG_DEBUG);
//		$this->log($data, LOG_DEBUG);
		$response = $this->__importPhoto($data, $UPLOAD_FOLDER, $photoPath);
//$this->log($response, LOG_DEBUG);
		
		// to pass data through iframe you will need to encode all html tags
		Configure::write('debug', 0);
		echo htmlspecialchars(json_encode($response), ENT_NOQUOTES);	
		exit(0);
	}
	
	/*
	 * use this to test POST:
	 * http://git:88/my/upload?data[User][id]=4d635c36-0190-4185-b509-1078f67883f5&data[isAIR]=1&data[ProviderAccount][id]=4AE74931-6224-43FE-8731-33FFB1108768&data[ProviderAccount][provider_name]=desktop&data[ProviderAccount][provider_version]=v1.0&data[ProviderAccount][provider_key]=4AE74931-6224-43FE-8731-33FFB1108768&data[ProviderAccount][baseurl]=C:\USERS\michael\Pictures\importTest&data[Asset][id]=2E3081BB-03B4-40F3-8266-ADA50EC326DA&data[Asset][asset_hash]=a963dd86f79d9d24a4779d3b97d4018f&data[Asset][batchId]=1300443389&data[Asset][rel_path]=DC\P1030998.JPG&data[Asset][width]=4000&data[Asset][height]=2672&data[Asset][json_exif]={%22FocalLength%22%3A30%2C%22XResolution%22%3A180%2C%22ResolutionUnit%22%3A1%2C%22Software%22%3A%22Picasa%203.0%22%2C%22InterOperabilityIndex%22%3A%22%22%2C%22DateTime%22%3A%222010%3A07%3A28%2004%3A10%3A12%22%2C%22SensingMethod%22%3A2%2C%22ComponentsConfiguration%22%3A%22\u0001\u0002\u0003%22%2C%22ColorSpace%22%3A1%2C%22ExposureProgram%22%3A4%2C%22CompressedBitsPerPixel%22%3A4%2C%22InterOperabilityVersion%22%3A%22%22%2C%22FileSource%22%3A%22%22%2C%22ExposureBiasValue%22%3A0%2C%22FlashPixVersion%22%3A0%2C%22InteroperabilityOffset%22%3A8374%2C%22MaxApertureValue%22%3A4.8203125%2C%22YResolution%22%3A180%2C%22DateTimeOriginal%22%3A%222010%3A07%3A27%2016%3A10%3A12%22%2C%22YCbCrPositioning%22%3A2%2C%22Make%22%3A%22Panasonic%22%2C%22Orientation%22%3A1%2C%22ExifImageWidth%22%3A4000%2C%22Model%22%3A%22DMC-G1%22%2C%22ExifImageLength%22%3A2672%2C%22ExposureTime%22%3A0.01%2C%22Flash%22%3A16%2C%22DateTimeDigitized%22%3A%222010%3A07%3A28%2004%3A10%3A12%22%2C%22MeteringMode%22%3A5%2C%22FNumber%22%3A8%2C%22ISOSpeedRatings%22%3A100%2C%22Compression%22%3A6%2C%22LightSource%22%3A0%2C%22ExifVersion%22%3A%220221%22}&forcexhr=1&debug=2
	 * http://git:88/my/upload?data[User][id]=4df70124-7000-4245-ab67-0290f67883f5&data[isAIR]=1&data[ProviderAccount][id]=4df70124-7000-4245-ab67-0290f67883f5&data[ProviderAccount][provider_name]=desktop&data[ProviderAccount][provider_version]=v1.0&data[ProviderAccount][provider_key]=4df70124-7000-4245-ab67-0290f67883f5&data[ProviderAccount][baseurl]=C:\Users\michael\Pictures\Peter%26Allie\Daniel&data[Asset][id]=EEFC82EE-0A53-4EE4-9BF6-463C17E7E6AA&data[Asset][asset_hash]=128c69eec29bda555bfb0155c46fa7aa&data[Asset][batchId]=1308034500&data[Asset][rel_path]=IMG_3505.JPG&data[Asset][width]=3648&data[Asset][height]=2736&data[Asset][json_exif]=[]&forcexhr=1&debug=2
	 */
	function __upload_AIRclient(){
		$forceXHR = setXHRDebug($this);
		$this->autoRender = false;
		$userid = AppController::$userid;
		// Check for complete Filedata or return error
		$fileDataERROR = !isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0; 
		if ($fileDataERROR && !$forceXHR) {
			header("HTTP/1.1 500 Internal Server Error");
			$response['success'] = 'false';
			$response['message'] = 'Error: invalid Filedata';
			$response['response'] = '';
			header('Content-Type: application/json');
		    echo json_encode($response);
		    exit(0);
		} 
		if (!$fileDataERROR) {
			/*
			 *  move/rename uploaded file
			 */
		    $name = $_FILES["Filedata"]["name"];
		    $title = '';
		    $type = $_FILES["Filedata"]["type"];
		    $file_url = $_FILES["Filedata"]["tmp_name"];
		    $size = ceil($_FILES["Filedata"]["size"]/1024) . 'Kb';
		    
		    // set upload folder
		    $UPLOAD_FOLDER = Configure::Read('path.airUploader.folder_basepath');
			if ($this->Session->check('airUploader.uploadFolder') == null) {
				Session::write('airUploader.uploadFolder', $UPLOAD_FOLDER);
			}		    
			$dest = cleanpath($UPLOAD_FOLDER.$userid.DS.$name);
			if (!file_exists(dirname($dest))) mkdir(dirname($dest), 2775, true);
			if( !move_uploaded_file($file_url, $dest) ){
				unlink($file_url);
				// return error
				$response['success'] = 'false';
				$response['message'] = 'Error moving uploaded file';
				$response['response'] = array('filename'=>$file_url);
				header('Content-Type: application/json');
			    echo json_encode($response);
			    exit(0);
			}
		} 
		
		if ($forceXHR) {
			$UPLOAD_FOLDER = Configure::Read('path.airUploader.folder_basepath');
			$dest = cleanpath($UPLOAD_FOLDER.$userid.DS."xhrforce-file");
		}
		
			
		/*
		 * process POST data
		 */
		/*
		 * import into DB
		 */
//		$this->log($this->data, LOG_DEBUG);
		$response = $this->__importPhoto($this->data, $UPLOAD_FOLDER, $dest);
//		$this->log($response, LOG_DEBUG);
		/*
		 * return response
		 */
		//			$this->log($response, LOG_DEBUG);			
		header('Content-Type: application/json');
	    echo json_encode($response);
	    exit(0);
	}
	
	/*
	 * valums uploader ONLY
	 */
	function upload () {
//		$this->log($this->data, LOG_DEBUG);
		$forceXHR = setXHRDebug($this);
		$userid = AppController::$userid;
		if (!empty($this->data['isAIR'])) {
			/*
			 *  POST from snappi AIR desktop uploader
			 */
//			$this->log(Session::read('Auth.User'), LOG_DEBUG);
			if (!$this->Auth->user() && Configure::read('debug') && isset($this->data['User']['id'])) {
				// TODO: authorize user by uuid. this is unsafe!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
				$userid = $this->data['User']['id'];
				$data = $this->User->read(null, $userid );
				$ret = $this->Auth->login($data);
				$this->__cacheAuth();
				$this->Permissionable->initialize($this);
//				$this->log(Session::read('Auth.User'), LOG_DEBUG);
			}
			
			$this->__upload_AIRclient();
			exit(0);
		} else if (!empty($this->params['url']['qqfile'])) {
			/*
			 * handle javascript POST
			 */
			$this->autoRender = false;
			$this->__upload_javascript($userid);
			exit(0);
		} else if ($this->data){
			/*
			 *  bad cakephp POST from somewhere else
			 */			
			header("HTTP/1.1 500 Internal Server Error");
			echo "Bad POST";
			exit(0);
		}
		
		
		/*
		 * GET
		 */
//		debug(session_name()."=".session_id());
		
		$this->layout = 'upload';
		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$userid));
		$data = $this->User->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$this->set('data', $data);			
		}
	}
	
		
	function remove_photos($id){
		$this->User->Asset->Behaviors->detach('Taggable');
		$options = array('conditions'=>array('Asset.owner_id'=>$id), 
			'fields'=>array('Asset.id', 'Asset.json_src', 'Asset.owner_id'),
			'permissionable'=>false
		);
		$data = $this->User->Asset->find('all', $options);
		$jsonSrc = Set::extract($data, '/Asset/json_src');
		$baseurl = Configure::read('path.stageroot.basepath');
		foreach ($jsonSrc as $json) {
			$src = json_decode($json, true);
			$root = cleanpath($baseurl.DS.$src['root']);
			$preview = str_replace('bp~', '.thumbs/bp~', getImageSrcBySize($root, 'bp'));
			$tn = getImageSrcBySize($preview, 'tn');
			$sq = getImageSrcBySize($preview, 'sq');
//			debug($sq);
			unlink($root);
			unlink($preview);
			unlink($tn);
			unlink($sq);
		}	
		debug("done");
		$this->autoRender=false;		
	}
	
	function lightbox () {
		$id = MyController::$userid;


		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$id));
		$data = $this->User->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$this->set('data', $data);			
		}
	}
	function pagemaker () {
		$id = MyController::$userid;


		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$id));
		$data = $this->User->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$this->set('data', $data);			
		}
		
	}					
}
?>
