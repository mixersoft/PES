<?php
App::import('Sanitize');
class PagemakerController extends AppController {

	var $name = 'Pagemaker';
	var $uses = array();
//	var $layout = 'ajax';
//	var $autoRender = false;
	var $components = array('RequestHandler');
	var $helpers  = array('Js');
	var $titleName = 'Pagemaker';

	function beforeFilter() {
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$this->Auth->allow('parseXml', 'catalog',
			/*
			 * move to ACL for role=users
			 */
			'arrangement', 'save_page'
		);
	}
	
	function parseXml($path) {
		// import XML class
		App::import('Xml');
		// now parse it
		$parsed_xml = & new XML($path);
		$parsed_xml = Set::reverse($parsed_xml); // this is what i call magic

		// see the returned array
		//        debug($parsed_xml);
		return $parsed_xml;
	}

	function catalog() {
		$this->layout = 'xml';
		setExpiresHeader(3600 * 24 * 30); // 30 days
		/*
		 * load arrangement xml documents from filesystem
		 */
		$catalogPath = Configure::read('path.pagemaker.catalog');
		$folder = new Folder($catalogPath);
		//		$arrangements = $folder->find('.*\.xml',true);
		$xmlpaths = $folder->findRecursive('.*\.xml');
		$catalog = array('format'=>'Picasa', 'xmlpaths'=>$xmlpaths);

		/*
		 * load component for arrangement format conversion
		 */
		App::import('component', "arrangement/converter/{$catalog['format']}Converter");
		$Converter = & new PicasaConverterComponent();
		$Converter->startup($controller);

		$converted = array();
		foreach ($catalog['xmlpaths'] as $path) {
			$xmlAsArray = $this->parseXml($path);
			$normalXmlAsArray = $Converter->convert($xmlAsArray);
			if (@ empty($normalXmlAsArray['Title']))
			$normalXmlAsArray['Title'] = basename($path);
			$converted[] = array('label'=>basename($path), 'xmlAsArray'=>$normalXmlAsArray);
		}

		// local xml contants
		$local['schemaLocation'] = 'snaphappi.com W:\www\matchmaker-svn\matchmaker.xsd';

		//		debug($converted);
		$this->set('catalog', array('format'=>$catalog['format'], 'arrangements'=>$converted));
		$this->set('local', $local);
//debug($this->viewVars['catalog']);
	}
	
	/*
	 * extract key properties from photos
	 */
	function __getPhotos($photos, $baseurl){
		$output = array();
		foreach ($photos as $photo) {
			$p = array();
			$p['id'] = $photo['id'];
			if (isset($photo['Photo'])) {	// from CastingCallComponent
				$p['caption'] = $photo['Photo']['Caption'];
				$p['unixtime'] = $photo['Photo']['TS'];
				$p['dateTaken'] = $photo['Photo']['DateTaken'];
				$p['rating'] = $photo['Photo']['Fix']['Rating'];
				$p['width'] = $photo['Photo']['Img']['Src']['W'];
				$p['height'] = $photo['Photo']['Img']['Src']['H'];
				$p['src'] = $baseurl . $photo['Photo']['Img']['Src']['rootSrc'];
			} else {	// flat object, from Catalog.getCustomFitArrangement()
				$p['caption'] = $photo['Caption'];
				$p['unixtime'] = $photo['TS'];
				$p['dateTaken'] = $photo['DateTaken'];
				$p['rating'] = $photo['Rating'];
				$p['width'] = $photo['W'];
				$p['height'] = $photo['H'];
				$p['src'] = $photo['src'];				
			}
			$output[] = $p;
		}
	//	sort($output, );
		return $output;
	}
	
	/*
	 * sort by Rating DESC, then unixtime ASC
	 */
	function __sortPhotos($photos, $count = null){
		// Obtain a list of columns
		foreach ($photos as $key => $row) {
		    $rating[$key]  = $row['rating'];
		}
		array_multisort($rating, SORT_DESC, $photos);
		if ($count) $photos = array_slice($photos, 0, $count);
		return $photos;
	}
	/*
	 * sort arrangement roles by 'prominence'
	 */
 	function __sortRoles($arrangement){
 		$roles = $arrangement['Roles'];
		// Obtain a list of columns
		foreach ($roles as $key => $row) {
		    $area[$key]  = $row['H']*$row['W'];
		    $top[$key] = $row['Y'];
		    $left[$key] = $row['Y'];
		}
		array_multisort($area, SORT_DESC, $top, SORT_ASC, $left, SORT_ASC, $roles);
		$arrangement['Roles'] = $roles;
		return $arrangement;
	}
	
	function __normalize(& $arrangement) {
		$w = $arrangement['W'];
		$h = $arrangement['H'];
		$scale = 72;
		$arrangement['Scale'] = $scale;
		foreach ($arrangement['Roles'] as & $role) {
			$role['H'] /= $h;
			$role['Y'] /= $h;
			$role['W'] /= $w;
			$role['X'] /= $w;
		}
		// in "inches" for dpi calculation
		$arrangement['W'] /= $scale;
		$arrangement['H'] /= $scale;
	}
	/*
	 * TODO: move to Story model, also see /gallery/save_page
     * __getSecretKey(): get a secret key to be used for unauthenticated access a PageGallery share
     * Notes:
     * 	- key is used to lookup pageGallery filename: see /svc/pages/[secretKey].div
     * 	- key is only issued to logged in user, used to save PageGallery
     * 	- key is not required for owner access, read/write
     */
    function __getSecretKey($seed = '') {
    	$salt = Configure::read('Security.salt');
    	$userid = AppController::$userid  ? AppController::$userid : 'Guest';
    	$key = sha1($userid.$seed.$salt);
//    	if (!$key) $key = sha1(time().'-'.rand().$salt); 		// guest user in designer mode. deprecate
        return $key;
    }

    /*
	 * * TODO: move to Story model, also see /gallery/save_page
     * use seed to reset secretKey
     * - seed should be a user-provided value stored in DB, along with $userid, $filename
     */
    function __getSeed($filename) {
    	// TODO: get from DB
    	return null;	
    }
    function save_page() {
    	$forceXHR = setXHRDebug($this, 0);
        $this->layout = null;
        $ret = 0;

        if ($this->data) {
            /*
             * POST - save/append/delete PageGallery file
             */        	
            // allow guest users to save
            $userid = AppController::$userid  ? AppController::$userid : 'Guest';
        	if ($userid) {	
	            $content = $this->data['content'];		// page content
	            $dest = $this->data['dest'];	// dest	file, book
        		// TODO: use seed to reset secretKey. get seed from DB
        		$secretKeyDest = $this->__getSecretKey($this->__getSeed($dest)); 
	            
	            /*
	             * read content from page
	             */
        		if (empty($content)) {
        			$src = $this->data['src'];		// source file, stored in /svc/pages
        			$secretKeySrc = $this->__getSecretKey($this->__getSeed($src));
		            $File = Configure::read('path.wwwroot').Configure::read('path.pageGalleryPrefix').DS.$src.'_'.$secretKeySrc.'.div';
		            $content = @file_get_contents($File);
        		}
	            /*
	             * append or write content to book
	             */
	            $File = Configure::read('path.wwwroot').Configure::read('path.pageGalleryPrefix').DS.$dest.'_'.$secretKeyDest.'.div';
	            
	            // append page to book
	            $mode = isset($this->data['reset']) ? 'w' : 'a';
	            $Handle = fopen($File, $mode);
	            fwrite($Handle, $content);
	            fclose($Handle);
	            // don't unlink
	            $ret = 1;
	        }
		}
		$this->autoRender = false;
		Configure::write('debug',0);
		header('Content-type: application/json');
		header('Pragma: no-cache');
		header('Cache-control: no-cache');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 		
		$success = $ret ? true : false;
		if ($success) {
			header("HTTP/1.1 201 CREATED");
			$message = "Your Story was saved.";
			$response = array(
				'key'=>$secretKeyDest, 
				'href'=>"/gallery/story/{$this->data['dest']}_{$secretKeyDest}",
			);
		} else {
			$message = "There was an error saving your Story. Please try again.";
			$response = array();
		}
		echo json_encode(compact('success', 'message', 'response'));
		return;
    }	


	function arrangement($count = 16) {
		$forceXHR = setXHRDebug($this, 0, true);
		if ($forceXHR) {
			// if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
			// $cc = $this->CastingCall->cache_MostRecent();
			// $this->data['CastingCall'] = json_encode($cc);  // [Auditions]
			// debug($this->data);
			$this->autoLayout = false;
		}
		if (empty($this->data)) {
			$success = false;
			$message = "/pagemaker/arrangement: postdata is blank";
			$response = "{}";
			return compact('success', 'message', 'response');
		}
		
		// prepare photos from castingCall
		$count = $count <= 16 ? $count : 16;
		$appHost = env('SERVER_NAME');
		$rawJson = $this->data['CastingCall']['Auditions'];
		$Auditions = json_decode($rawJson, true);
		$photos = $Auditions['Audition'];
		$baseurl = "http://{$appHost}".$Auditions['Baseurl'];
debug($photos);
		$sortedPhotos = $this->__sortPhotos($this->__getPhotos($photos, $baseurl), null);
		$layoutPhotos = count($sortedPhotos) > $count ? array_slice($sortedPhotos, 0, $count) : $sortedPhotos;
		
		/*
		 * get arrangement from photos
		 * params
		 * 	- cropVarianceMax, maxHeight, maxWidth
		 * 	- count
		 * 	- seed
		 * 	- index
		 */
		App::import('Vendor', 'pagemaker', array('file'=>'pagemaker'.DS.'cluster-collage.4.php'));
		$cropVarianceMax = 0.20; 
		$maxHeight = 900;
		$maxWidth = 1600;
		$collage = new ClusterCollage($cropVarianceMax, $maxHeight, $maxWidth);
		try {
			$collage->setPhotos($layoutPhotos, 'topRatedCutoff');
			$arrangement = $collage->getArrangement();
			$this->__normalize($arrangement);
			// if ($forceXHR) debug($arrangement);
							
			// format jsonData response
			$success = true;
			$message = '';
			$response['arrangement'] = $arrangement;
		} catch (Exception $e) {
			$success = false;
			$message = $e->getMessage();
			$response = null;
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
	}
}
?>
