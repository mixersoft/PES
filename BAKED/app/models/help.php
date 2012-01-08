<?php
class Help extends AppModel {
	public $name = 'Help';
	public $useTable = 'Help';
	public $actsAs = array(
		'Comments.Sluggable' => array('label' => 'title'),	
	);	
	
	public function findOrCreate($id) {
		$this->contain('Comment');
		$options = array('conditions'=>array('Help.id'=>$id));
		$data = $this->find('first', $options);
		if (empty($data)) {
			$data = array('Help'=>array());
			$data['Help']['id'] = $id;
			$data['Help']['lastVisit'] = date('Y-m-d H:i:s',time());
			// create new Help item
			$this->create();
			if (!$this->save($data)) {
				$this->log('ERROR: Help::findOrCreate() - error creating new record');
				return null;
			} 
		}
		return $data;	
	}
}
?>