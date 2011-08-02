<?php
class AppController extends Controller {
var $components = array('RequestHandler');

	function beforefilter()
	{
		// check for mobile devices
		if ($this->RequestHandler->isMobile()) $this->RequestHandler->renderAs($this, 'mobile');
		if ($this->RequestHandler->isMobile()) {
			// if device is mobile, change layout to mobile
			$this->layout = 'mobile';
			
			// and if a mobile view file has been created for the action, serve it instead of the default view file
			$mobileViewFile = VIEWS . strtolower($this->params['controller']) . '/mobile/' . $this->params['action'] . '.ctp';
			if (file_exists($mobileViewFile)) {
				$mobileView = strtolower($this->params['controller']) . '/mobile/';
				$this->viewPath = $mobileView;
			}
		}

	}
}
