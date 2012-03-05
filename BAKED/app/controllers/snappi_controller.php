<?php
App::import('Sanitize');
class SnappiController extends AppController {

	var $name = 'Snappi';
	var $uses = array('Snappi');
	var $layout = 'ajax';
	var $autoRender = false;
	var $components = array('RequestHandler');
	//TODO add helper and titleName here for sending JSON data
	var $helpers  = array('Js');
	var $titleName = 'Snappi';

	function beforeFilter() {
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$this->Auth->allow('*');
	}
	
	/*
	 * dev function only
	 */
	function showCC($ccid) {
		$cc = $this->Session->read("castingCall.{$ccid}");
		$this->autoRender = false;
		header('Content-Type: text/html');
		echo json_encode($cc);
	}
	
	function __getExtendedCCRequest($ccid, $limit=2000) {
		$cc = $this->Session->read("castingCall.{$ccid}");
		$request = $cc['Request'];
		$paging = $cc['Auditions'];
		$paging = array_filter_keys($paging,array('Total', 'Page', 'Perpage'));
		$paging['Page'] = 20;
		$paging['Perpage'] = 10;
		$perpage = min($limit, $paging['Total']);
		if ($paging['Total'] > $limit) {
			$lastIndex = $paging['Page'] * $paging['Perpage'];
			$page = max(1, floor( $lastIndex / ($limit) )+1);
			$request = setNamedParam($request, 'page', $page );
		} 
		$request = setNamedParam($request, 'perpage', $perpage );
		$request = makeJsonRequest($request);		
		return $request;
	}
	
	function extendcc($ccid) {
		$request = $this->__getExtendedCCRequest($ccid);
		$this->viewVars['jsonData']['request'] = $request;
		$done = $this->renderXHRByRequest('json', null, null, 0);
		if ($done) return;
		debug("not done");
	}

	/**
	 * write profile data into metadata
	 */
	function writeProfile(){
		$forceXHR = false;
		if ($forceXHR) {
			$this->data = $this->params['url']['data'];	
			debug($this->data);
		}
		if ($this->RequestHandler->isAjax() || $forceXHR) {
			$this->layout='ajax';
			$this->autoRender=false;
			$this->User = ClassRegistry::init('User'); 
			foreach($this->data as $key => $value){
				$this->User->id = AppController::$userid;
				$getMetaData = $this->User->getMeta($key);
				$mergedMetaData = Set::merge($getMetaData, $value);
				if($this->User->setMeta($key, $mergedMetaData)){
					$readSessionData = $this->Session->read($key);
					$mergedSessionData = Set::merge($readSessionData, $key);
					if($this->Session->write($key, $mergedSessionData)){
						$success = 'true';
						$message = 'Write profile and session successfully!';
						$response = '';
					}else{
						$success = 'false';
						$message = 'Write profile successfully, error writing to session.';
						$response = '';
					}
				}else{
					$success = 'false';
					$message = 'Error writing to profile.';
					$response = '';
				}
			}
		}else{
			$success = 'false';
			$message = 'Request is not Ajax!';
			$response = '';
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json');
		return;
	}
	
	/**
	 * read profile data from metadata
	 */
	function readProfile(){
		$forceXHR = false;
		if ($forceXHR) {
			debug($this->params['url']);
		}
		if ($this->RequestHandler->isAjax() || $forceXHR) {
			$this->User = ClassRegistry::init('User'); 
//			$this->autoRender=false;
			$key = $this->params['url']['key'];
			if($this->Session->check($key)){
				$getSessionData = $this->Session->read($key);
				if($getSessionData){
					$success = 'true';
					$message = 'Read session successfully!';
					//TODO need to improve
					$response = json_decode($getSessionData);
				}else{
					$success = 'false';
					$message = 'Not read session successfully!';
					$response = '';
				}
			}else{
				$this->User->id = AppController::$userid;
				$getMetaData = $this->User->getMeta($key);
				if($getMetaData){
					$success = 'true';
					$message = 'Read profile successfully!';
					//TODO need to improve
					$response = json_decode($getMetaData);
				}else{
					$success = 'false';
					$message = 'Error reading from Profile';
					$response = '';
				}
			}			
		}else{
			$success = 'false';
			$message = 'Request is not Ajax!';
			$response = '';
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json');
		return;
	}	
	
	/**
	 * write session data into session
	 */
	function writeSession(){
		$forceXHR = false;
		if ($forceXHR) {
			$this->data = $this->params['url']['data'];	
			debug($this->data);
		}
		if ($this->RequestHandler->isAjax() || $forceXHR) {
			$this->layout='ajax';
			$this->autoRender=false;
			foreach($this->data as $key => $value){
				if($this->Session->write($key, Set::merge($this->Session->read($key), $value))){
					$success = 'true';
					$message = 'Write session successfully!';
					$response = '';
				}else{
					$success = 'false';
					$message = 'Not write session successfully!';
					$response = '';
				}
			}
		}else{
			$success = 'false';
			$message = 'Request is not Ajax!';
			$response = '';
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json');
		return;
	}
	
	/**
	 * read session data from session
	 */
	function readSession(){
		$forceXHR = false;
		if ($forceXHR) {
			$this->data = $this->params['url']['key'];	
			debug($this->data);
		}
		if ($this->RequestHandler->isAjax() || $forceXHR) {
			$this->autoRender=false;
			$key = $this->params['url']['key'];
			$getSessionData = $this->Session->read($key);
			if($getSessionData){
				$success = 'true';
				$message = 'Read session successfully!';
				//TODO need to improve
				$response = json_decode($getSessionData);
			}else{
				$success = 'false';
				$message = 'Not read session successfully!';
				$response = '';
			}
		}else{
			$success = 'false';
			$message = 'Request is not Ajax!';
			$response = '';
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json');
		return;
	}
	
	function __array_grab(&$arr, $key) {
		$value = null;
		if (isset($arr[$key])) {
			$value = $arr[$key];
			unset($arr[$key]);
		}
		return $value;
	}	
	function proxy($host = 'passthru') {
		Configure::write('debug', 1);
		$this->layout = false;

		// Is it a POST or a GET?
		$isPost = isset($this->data['qs']);
		if ($isPost) {
			$qs = $this->data;
		} else {
			$qs = $this->__array_grab($this->params, 'url');
		}
		// get host from qs
		$qsHost = $this->__array_grab($qs, 'host');
		if ($qsHost) $host = $qsHost;

		switch ($host) {
			case "facebook":
				break;
			case "passthru":
				// qs passthrough, use phpcurl
				$proxyurl = $this->__array_grab($qs, 'proxyurl'); // set urlbase for passthrough manually
				// Open the Curl session
				$session = curl_init($proxyurl);
				// If it's a POST, put the POST data in the body
				if ($isPost) {
					$postvars = '';
					while ($element = current($_POST)) {

						if (key($_POST) != 'proxy_url') {
							$postvars .= key($_POST).'='.$element.'&';
						}
						next($_POST);
					}
					curl_setopt($session, CURLOPT_POST, true);
					curl_setopt($session, CURLOPT_POSTFIELDS, $postvars);
				}
				// Don't return HTTP headers. Do return the contents of the call
				curl_setopt($session, CURLOPT_HEADER, false);
				curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($session);
				curl_close($session);
				//debug($qs);
				//debug($proxyurl);
				break;
			case "flickr":
			default:
				$response = $this->__proxyFlickrAsXML($qs);
				break;
		}
//		$this->set('xml_response', $response);
		Configure::write('debug', 0);
		$this->header('Content-Type: application/xml');
		$this->autoRender = false;
//		$this->layout = 'xml';
		echo $response;
		return;
	}	
	// for testing snappi-uploader
	function debugPost() {
		$this->autoRender=false;
		$this->log($this->data, LOG_DEBUG);
//		$this->log($_POST, LOG_DEBUG);
		Configure::write('debug', 0);
		echo "success";
		return;
	}
	/**
	 * get current version for AIR application
	 * 
	 */
	function uploader_version(){
		$version = Configure::read('desktop.uploader.version');	
$this->log("version={$version}", LOG_DEBUG);		
		Configure::write('debug',0);
		$this->autoRender = false;
		$this->layout = 'plain';
		$this->set('plain', $version);
		$this->render('/elements/plain');
	}
	
	/*
	 * use importComponent to read exif in debug mode
	 */
	function exif($id){
		$this->autoRender = false;
		$this->layout = 'plain';
		
		$data = ClassRegistry::init('Asset')->findById($id);
		if (empty($data)) {
			$path = "F:\Milan 2012\LesArc 2012 179.JPG";
		} else {
			$wwwroot = Configure::read('path.wwwroot');
			$src = Stagehand::getSrc($data['Asset']['src_thumbnail'], '');
			$path = $wwwroot.$src;
		}
debug($path);
		/*
		 * IMPORTANT!!! do NOT change $data['Asset']['json_exif']['Orientation']
		 */
		$Import = loadComponent('Import', $this);
		$data['Asset']['json_exif'] = json_decode($data['Asset']['json_exif'], true);
debug($data['Asset']['json_exif']);
		/*
		 * Do Not Change
		 * see: ImportComponent::$EXIF_DO_NOT_CHANGE
		 */
debug("IMPORTANT!!! do NOT change data['Asset']['json_exif']['Orientation']");


		$meta = $Import->getMeta($path, null, $data['Asset']['json_exif']);
debug($meta);		
		$data['Asset']['json_exif'] = $meta['exif'] + $data['Asset']['json_exif'];
debug(getAssetHash($data['Asset'], $path));	

		$this->set('plain', null);
		$this->render('/elements/plain');
	}
}
?>
