<?php
class WelcomeController extends AppController {

	var $name = 'Welcome';
	var $uses = NULL;
	var $layout = 'snappi';
//	var $components = array('Session');
//	var $helpers = array('Session');

	public $paginate = array();
	
	function beforeFilter() {
		parent::beforeFilter(); // disable access control for this controller
		$this->Auth->allow('*');
	}
	function preview($force = null){
		$this->layout = 'snappi-guest';
		// check for cookie 'donotshow.welcome-preview'
		$skip = isset($_COOKIE['donotshow']) ? $_COOKIE['donotshow'] : null;
		if (!empty($skip['welcome-preview'])){
			if ($force || isset($this->params['url']['show'])) {
				// show this page
			} else  {
				$this->redirect('/', null, true);
			}
		} 

	}
	function remixed(){
		$this->layout = false;
	}
	
	function index() {
		$this->redirect(array('action'=>'preview', 'show'));
	}

	function home() {
		if ($this->params['url']['url'] == "/") {
			$this->redirect("/welcome/home", 301, true);
		}
	}

	function test(){
		$this->autoRender=false;
		pr(Configure::read('Config'));
	}

	function connect(){
	}

	function about() {
	}

	function faq() {
	}

	function blog() {
		$this->redirect('http://www.facebook.com/pages/Snaphappi/16486082015', null, true);
	}

	function forums($encoded_url = null) {
		if ($encoded_url) {
			$target = base64_decode($encoded_url);
			$this->redirect($target, null, true);
		} else {
			$this->redirect('http://www.facebook.com/pages/Snaphappi/16486082015#/board.php?uid=16486082015', null, true);
		}
	}

	function tos() {
	}
}

?>
