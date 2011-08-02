<?php
class WelcomeController extends AppController {

	var $name = 'Welcome';
	var $uses = NULL;
//	var $components = array('Session');
//	var $helpers = array('Session');

	public $paginate = array(
		'Asset'=>array(
			'limit'=>48,
			'order'=>array('Asset.dateTaken'=>'ASC'),
		),
		'Group'=>array(
			'limit' => 8,
			'order'=>array('Group.title'=>'ASC'),
		)
	);
	function preview(){
		$this->layout = false;
	}
	function beforeFilter() {
		parent::beforeFilter(); // disable access control for this controller
		$this->Auth->allow('*');
	}

	function index() {
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
