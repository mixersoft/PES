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
	 * * TODO: move to Story model, also see /pagemaker/save_page
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
	 * * TODO: move to Story model, also see /pagemaker/save_page
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
    function story($filename = null) {
        $filename = $filename ? $filename : 'guest'; // testing
        $seed = $this->__getSeed($filename); 	// TODO: use seed to reset secretKey. get seed from DB 
        if ($this->data) {
            /*
             * POST - save/append/delete PageGallery file
             */        	
        	if (empty($this->data['content'])) {
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
            $page_gallery = array('<div class="error-page-gallery">Sorry, it seems there was an error somewhere. We cannot find the Story you requested.</div>');
            $link = '';
        }
        $this->set('page_gallery', $page_gallery);
        $this->set(compact('link', 'isPreview'));
        
        $done = $this->renderXHRByRequest(null, '/gallery/story', null, 0);
        if ($done) return;
        
        // render as http request
        $this->layout = "story";
    }
    
    
    /*
	 * move to pagemaker or story controller
     * SAVE/APPEND PageGallery PAGE to PageGallery BOOK. 
     */
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
		}
		$this->autoRender = false;
		header('Content-type: application/json');
		header('Pragma: no-cache');
		header('Cache-control: no-cache');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 		
		$success = $ret ? true : false;
		if ($success) {
			header("HTTP/1.1 201 CREATED");
			$message = "Your Story was saved.";
			$response = array(
				'key'=>$secretKeySrc, 
				'link'=>"/gallery/story/{$this->data['dest']}_{$secretKeySrc}",
			);
		} else {
			$message = "There was an error saving your Story. Please try again.";
			$response = array();
		}
		echo json_encode(compact('success', 'message', 'response'));
		return;
    }

	function upload_share() {
		$forceXHR = setXHRDebug($this, 0);
		$data = $_POST ? $_POST : $this->data;
		$this->autoRender = false;	
// $this->log("POST _FILES['Filedata'] follow:", LOG_DEBUG);			
		if (!empty($_FILES) && !isset($_FILES["Filedata"])) {
			$fileDataERROR = !isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0; 
$this->log("fileDataERROR={$fileDataERROR}, is_uploaded_file=".is_uploaded_file($_FILES["Filedata"]["tmp_name"]) , LOG_DEBUG);			
		}
		if (!empty($data)){
$this->log("POST to /gallery/upload_share", LOG_DEBUG);
$this->log($_FILES['Filedata'], LOG_DEBUG);	
$this->log($data['data']['photos'], LOG_DEBUG);


		    /*
			 * set story params for anonymous upload 
			 */ 
			$UPLOAD_FOLDER = Configure::Read('path.storyMakerUploader');
			$STORY_PREFIX = "story_";
			$EXTENSION = ".JPG";
		    $arrangement = json_decode($data['data']['arrangement'], true);
			$photos = json_decode($data['data']['photos'], true);
			$story_id = $arrangement['story_id'];
			$photo_id = $_FILES["Filedata"]["name"];
			$tmp_filepath = $_FILES["Filedata"]["tmp_name"];
			$dest_basepath = cleanpath("{$UPLOAD_FOLDER['folder_basepath']}{$STORY_PREFIX}{$story_id}");
					    
			$dest_filepath = $dest_basepath.DS.$photo_id.$EXTENSION;
// $this->log("__upload_share(): upload success, story_id={$story_id}, file dest={$dest_filepath}", LOG_DEBUG);				
			/*
			 * reset story folder if we are REPLACING photos
			 */ 
			if (!file_exists($dest_basepath)) mkdir($dest_basepath, 0775, true);
			/*
			 * TODO: check if $tmp_filepath == $dest_filepath
			 * 		can we cancel upload EARLY if the file is the same?
			 */
			if ( !move_uploaded_file($tmp_filepath, $dest_filepath) ){
				@unlink($tmp_filepath);
				// return error
				$response['success'] = 'false';
				$response['message'] = 'Error moving uploaded file';
				$response['response'] = array('tmp_name'=>$tmp_filepath, 'dest'=>$dest_filepath);
				header('Content-Type: application/json');
			    echo json_encode($response);
			    exit(0);
			}
			$success = true;
			$message = "POST data logged on server.";
			// $response = $data;
			$response['uploaded_file'] = $_FILES['Filedata'];
			unset($response['uploaded_file']['type']);
			unset($response['uploaded_file']['tmp_name']);
			
			/*
			 * check if story upload complete
			 */
			$count = 0; $total = count($photos);
			foreach ($photos as $photo){
				$check_filepath = $dest_basepath.DS.$photo['photo_id'].$EXTENSION;
				if (file_exists($check_filepath)) {
					$count++;
$this->log(">>> UPLOAD COMPLETE uuid={$photo['photo_id']},  file={$check_filepath}" , LOG_DEBUG);
				}
			}
			$response['story']['upload_complete'] = $count/$total;
			if ($count == $total) {
				$story_baseurl = "http://".env('HTTP_HOST').cleanpath("/{$UPLOAD_FOLDER['baseurl']}{$STORY_PREFIX}{$story_id}");
				$message = "story upload complete.";
				$response['story']['url'] = "{$story_baseurl}";
				$response['arrangement'] = $arrangement;
$this->log("StoryMaker: story upload complete. baseurl = {$story_baseurl}", LOG_DEBUG);					
					
			} else {
				$message = "received {$count} of {$total} photos";
				$response['story']['url'] = null;
			}
			$json_response = compact('success', 'message', 'response');
			
$this->log($json_response , LOG_DEBUG);			
			$this->viewVars['jsonData'] = $response['story']['upload_complete'];
			
			$json_response = json_encode($json_response);
			$this->layout = '';
			header('Content-type: application/json');
			echo header('Pragma: no-cache');
			echo header('Cache-control: no-cache');
			echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
			echo $json_response;
			exit;

			$done = $this->renderXHRByRequest('json', null, null , 0);			
			if ($done) return; 
		}
	}
}
?>
