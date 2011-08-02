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
			'foreignKey' => 'asset_hash',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	
	public function saveEdit($data) {
		// expecting $data['UserEdit'][]
		// INSERT INTO `user_edits` (`rotate`, `rating`, `id`, `owner_id`, `asset_hash`, `modified`, `created`, `id`) VALUES (NULL, 0, NULL, '12345678-1111-0000-0000-venice------', '4d2bea302443acddcfd60f4ac7808b20', '2010-08-24 02:10:22', '2010-08-24 02:10:22', '4c732a0e-f71c-4243-aa4b-4f29d109ed39')
//print_r($data);		
		$fields = array('id', 'rotate', 'rating','asset_hash', 'owner_id');
		$columns = array();
		$values = array();
		$update = array();
		foreach ($fields as $field) {
			if (!isset($data['UserEdit'][$field])) continue;
			$columns[] = $field;
			$v = $data['UserEdit'][$field]===null ? "NULL" : "'{$data['UserEdit'][$field]}'";
			$values[] = $v;
			if ($field == 'rotate') $update[] = "`{$field}`=VALUES(`{$field}`)";	
			if ($field == 'rating') $update[] = "`{$field}`=VALUES(`{$field}`)";				
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