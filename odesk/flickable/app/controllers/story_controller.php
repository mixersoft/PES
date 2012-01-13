<?php
class  StoryController extends AppController {
	// public $layout = '';
	public $uses = null;
	public $helpers = array('RequestHandler');
	function __getPhotos($photos, $baseurl = ''){
		$output = array();
		foreach ($photos as $photo) {
			$p = array();
			$p['id'] = $photo['id'];
			// $p['caption'] = $photo['Photo']['Caption'];
			$p['unixtime'] = $photo['Photo']['TS'];
			$p['dateTaken'] = $photo['Photo']['DateTaken'];
			$p['rating'] = $photo['Photo']['Fix']['Rating'];
			$p['width'] = $photo['Photo']['Img']['Src']['W'];
			$p['height'] = $photo['Photo']['Img']['Src']['H'];
			// $p['src'] = $baseurl . $photo['Photo']['Img']['Src']['previewSrc'];
			$output[] = $p;
		}
	//	sort($output, );
		return $output;
	}
	
	
	function __sortPhotos($photos, $count = null){
		// Obtain a list of columns
		foreach ($photos as $key => $row) {
		    $rating[$key]  = $row['rating'];
		}
		array_multisort($rating, SORT_DESC, $photos);
		if ($count) $photos = array_slice($photos, 0, $count);
		return $photos;
	}	
	
	public function create() {
		if (strpos(env('SERVER_NAME'), 'snaphappi.com')) {
			Configure::write('debug', 0);
		}
		$forceXHR = setXHRDebug($this, 0);
		if (empty($this->data) && !empty($this->params['url']['data'])) {
			$this->data = $this->params['url']['data'];
			$this->log("/story/create: using forcexhr=true, this->params['url']['data'] ", LOG_DEBUG);
		}
		
		if (isset($this->data['photos'])) {
			$photos = json_decode($this->data['photos'], true);
		}
$this->log($this->data, LOG_DEBUG);
// $this->log($this->here, LOG_DEBUG);

		
		/*
		 * create arrangement
		 */
		App::import('Vendor', 'pagemaker', array('file'=>'pagemaker'.DS.'cluster-collage.4.php'));
 		$layoutPhotos = $this->__sortPhotos($photos, null);
        $collage = new ClusterCollage(0.2);		
		$COUNT = count($photos);
        do {
            $tempPhotos = array_slice($layoutPhotos,0,$COUNT);

            array_splice($layoutPhotos,0,$COUNT);
            try {
                $collage->setPhotos($tempPhotos, $tempPhotos);
                $arrangement = $collage->getArrangement();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        } while (!empty($layoutPhotos));
		// debug($arrangement);		


		/*
		 * format response
		 */
		$response = array();
		$response['success']=false;
		if ($arrangement) {
			$response['success']=true;	
			$response['message']= $this->data['photos'];
			$response['json'] = $arrangement;
		}
$this->log($arrangement, LOG_DEBUG);		
		$json_response = json_encode($response);
		if ($this->RequestHandler->ext == 'json') {
			$this->layout = '';
			header('Content-type: application/json');
			echo header('Pragma: no-cache');
			echo header('Cache-control: no-cache');
			echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
			echo $json_response;
			exit;
		} else {
			/*
			 * json POST
			 * */
			echo "<P>JSON POST data:  </P>";
			echo "<blockquote>".urlencode($this->data['photos'])."</blockquote>"; 
			/*
			 * json response
			 * */
			echo "<P>JSON response string:  </P>";
			echo "<blockquote>{$json_response}</blockquote>"; 
			echo "</body>";  
			exit;
		}
	}

	/*
	 * deprecate: using aws.snaphappi.com/gallery/upload_share/.json
	 */
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
