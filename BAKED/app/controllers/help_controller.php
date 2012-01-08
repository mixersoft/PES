<?php 
class HelpController extends AppController {
    public $name = 'Help';
	public $uses = 'Help';
	public $viewPath = 'help';
    public $helpers = array(
		'Time',
		'Text',
		'Layout',
	);
    public $layout = 'snappi';
	public $components = array(
		'Comments.Comments' => array( 'userModelClass' => 'User'),
		'Search.Prg',
	);	
    
    function beforeFilter() {
    	parent::beforeFilter();
        $this->Auth->allow('*');
		if (isset($this->Comments)) {
			$this->Comments->actionNames = array('topic');
			$this->Comments->viewVariable = 'data';
		}	
    }
   
    function topic($id) {
		if (isset($this->params['url']['view'])) {
			$this->Session->write("comments.viewType.help", $this->params['url']['view']);
			$this->redirect($this->here, null, true);
		}
    	list($controller, $alias, $action) = explode('~',$id);
		
		$data = $this->Help->findOrCreate($id);	
		$this->set('data', $data);
		$help_page = array();
		$help_page['class']=$controller;
		$help_page['alias']=$alias;
		$help_page['action']=$action;
		$help_page['id']=$id;
		$this->set('help_page', $help_page);
		
		$done = $this->renderXHRByRequest(null, '/elements/comments/help', 0);
		// or $this->autoRender		
    }

	public function callback_commentsInitType(){
		return parent::callback_commentsInitType('help');
	}

}
?>
