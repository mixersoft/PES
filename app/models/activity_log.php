<?php

class ActivityLog extends AppModel {
	public $name = 'ActivityLog';
	public $useDbConfig = 'workorders';

	public $belongsTo = array(
		'Editor' => array(
			'className' => 'WorkorderEditor', 
			'foreignKey' => 'editor_id'
		),
		'Workorder',
		'TasksWorkorder',
	);

	public $hasMany = array(
		'FlagComment' => array('className' => 'ActivityLog', 'foreignKey' => 'flag_id')
	);

	public $order = array('ActivityLog.created' => 'desc', 'ActivityLog.id' => 'desc');

	public $validate = array(
		'comment' => array('rule' => 'notEmpty'),
	);


	public function afterSave($created) {
		$this->updateCacheFields($this->id);
	}


	/**
	* update the fields workorder_id and task_workorder_id with are a cache for easy later find
	*/
	public function updateCacheFields($id) {
		$log = $this->findById($id);
		$forSave = array();
		switch ($log['ActivityLog']['model']) {
			case 'Workorder':
				$forSave['workorder_id'] = $log['ActivityLog']['foreign_key'];
			break;
			case 'TasksWorkorder':
				$forSave['tasks_workorder_id'] = $log['ActivityLog']['foreign_key'];
				$task = $this->TasksWorkorder->findById($log['ActivityLog']['foreign_key']);
				$forSave['workorder_id'] = $task['TasksWorkorder']['workorder_id'];
			break;
			case 'Asset':
				// NOTE: when flagging an Asset, you MUST provide the workorder_id/tasks_workorder_id 
				if (!empty($log['ActivityLog']['tasks_workorder_id'])) {
					$task = $this->TasksWorkorder->findById($log['ActivityLog']['tasks_workorder_id']);
					$forSave['workorder_id'] = $task['TasksWorkorder']['workorder_id'];
				}
			break;
		}
		if (empty($forSave)) return;
		$this->id = $id;
		return $this->save(array('ActivityLog'=>$forSave), array('callbacks' => false));
	}




	/**
	* update the parent's flag status of a recently comment saved.
	*
	* when a comment is made into a flagged or cleared activity log, we must update the
	* flag status of the parent comment.
	*/
	public function updateParentFlag($childrenId, $newFlagStatus) {
		$comment = $this->findById($childrenId);
		if (!$comment) return null;
		$this->id = $comment['ActivityLog']['flag_id'];
		return $this->saveField('flag_status', $newFlagStatus);
	}


	
}