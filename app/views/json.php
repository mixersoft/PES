<?php
class JsonView extends View {
	static $once = false;		// for some unknown reason, this is called 2 times
	public function render($action = null, $layout = null, $file = null) {
		if (JsonView::$once) return;
	
		$response = false;

		if (isset($this->viewVars['response']) && !empty($this->viewVars['response'])) {
			$response = $this->viewVars['response'];
		}

		// Override $response and use 'json' if set, for raw JSON data
		if (isset($this->viewVars['jsonData']) && !empty($this->viewVars['jsonData'])) {
			$response = $this->viewVars['jsonData'];
		}
		
		// add standard JSON response format if missing
		if (!isset($response['success'])) {
			$response = array('success'=>'true', 'message'=>'', 'response'=>$response);
		}
//		Configure::write('debug', 0);
//		header('Content-type: application/json');

		if (empty($response)) {

			return '[]';

		}
		$output = json_encode($response);
		$output .= $this->element('dumpSQL');
		JsonView::$once = true;
		if (isset($this->params['url']['callback']) && strpos($this->params['url']['callback'],'YUI.Env.JSONP')===0){
			return "{$this->params['url']['callback']}({$output})";
		} else 	return $output; 
	}
}
?>