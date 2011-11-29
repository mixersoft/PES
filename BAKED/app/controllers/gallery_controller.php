<?php 
class GalleryController extends AppController {

    var $name = 'Gallery';
    var $uses = array('Snappi');
    var $components = array('RequestHandler');
    var $helpers = array('Html', 'Form', 'Time', 'Text', 'Xml');
    
    public $titleName = 'Page Gallery';
    
    function beforeFilter() {
        parent::beforeFilter();
        /*
         *	These actions are allowed for all users
         */
        $this->Auth->allow(array('story', 'page_gallery', 'save_page', 'upload_share'));
    }
    
    function home() {
    }
    
    /*
     * __getSecretKey(): get a secret key to be used for unauthenticated access a PageGallery share
     * Notes:
     * 	- key is used to lookup pageGallery filename: see /svc/pages/[secretKey].div
     * 	- key is only issued to logged in user, used to save PageGallery
     * 	- key is not required for owner access, read/write
     */
    function __getSecretKey($seed = '') {
    	$salt = Configure::read('Security.salt');
    	$userid = $this->Session->read('Auth.User.id');
    	$key = sha1($userid.$seed.$salt);
//    	if (!$key) $key = sha1(time().'-'.rand().$salt); 		// guest user in designer mode. deprecate
        return $key;
    }

    /*
     * use seed to reset secretKey
     * - seed should be a user-provided value stored in DB, along with $userid, $filename
     */
    function __getSeed($filename) {
    	// TODO: get from DB
    	return null;	
    }
    function page_gallery($filename = '') {
    	$next = str_replace('page_gallery','story', $this->here);
    	$this->redirect($next, null, true);
    }
    function story($filename = '') {
        $filename = $filename ? $filename : 'default'; // testing
        $seed = $this->__getSeed($filename); 	// TODO: use seed to reset secretKey. get seed from DB 
        if ($this->data) {
            /*
             * POST - save/append/delete PageGallery file
             */        	
        	if (empty(AppController::$userid) || empty($this->data['content'])) {
        		$check = false;
        		$ret = 0;
        	} else {
	            $content = $this->data['content'];
	            /*
	             * get secretKey for current user
	             */
		        $secretKey = $this->__getSecretKey($seed);            
	            /*
	             * save PageGallery content to file
	             */
	            $File = Configure::read('path.wwwroot').Configure::read('path.pageGalleryPrefix').DS.$filename.'_'.$secretKey.'.div';
	            $mode = isset($this->data['reset']) ? 'w' : 'a';
	            $Handle = fopen($File, $mode);
	            fwrite($Handle, $content);
	            fclose($Handle);
	            $page_gallery = array($content);
	            $ret = $filename;
        	}
            
            /*
             * do NOT render page gallery on post.
             */
            $this->autoRender = false;
            header("HTTP/1.1 201 CREATED");
        	// TODO: return standard JSON message format
            echo $ret;
            return;
        } else {
        	/*
        	 * GET - read PageGallery content
        	 */
            $page_gallery = array();
            $isPreview = isset($this->params['url']['preview']) ? $this->params['url']['preview'] !== '0' : false;
            
            /*
             * caching
             */
            // IMPORTANT: prevent client side caching when in designer mode
//            $referer = @if_e(env('HTTP_REFERER'), null);
//            if ($isPreview || strpos($referer, env('SERVER_NAME'))) {
//                                Controller::disableCache();
//            } else
//                setExpiresHeader(3600 * 24); // 1 hour
			/*
			 *	end Caching 
			 */
                
            /*
             * read content from file
             */
            $File = Configure::read('path.wwwroot').Configure::read('path.pageGalleryPrefix').DS.$filename.'.div';
            $request = $this->params['url']['url'];
            if (file_exists($File)) {	
            	// secretKey already embedded in $filename 
                $link = "http://{$_SERVER['HTTP_HOST']}/{$request}";	// share link to display in PageGallery
            } else {
                // logged in user, add secretKey to find PageGallery
                $secretKey = $this->__getSecretKey($seed); 
                $File = Configure::read('path.wwwroot').Configure::read('path.pageGalleryPrefix').DS.$filename.'_'.$secretKey.'.div';
                $link = "http://{$_SERVER['HTTP_HOST']}/{$request}_{$secretKey}";
            }
            if (file_exists($File)) {	
	            if (isset($this->params['url']['reset'])) {
	                unlink($File);
	            } else {
	                $str = @file_get_contents($File);
	                if ($str) {
	                    // set Last-Modified to file date
	                    setLastModifiedHeader(filemtime($File));
	                    $page_gallery[] = $str;
	                }
	            }
            }
        }
        if ( empty($page_gallery) ) {
            $page_gallery = array('<div class="error-page-gallery">Sorry, it seems there was an error somewhere. The Page Gallery you requested is not available.</div>');
            $link = '';
        }
        $this->set('page_gallery', $page_gallery);
        $this->set(compact('link', 'isPreview'));
        
        $done = $this->renderXHRByRequest(null, '/gallery/page_gallery', null, 0);
        if ($done) return;
        
        // render as http request, uses iFrame
        $this->layout = "pageGallery";
        /*
         * do not cache for iframe
         */
        echo header('Pragma: no-cache');
	  	echo header('Cache-control: no-cache');
	  	echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");         
    }
    
    
    /*
     * SAVE/APPEND PageGallery PAGE to PageGallery BOOK. 
     */
    function save_page() {
        $this->layout = null;
        $ret = 0;
        if ($this->data) {
            /*
             * POST - save/append/delete PageGallery file
             */        	
        	if (AppController::$userid) {
	            $content = $this->data['content'];		// page content
	            $dest = $this->data['dest'];	// dest	file, book
        		// TODO: use seed to reset secretKey. get seed from DB
        		$secretKeyDest = $this->__getSecretKey($this->__getSeed($dest)); 
	            
	            /*
	             * read content from page
	             */
        		if (empty($content)) {
        			$src = $this->data['src'];		// source file, page
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
			$this->autoRender = false;
			header("HTTP/1.1 201 CREATED");
			// TODO: return standard JSON message format
			echo $ret;
			return;
	    }
    }

	function upload_share() {
		$forceXHR = setXHRDebug($this, 0);
		$data = $_POST ? $_POST : $this->data;
		$this->autoRender = false;		
		if (!empty($data)){
			$this->log("POST to /gallery/upload_share", LOG_DEBUG);
			$this->log($data, LOG_DEBUG);
			$success = true;
			$message = "POST data logged on server.";
			$response = $data;
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
			$done = $this->renderXHRByRequest('json', null, null , 0);			
			if ($done) return; 
		}
	}
}
?>
