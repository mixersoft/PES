<?php
class UserEdit extends AppModel {
	var $name = 'UserEdit';
	var $displayField = 'rating';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'Owner' => array(
			'className' => 'User',
			'foreignKey' => 'owner_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Asset' => array(
			'className' => 'Asset',
			'foreignKey' => 'asset_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	
	public function saveEdit($data) {
		// expecting $data['UserEdit'][]
		// INSERT INTO `user_edits` (`rotate`, `rating`, `id`, `owner_id`, `asset_id`, `modified`, `created`, `id`) VALUES (NULL, 0, NULL, '12345678-1111-0000-0000-venice------', '4d2bea302443acddcfd60f4ac7808b20', '2010-08-24 02:10:22', '2010-08-24 02:10:22', '4c732a0e-f71c-4243-aa4b-4f29d109ed39')
		$fields = array('id', 'rotate', 'rating','asset_id', 'owner_id');
		$update_fields = array('rotate', 'rating', 'isEditor', 'isReviewed', 'modified');
		$columns = array();
		$values = array();
		$update = array();
		if (in_array('WorkorderPermissionable', $this->Asset->Behaviors->attached())) {
			$fields = array_merge($fields, array('isEditor', 'isReviewed'));
			$data['UserEdit']['isEditor'] = 1;	// record as editor action
		}
		foreach ($fields as $field) {
			if (!isset($data['UserEdit'][$field])) continue;
			$columns[] = $field;
			$v = $data['UserEdit'][$field]===null ? "NULL" : "'{$data['UserEdit'][$field]}'";
			$values[] = $v;
			if (in_array($field, $update_fields)){
				$update[] = "`{$field}`=VALUES(`{$field}`)";				
			}
		}
		$columns[] = 'modified';
		$values[] = 'NOW()'; 
		if (@ise($columns['id'])) {
			$columns[] = 'created';
			$values[] = 'NOW()'; 
		}
		$column_list = implode($columns, '`,`');
		$value_list = implode($values, ",");
		
		$update_list = !empty($update) ? "ON DUPLICATE KEY UPDATE " . implode($update, ",") : '';
		$sql = "INSERT INTO user_edits (`{$column_list}`) VALUES ({$value_list}) {$update_list};";
		return $this->query($sql) !== false;
	}
}
?>