<?php

$UPLOAD_FOLDER = Session::read('fileUploader.uploadFolder');
$BATCH_ID = $_GET['batchId'];

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
	
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){        
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
        }        
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /*
	 * SNAPPI counter spec for filenames = filename~XXX.jpg
	 * this is the opposite of __stripCounterFromFilename() in provider_accounts_controller
	 */
    private function __incrementCounterToFilename($filename) {
    	if(preg_match('/(.*)~(\d*)$/',$filename, $matches)) {
    		$count = $matches[2]+1;
    		$filename = "{$matches[1]}~{$count}";
    	} else {
    		$filename .= "~1" ;
    	};
    	return $filename;
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
    	if (!file_exists($uploadDirectory)) {
    		mkdir($uploadDirectory, 2775, true);
    	}
    	
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];
        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
//                $filename .= rand(10, 99);
				$filename = $this->__incrementCounterToFilename($filename);
            }
        }
        $destpath = $uploadDirectory . $filename . '.' . $ext;
        if ($this->file->save($destpath)){
        	return $destpath;
//        	/*
//        	 * import File into DB
//        	 */
//        	$response = importFile($destpath);
//            return $response;
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        
    }    
    
}
/**
 * @deprecate - use /my/upload
 * @param $photoPath
 * @param $batchId
 * @return unknown_type
 */
function importFile($photoPath, $batchId=null) {
	global $BATCH_ID;
	$ret = true;
	$userid = AppController::$userid;
	$PROVIDER_NAME = 'snappi';	// for drag-drop uploader
	$baseurl = '';	// drag drop uploader does not get filepath info
	$batchId = $batchId ? $batchId : $BATCH_ID;
	$response=array();
	
	$Import = loadComponent('Import', $this);
	$ProviderAccount = ClassRegistry::init('ProviderAccount');
	$Asset = ClassRegistry::init('Asset');
	$Shot = ClassRegistry::init('Shot');
	
	/*
	 * create ProviderAccount, if missing
	 */	
	$data['ProviderAccount']['provider_name']=$PROVIDER_NAME;
	$conditions = array('provider_name'=>$PROVIDER_NAME, 'user_id'=>$userid);
	$paData = $ProviderAccount->addIfNew($data['ProviderAccount'], $conditions,  $response);
	
	
	$meta = $Import->getMeta($photoPath);
	$data['Asset']['json_exif'] = $meta['exif'];
	$data['Asset']['iptc_exif'] = $meta['iptc'];
	$data['Asset']['batchId'] = $batchId;
	$data['Asset']['rel_path'] = '';

	/****************************************************
	 * setup data['Asset'] to create new Asset
	 * not a controller
	 */
	$profile = $this->getProfile();
	if (isset($profile['Profile']['privacy_assets'])) {
		$data['Asset']['perms'] = $profile['Profile']['privacy_assets'];
	}	
	$assetData = $Asset->addIfNew($data['Asset'], $paData['ProviderAccount'], $baseurl, $photoPath, $response);		
	
	// move file to staging server 
	$src = json_decode($assetData['Asset']['json_src'], true);
	$stage=array('src'=>$photoPath, 'dest'=>$src['root']);
 	if ($ret3 = $Import->import2stage($stage['src'], $stage['dest'], null, $move = true)) {
 		$response['message'][]="file staged successfully, dest={$stage['dest']}";
	} else $response['message'][]="Error staging file, src={$stage['src']}";
 	$ret = $ret && $ret3;

 	$response['success'] = $ret ? 'true' : 'false';
	$response['response'] = json_encode($data);
	return $response;
}
//
//// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array();
//// max file size in bytes
$sizeLimit = 8 * 1024 * 1024;
//
//$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
//$result = $uploader->handleUpload($UPLOAD_FOLDER, false);
//// to pass data through iframe you will need to encode all html tags
//Configure::write('debug', 0);
//echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
