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
	
	public $layout = 'snappi';
	
	
	public static $userid = null;
	
	function __construct() {
		parent::__construct();
	}
	function beforeFilter() {
			
		if (Session::check('Auth.User')) {
			$this->passedArgs[0] = Session::read('Auth.User.id');	// emulate request with UUID in the right position
		}
		parent::beforeFilter();
		MyController::$userid = AppController::$ownerid;
		$myAllowedActions = array( 
			/*
			 * main
			 */
			// add to ACLs
			'upload', 'desktop_upload', 'express_uploads', 'lightbox', 'settings',
			/*
			 * experimental
			 */
			'pagemaker', 'updateExif', 
		);
		$removeAuth = array('photos', 'groups', 'trends', 'stories', 'photostreams', 'home', 
			'all', 'most_active', 'most_recent','most_photos','most_groups','remove_photos'
			);
		$this->Auth->allowedActions = array_merge(array_diff($this->Auth->allowedActions , $removeAuth), $myAllowedActions);
		// else auth redirect
	}
	
	
	function home() {
		parent::home(MyController::$userid );
	}
	function photos() {
		if (!MyController::$userid) {
			
		}
		parent::photos(MyController::$userid );
	}
	function snaps() {
		parent::photos(MyController::$userid );
	}
	function stories() {
		parent::stories(MyController::$userid );
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
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		$id = MyController::$userid;
		if (!empty($this->data)) {
			/*
			 * redirect to edit with setting=[form id]
			 */
			$qs = @http_build_query(array('setting'=>$this->data['User']['setting']));
			$redirect = Router::url(array('action'=>'edit')). ($qs ? "?{$qs}" : '');
			$this->redirect($redirect, null, true);
		}
		$privacy = $this->__getPrivacyConfig();
		$moderator =  $this->__getModeratorConfig();
		$this->set(compact('privacy', 'moderator'));
		
		$this->User->contain('Profile');
		$options = array('conditions'=>array('User.id'=>$id));
		$data = $this->User->find('first', $options);
		
		$this->data = $data;
		$this->set('data', $data);
		
		$xhrFrom = Configure::read('controller.xhrFrom');
		if ($xhrFrom) {
			$viewElement = '/elements/users/'.$xhrFrom['view'];
		} else $viewElement = null;
		$this->viewVars['jsonData']=$this->viewVars;
		$done = $this->renderXHRByRequest('json', $viewElement, 0);		
		return;
	}
	/**
	 * @param $data, Asset.batchId, ProviderAccount.provider_name
	 * @param $baseurl, the base folder for upload files
	 * @param $move_to_src_root, full path to uploaded file, includes $baseurl
	 * @param $isOriginal boolean
	 */
	function __importPhoto($data, $baseurl, $move_to_src_root, $isOriginal){
		$isOriginal = ($isOriginal == 'ORIGINAL');
		$ret = true;
		$Import = loadComponent('Import', $this);
		if (!isset($this->ProviderAccount)) $this->ProviderAccount = ClassRegistry::init('ProviderAccount');
		if (!isset($this->Asset)) $this->Asset = ClassRegistry::init('Asset');
		
		/*
		 * create ProviderAccount, if missing
		 */
		$conditions = array('provider_name'=>$data['ProviderAccount']['provider_name'], 'user_id'=>AppController::$userid);
		if (!empty($data['ProviderAccount']['id']) ) $conditions['id'] = $data['ProviderAccount']['id'];
		$paData = $this->ProviderAccount->addIfNew($data['ProviderAccount'], $conditions,  $response);
		$paData['baseurl'] = isset($data['ProviderAccount']['baseurl']) ? $data['ProviderAccount']['baseurl'] : $paData['baseurl']; 
		/****************************************************
		 * setup data['Asset'] to create new Asset
		 */
		$profile = $this->getProfile();
		if (isset($profile['Profile']['privacy_assets'])) {
			$data['Asset']['perms'] = $profile['Profile']['privacy_assets'];
		}	
		
// $this->log("MyController::__importPhoto, paData=".print_r($paData, true), LOG_DEBUG);			
		$assetData = $this->Asset->addIfNew($data['Asset'], $paData['ProviderAccount'], $baseurl, $move_to_src_root, $isOriginal, $response);		
// $this->log("MyController::__importPhoto, this->Asset->addIfNew(), asset=".print_r($assetData, true), LOG_DEBUG);		
		/*
		 *  move file to staging server,
		 * 	NOTE: for DUPLICATE files, 
		 * 		asset fields are updated in DB, see Asset::__updateAssetFields()
		 * 		files are COPIED only if larger
		 */   
		$src = json_decode($assetData['Asset']['json_src'], true);
		$stage=array('src'=>$move_to_src_root, 'dest'=>$src['root']);
// $this->log("MyController::__importPhoto staging files, ".print_r($stage, true), LOG_DEBUG);		
	 	if ($ret3 = $Import->import2stage($stage['src'], $stage['dest'], null, $move = true)) {
	 		$response['message'][]="file staged successfully, dest={$stage['dest']}";
		} else $response['message'][]="Error staging file, src={$stage['src']} dest={$stage['dest']}";
	 	$ret = $ret && $ret3;

	 	$response['success'] = $ret ? 'true' : 'false';
		$response['response'] = $assetData;
// $this->log("MyController::__importPhoto JSON response, ".print_r($response, true), LOG_DEBUG);			
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
		$move_to_src_root = $dest;
		
		/*
		 * autorotate NO
		 * 	- DO NOT AUTOROTATE Originals from JS loader
		 * 	- NOTE: AIR will autorotate before uploading bp~
		 */
		// $Jhead = loadComponent('Jhead', $this);
		// $Jhead->autoRotate($move_to_src_root);	// 
		
		// setup meta data
		$BATCH_ID = $_GET['batchId'];
		$PROVIDER_NAME = 'snappi';
		$Import = loadComponent('Import', $this);
		$data = array();
		$data['Asset']['id'] = null;
		$data['Asset']['asset_hash'] = null;
		$data['Asset']['batchId'] = $BATCH_ID;
		$data['Asset']['rel_path'] = basename($move_to_src_root);
		$data['ProviderAccount']['provider_name']=$PROVIDER_NAME;
// $this->log($data['Asset'], LOG_DEBUG);		
		/***************************************************************
		 * experimental: replace mode, replace existing with original
		 * 	pass to Asset::addIfNew()
		 ***************************************************************/ 
		 if (!empty($this->params['url']['replace'])){
		 	$data['Asset']['replace-preview-with-original'] = true;
		 };
		
		/*
		 * import into DB
		 */
// $this->log("MyController::__upload_javascript() BEGIN VALUMS/JAVASCRIPT IMPORT", LOG_DEBUG);
// $this->log($data, LOG_DEBUG);
		$response = $this->__importPhoto($data, $UPLOAD_FOLDER, $move_to_src_root, 'ORIGINAL');	// autoRotate=false
		if ($response['success'] && isset($response['response']['Asset']['id'])) {
			/*
			 * share via express uploads, as necessary
			 */ 
			$groupIds = (array)explode(',',$_GET['groupIds']);
			$resp1 = array();
			foreach($groupIds as $gid){
				if (empty($gid)) continue;
				$asset_id = $response['response']['Asset']['id'];
				$paid = $response['response']['Asset']['provider_account_id'];
				$count = $this->User->Membership->contributePhoto($gid, $asset_id, $paid);
				if ($count) {
					$resp1['message'][] = "Express Upload: shared with Group id={$gid}";
					$resp1['response']['Group'][]['id'] = $gid;
				}
			}
			$response = Set::merge($response, $resp1);
		}
//$this->log($response, LOG_DEBUG);
		$this->User->setRandomBadgePhoto($userid);
		// to pass data through iframe you will need to encode all html tags
		// Configure::write('debug', 0);
		echo htmlspecialchars(json_encode($response), ENT_NOQUOTES);	
		// echo json_encode($response);
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
$this->log("__upload_AIRclient(): fileDataERROR for userid={$userid}", LOG_DEBUG);			
			header("HTTP/1.1 500 Internal Server Error");
			$response['success'] = 'false';
			$response['message'] = 'Error: invalid Filedata';
			$response['response'] = '';
			header('Content-Type: application/json');
		    echo json_encode($response);
		    exit(0);
		} else {
$this->log("upload FILES[Filedata] = {$_FILES['Filedata']['tmp_name']}", LOG_DEBUG);			
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
			$move_to_src_root = cleanpath($UPLOAD_FOLDER.$userid.DS.$name);
$this->log("__upload_AIRclient(): upload success, owner_id={$userid}, file dest={$move_to_src_root}", LOG_DEBUG);				
			if (!file_exists(dirname($move_to_src_root))) mkdir(dirname($move_to_src_root), 2775, true);
			if( !move_uploaded_file($file_url, $move_to_src_root) ){
				@unlink($file_url);
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
			$move_to_src_root = cleanpath($UPLOAD_FOLDER.$userid.DS."xhrforce-file");
		}
		
			
		/*
		 * process POST data
		 */
$this->log("=====   Post data  ==========", LOG_DEBUG);
			$this->log($this->data, LOG_DEBUG);		
		/*
		 * import into DB
		 */
$this->log("before __importPhoto  >>>>>>>>>>>>>>>", LOG_DEBUG);	
// $this->log($this->data['Asset'], LOG_DEBUG);
		// for AIR: autoRotate==true
		$this->data['Asset']['isAIR'] = 1;
		$response = $this->__importPhoto($this->data, $UPLOAD_FOLDER, $move_to_src_root, "PREVIEW");
$this->log($response['message'], LOG_DEBUG);		
		/*
		 * share via express uploads, as necessary
		 */ 
		 if (isset($this->data['groupIds'])) {
		 	$groupIds = (array)explode(',',$this->data['groupIds']);
			$resp1 = array();
			foreach($groupIds as $gid){
				$asset_id = $response['response']['Asset']['id'];
// $this->log("asset_id={$asset_id}", LOG_DEBUG);				
				if (!$asset_id) break; 		// error, skip contributePhoto 
				$paid = $response['response']['Asset']['provider_account_id'];
				$count = $this->User->Membership->contributePhoto($gid, $asset_id, $paid);
				if ($count) {
					$resp1['message'][] = "Express Upload: shared with Group id={$gid}";
					$resp1['response']['Group'][]['id'] = $gid;
				}
			}
			$response = Set::merge($response, $resp1);
		 }
		 $this->User->setRandomBadgePhoto($userid);
//		$this->log($response, LOG_DEBUG);
		/*
		 * return response
		 */
		//			$this->log($response, LOG_DEBUG);			
		header('Content-Type: application/json');
	    echo json_encode($response);
	    exit(0);
	}

	function __getExpressUploads($userid) {
				// paginate 
		$paginateModel = 'ExpressUploadGroup';
		$copyFromAlias = 'Membership';
		
		// bind habtm Group using $paginateModel as alias
		$this->User->bindModel(array(
			'hasAndBelongsToMany'=>array(
				$paginateModel=>$this->User->hasAndBelongsToMany[$copyFromAlias])
			)
		);
		
		$Model = $this->User->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateGroupsByUserId($userid, $this->paginate[$paginateModel]);
			$joins[] = array(
				'table'=>'groups_users',
				'alias'=>'HABTM',
				'type'=>'INNER',
				'conditions'=>array("`HABTM`.group_id =`{$paginateModel}`.id", 
					"`HABTM`.user_id" => $userid,
					"`HABTM`.isActive" => 1,
					"`HABTM`.isExpress" => 1,),
			);	
		$paginateArray['joins'] = @mergeAsArray($paginateArray['joins'], $joins);
		$paginateArray['order'] = array("`HABTM`.created DESC");
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$expressUploadGroups = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
// debug($expressUploadGroups);		
		// this is the model we really want to paginate
		Configure::write('paginate.Model', $copyFromAlias);
		return $expressUploadGroups;
	}
	/**
	 * get express upload groups by AJAX for desktop uploader
	 * for sharing uploaded Snap directly with Express Upload Group
	 */
	function express_uploads(){
		$forceXHR = setXHRDebug($this, 0);
		if (!$this->RequestHandler->isAjax() && !$forceXHR) return;
		if (AppController::$role !== 'USER') return;
		
		Configure::write('debug', $forceXHR);
		$this->autoRender = false;
		$userid = AppController::$ownerid;
		$expressUploadGroups =$this->__getExpressUploads($userid);  	// sets  viewVars['expressUploadGroups']		
		$this->set(compact('expressUploadGroups'));
		$done = $this->renderXHRByRequest('json', '/elements/group/express-upload', null, $forceXHR);
		if ($done) return;
	}
	
	/**
	 * upload from AIR desktop uploader
	 */ 
	 function desktop_upload(){
// $this->log($_POST, LOG_DEBUG);
// $this->log("userid==".AppController::$userid, LOG_DEBUG);
// $this->log("[HTTP_COOKIE]=".$_SERVER['HTTP_COOKIE'], LOG_DEBUG);
// $this->log(">>>>>>>>>>>>>>>>>>>>>> AppController::role==".AppController::$role, LOG_DEBUG);		
// $this->log($this->data, LOG_DEBUG);		
// debug($_SERVER);
		// exit(0);
		Configure::write('debug', 0);		// preserve valid JSON response
		$forceXHR = setXHRDebug($this, 0);
$this->log("forceXHR=={$forceXHR}, debug=".Configure::read('debug'), LOG_DEBUG);		
		$force_UNSECURE_LOGIN = true;
		if (AppController::$role != 'USER') {
			/*
			 *  POST from snappi AIR desktop uploader
			 * 	WARNING: json/xhr login DOES NOT transfer Session cookie
			 */
// 			$this->log(Session::read('Auth.User'), LOG_DEBUG);
			if ($force_UNSECURE_LOGIN && !$this->Auth->user() && isset($this->data['User']['id'])) {
// $this->log("force_UNSECURE_LOGIN={$force_UNSECURE_LOGIN}, role=".AppController::$role, LOG_DEBUG);				
				// TODO: authorize user by uuid. this is unsafe!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
				$userid = $this->data['User']['id'];
				$data = $this->User->read(null, $userid );
			
// $this->log($data['User']['username'], LOG_DEBUG);				
				$ret = $this->Auth->login($data);
// $this->log($ret, LOG_DEBUG);
$this->log("force_UNSECURE_LOGIN for username={$data['User']['username']}", LOG_DEBUG);
				
				$this->__cacheAuth();
				$this->Permissionable->initialize($this);
//				$this->log(Session::read('Auth.User'), LOG_DEBUG);
			} else {
	//			$this->log($response, LOG_DEBUG);			
				$response['success']=false;
				$response['message']='Session not Authenticated for COOKIE='.$_SERVER['HTTP_COOKIE'];
	$this->log($response, LOG_DEBUG);			
	$this->log("try setting force_UNSECURE_LOGIN in MyController", LOG_DEBUG);	
				header('Content-Type: application/json');
			    echo json_encode($response);
			    exit(0);			
				return;
			}
		}
		
		$this->autoRender = false;
		$this->__upload_AIRclient();	
		exit(0);
	}
	
	/*
	 * PHP or javascript/valums upload
	 */
	function upload () {
		$this->layout = 'snappi-guest';
		$this->viewPath = 'my';
//		$this->log($this->data, LOG_DEBUG);
		$forceXHR = setXHRDebug($this);
		$userid = AppController::$userid;
		
		if (!empty($this->params['url']['qqfile'])) {
			Configure::write('debug', $forceXHR);
			/*
			 * handle javascript POST
			 */
			$this->autoRender = false;
			$this->__upload_javascript($userid);
			exit(0);
		} else if ($this->data){
			$this->log("=====   Post data  ==========", LOG_DEBUG);
			$this->log($this->data, LOG_DEBUG);
			echo "1";
			return;
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
		
		
		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$userid));
		$data = $this->User->find('first', $options);
		
		// set for '/elements/group/express-upload'
		$expressUploadGroups = $this->__getExpressUploads($userid);
		$this->set(compact('expressUploadGroups'));
				
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

	/*
	 * native SnappiUploader (UploadHelperApp) upload
	 */
	function uploader () {
		$this->layout = 'snappi-thrift';
		$this->viewPath = 'my';
		$userid = AppController::$userid;
		
		
		/*
		 * get valid ProviderAccount.auth_token
		 */	
		$provider_name = 'native-uploader';
		$options = array(
			'conditions'=>array(
				'ProviderAccount.provider_name'=>$provider_name,
			)
		);
		// NOTE: for provider-name=native-uploader, this should be UNIQUE.
		$data = $this->User->ProviderAccount->getByOwner($userid, $options);

		if (!empty($data['ProviderAccount']['auth_token'])) {
			// found, check authToken for expiration 
			$last_modified = strtotime($data['ProviderAccount']['modified']);
			$age = time() - $last_modified;
			$EXPIRES_IN_SEC = 3 * 30 * 24 * 3600;  // 3 months, TODO: get from Profile
			if ($age > $EXPIRES_IN_SEC) {
				$this->Session->setFlash('Warning: Your secret key for uploading Snaps has expired. Do you want to renew it?');
				// TODO: add option for renewing auth token.
			}
		} else if (!empty($data['ProviderAccount']['id'])) {
			// create new auth_token
			$ret = $this->User->ProviderAccount->thrift_renewAuthToken($data['ProviderAccount']['id']);
			$data = $this->User->ProviderAccount->getByOwner($userid, $options);
		} else {
			/*
			 * if NOT found, create ProviderAccount with authToken, 
			 * IMPORTANT: this scheme requires ALL pa rows for user/native-uplaoder 
			 * to have the SAME authToken
			 */
			$paData=array();
			$paData['user_id'] = AppController::$userid;
			$paData['provider_name'] = $provider_name;
			// $paData['provider_key'] = null;		-- we don't know the DeviceID yet
			// $paData['auth_token'] = sha1(String::uuid().Configure::read('Security.salt'));
			// $paData['baseurl'] =  
			$conditions = array(
				'user_id'=>AppController::$userid,
				'provider_name'=>$provider_name, 
			);
			$paData = $this->User->ProviderAccount->addIfNew($paData, $conditions,  $response);
			$this->User->ProviderAccount->thrift_renewAuthToken($paData['ProviderAccount']['id']);
			$data = $this->User->ProviderAccount->getByOwner($userid, $options);
		}
		// NOTE: we only want ProviderAccount.auth_token

		/*
		 * get valid session_id
		 */		
		// setup Task state, 
		// TODO: How do we delete keys for expired sessions? DELETE WHERE is_cancelled=1 or modified too old
		$this->ThriftSession = ClassRegistry::init('ThriftSession');
		
		// see HKEY_LOCAL_MACHINE\SOFTWARE\Wow6432Node\Snaphappi for DeviceID
		// use for testing fixed authToken/Session
		$DEVICE[1] = array(
			'device_id'=>1,
			'device_UUID'=>'2738ebe4-95a1-4d4a-aefe-761d97881535', 
			'session_id'=>'50a3fb31-7514-4db3-b730-1644f67883f5'
		);
		$DEVICE[2] = array(
			'device_id'=>2,
			'device_UUID'=>'2738ebe4-XXXX-4d4a-aefe-761d97881535', 
			'session_id'=>'509d820e-b990-4822-bb9c-11d0f67883f5'
		);
		$DEVICE[3] = array(
			'device_id'=>3,		// alexey
			'device_UUID'=>'b6673a7f-c151-4eff-91f3-9c45a61d6f36', 
			'session_id'=>'50a4fd3b-034c-48c7-9f87-1644f67883f5'
		);
		
		/*
		 * for testing only
		 * // choose Device 1 or 2, or 0 to get a new session
		 */ 
		if (!empty($this->params['url']['reset'])) {
			$attach_fixed_session = $this->params['url']['reset'];
		} else if (!empty($this->params['url']['device'])) {
			$attach_fixed_session = $this->params['url']['device'];
		} else 	
			$attach_fixed_session = 1;		// testing for this device
		if ($attach_fixed_session) {	
			$session = $this->ThriftSession->newSession($DEVICE[$attach_fixed_session]);
		}
		
		$taskID = array(
			'AuthToken'=>$data['ProviderAccount']['auth_token'],
			'Session'=>$session['ThriftSession']['id'],
		// DeviceID is known from Task->GetDeviceID(), or user sets manually, saves to session			
			'DeviceID'=>$DEVICE[$attach_fixed_session]['device_UUID'],	// hack: get from GetDeviceID()
		);
		Session::write('thrift-task', $taskID);
		
		// on first POST of TaskID, bind taskID->DeviceID with Session	
		$session = $this->ThriftSession->checkDevice($taskID['Session'], $taskID['DeviceID']);
		if (!$session) $session = $this->ThriftSession->bindDeviceToSession($taskID['Session'], $taskID['AuthToken'], $taskID['DeviceID']);
		
		/*
		 * show express-uploads, if any
		 *  see '/elements/group/express-upload'
		 */ 
		$expressUploadGroups = $this->__getExpressUploads($userid);
		$this->set(compact('taskID', 'expressUploadGroups'));
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			// $this->redirectSafe();
		} else {
			
			if (isset($this->params['url']['reset'])) { 	// reset folders for device
				// set hardcoded GetFolders() data for TESTING, normally we'd use the TopLevelFolder app
				$folder_state = array();
				$folder_state[] = array('folder_path'=>'C:\\TEMP\\May', 'is_scanned'=>0, 'is_watched'=>0, 'count'=>0); 
				$folder_state[] = array('folder_path'=>'C:\\TEMP\\small import test', 'is_scanned'=>0, 'is_watched'=>1, 'count'=>0);
				$folder_state[] = array('folder_path'=>'C:\\TEMP\\folder with special char (;\'&.=^%$#@!) test', 'is_scanned'=>0, 'is_watched'=>0, 'count'=>0);
				$folder_state[] = array('folder_path'=>'C:\\TEMP\\big-test', 'is_scanned'=>0, 'is_watched'=>0, 'count'=>0);
				$folder_state[] = array('folder_path'=>'C:\\TEMP\\big-test\\events\\NYC', 'is_scanned'=>1, 'is_watched'=>0, 'count'=>0);
				$folder_state[] = array('folder_path'=>'C:\\TEMP\\big-test\\events\\world thinking day', 'is_scanned'=>1, 'is_watched'=>1, 'count'=>0);
			debug(Set::extract('/folder_path', $folder_state));	
				// load folders to ThriftFolders for testing
				$resetSQL = "delete f from thrift_folders f join thrift_devices d on d.id = f.thrift_device_id 
						where d.device_UUID='{$taskID['DeviceID']}'";
				$this->ThriftSession->query($resetSQL);
				foreach($folder_state as $folder) {
					$ret = $this->ThriftSession->ThriftDevice->ThriftFolder->addFolder(
						$session['ThriftDevice']['id'], $folder['folder_path'], $folder);
				}
			}			
			
			// on GetFolders()
			$folders = $this->ThriftSession->ThriftDevice->ThriftFolder->findByDeviceUUID($taskID['DeviceID'], $is_watched = null); 
			$taskState['ThriftSession'] = $session['ThriftSession'];
			
			$this->set(compact('data', 'taskID', 'folders', 'taskState'));
		}
	}

	/*
	 * native SnappiUploader (UploadHelperApp) upload
	 */
	function uploader_ui () {
		$forceXHR = setXHRDebug($this, 0, 1);
		$this->autoRender = false;
		$success = true;
		try {
			if (!$this->RequestHandler->isAjax() && !$forceXHR) 
				throw new Exception("ERROR: This action is only available by XHR");
			
			/*
			 * Get correct ProviderAccount
			 * 	NOTE: at this point, we do know know the DeviceID/provider_key
			 */
			// $data = $this->User->ProviderAccount->getByOwner($userid, $options);
			
			$userid = AppController::$userid;
			$taskID = Session::read('thrift-task');  // set in /my/uploader parent action
			$this->ThriftSession = ClassRegistry::init('ThriftSession');
			if (!$taskID)
				throw new Exception("ERROR: TaskID not found in session");
	
			// on first POST of TaskID, bind taskID->DeviceID with Session	
			$session = $this->ThriftSession->checkDevice($taskID['Session'], $taskID['DeviceID']);
			if (!$session) 
				throw new Exception("ERROR: DeviceID should alreay be bound to Session");
			// on GetFolders()
			$folders = $this->ThriftSession->ThriftDevice->ThriftFolder->findByDeviceUUID($taskID['DeviceID'], $is_watched = null); 
			$taskState['ThriftSession'] = $session['ThriftSession'];
			$response = compact('taskID', 'folders', 'taskState');
		} catch (Exception $ex) {
			$message = $ex->getMessage();
			$success = false;
		}
		
		if ($this->RequestHandler->ext=='json') {
			$this->viewVars['jsonData'] = compact('success', 'message','response');
			$done = $this->renderXHRByRequest('json', null, null , 0);
			if ($done) return;
		}
		// TODO: deprecated, only JSON reponse allowed
		// for HTML response
		$this->layout = 'ajax';
		$this->viewPath = 'my';
		$this->set($response);
		$this->render('/elements/thrift/folder'); 		
	}
	
	
	function truncate($id=null){
		Configure::write('debug', 2);
		$this->autoRender=false;	
		if ($id != MyController::$userid) {
			debug('<span style="font-size:14pt;color:red;">WARNING: about to delete all Snaps for user=<b>'.$this->Auth->user('username').'</b>.</span> <br />Please append User.id');
			debug($this->Auth->user());
			return;
		};
		
		set_time_limit(600);
		$options = array('conditions'=>array('Asset.owner_id'=>$id), 
			'fields'=>array('Asset.id', 'Asset.json_src', 'Asset.owner_id'),
			'permissionable'=>false
		);
		$data = $this->User->Asset->find('all', $options);
		$jsonSrc = Set::extract($data, '/Asset/json_src');
		$basepath = Configure::read('path.stageroot.basepath');
		foreach ($jsonSrc as $json) {
			$src = json_decode($json, true);
			$root = $basepath.DS.cleanpath($src['root']);
			@unlink($root);
			if (isset($src['preview'])) {	// deprecated
				$preview = $basepath.DS.cleanpath($src['preview']);
				@unlink($preview);
			}
			$thumb_src = $basepath.'/'.preg_replace('/\//', '/.thumbs/', $src['root'], 1);
			$sizes = array('bp', 'tn', 'sq', 'lm', 'll', 'bm', 'bs');
			foreach ($sizes as $size) {
				$path = Stagehand::getImageSrcBySize($thumb_src, $size);
				@unlink($path);
			}
		}	
		debug("JPG files removed");
		
		$sql_deleteCascade = 
  "DELETE FROM `Asset`, `Shot`, `Best`, `AssetsShot`, `AssetsGroup`, `AssetsCollection`
	USING `assets` AS `Asset`
  LEFT JOIN `assets_usershots` AS `AssetsShot` ON (`AssetsShot`.asset_id = `Asset`.id)
  LEFT JOIN `usershots` AS `Shot` ON (`Shot`.id = `AssetsShot`.usershot_id )
	LEFT JOIN `best_usershots` AS `Best` ON (`Best`.usershot_id = `Shot`.id)
  LEFT JOIN `assets_groups` AS `AssetsGroup` ON (`AssetsGroup`.asset_id = `Asset`.id)
  LEFT JOIN `assets_collections` AS `AssetsCollection` ON (`AssetsCollection`.asset_id = `Asset`.id)
	WHERE `Asset`.owner_id ='{$id}'";
	
		$this->User->query($sql_deleteCascade);
		$this->User->id = $id;
		$this->User->saveField('asset_count',0);
		
		debug("<br /><a href='".Router::url(array('action'=>'photos'))."'>/my/photos</a><br />");
		
		$this->render('/elements/sql_dump');
	}
		
	function remove_photos($id){
		$id = MyController::$userid;
		$options = array('conditions'=>array('Asset.owner_id'=>$id), 
			'fields'=>array('Asset.id', 'Asset.json_src', 'Asset.owner_id'),
			'permissionable'=>false
		);
		$data = $this->User->Asset->find('all', $options);
		$jsonSrc = Set::extract($data, '/Asset/json_src');
		$basepath = Configure::read('path.stageroot.basepath');
		foreach ($jsonSrc as $json) {
			$src = json_decode($json, true);
			$root = $basepath.DS.cleanpath($src['root']);
			$preview = $basepath.DS.cleanpath($src['preview']);
			@unlink($root);
			@unlink($preview);
			$thumb_src = $basepath.'/'.preg_replace('/\//', '/.thumbs/', $src['root'], 1);
			$sizes = array('bp', 'tn', 'sq', 'lm', 'll', 'bm', 'bs');
			foreach ($sizes as $size) {
				$path = Stagehand::getImageSrcBySize($thumb_src, $size);
				@unlink($path);
			}
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
	
	function updateExif() {
		return parent::__updateExif(MyController::$userid);
	}					
}
?>
