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
	/**
	 * Get the number of steps needed for TreeBehavior::moveup/movedown to move 
	 * 		item up/down 1 step within current generation, i.e. by prev/next sibling
	 * 	- works with Commentable/TreeBehavior
	 * @params $foreign_key primary_key of Commentable topic
	 * @params $id UUID, id of Tree object to move, i.e. Comment
	 * @params $dir string, [UP | DOWN]
	 */
	function __getSibling_in_Steps(& $ActsAsTree, $foreign_key, $id, $dir = 'UP') {
		if (isset($this->params['url']['step'])) return $this->params['url']['step'];
		$parent = $ActsAsTree->getparentnode($id);
		$parentId = isset($parent[$ModelAlias]['id']) ? $parent[$ModelAlias]['id'] : 0;
		$ModelAlias = $ActsAsTree->alias;
		$conditions = array(
			"{$ModelAlias}.foreign_key"=> $foreign_key,
			"{$ModelAlias}.parent_id"=>$parentId,
			"{$ModelAlias}.approved" => 1,				// TODO: is this correct?
		);
		$order = array("{$ModelAlias}.lft" => "asc");
		$fields = array(
			"{$ModelAlias}.id", "{$ModelAlias}.parent_id", "{$ModelAlias}.approved",
			"{$ModelAlias}.lft", "{$ModelAlias}.rght",
			"{$ModelAlias}.foreign_key", 
		);
		$flat = $ActsAsTree->find('all', compact('conditions', 'order', 'fields'));
		$steps = 0;	
		foreach ($flat as $i=>$row){
			if (!isset($prev)) $prev = $row;
			if ($dir == 'UP' && $row[$ModelAlias]['id'] == $id) {
				$steps = ($row[$ModelAlias]['lft'] - $prev[$ModelAlias]['lft'])/2;
				break;
			}
			if ($dir == 'DOWN' && $i && $prev[$ModelAlias]['id'] == $id) {
				$steps = ($row[$ModelAlias]['rght'] - $prev[$ModelAlias]['rght'])/2 ;
				// $steps++;
				break;
			};
			$prev = $row;
		}
		return $steps;
	}
	function up($id) {
		if (isset($this->params['named']['comment'])) {
			$commentId = $this->params['named']['comment'];
			$ActsAsTree = $this->Help->Comment;
			$steps = $this->__getSibling_in_Steps($ActsAsTree, $id, $commentId, 'UP');
			$ret = $ActsAsTree->moveup($commentId,  $steps);
			$this->Session->setFlash("bump comment={$commentId}, steps={$steps}, result={$ret}");		
			$this->redirect(array('action'=>'topic', $id));
		}
	}
	function down($id) {
		if (isset($this->params['named']['comment'])) {
			$commentId = $this->params['named']['comment'];
			$ActsAsTree = $this->Help->Comment;
			$steps = $this->__getSibling_in_Steps($ActsAsTree, $id, $commentId, 'DOWN');
			if ($steps) $ret = $ActsAsTree->movedown($commentId, $steps);
			$this->Session->setFlash("bump down comment={$commentId}, steps={$steps}, result=".($ret ? 'success' : 'failure'));		
			$this->redirect(array('action'=>'topic', $id));
		}
	}

	public function callback_commentsInitType(){
		return parent::callback_commentsInitType('help');
	}
	
	 /**
     * renders raw HTML markup templates for use in javascript
     * @param $name string - name of view file
     */
    function markup($name) {
    	// exports cookies with 'SNAPPI_' to PAGE.Cookie
    	$this->layout='markup';
		$this->__setCookies();
		$this->autoRender = false;
		$viewFile = DS."help".DS.$name;
    	$this->render(null, 'markup', $viewFile);
    }

}
?>
