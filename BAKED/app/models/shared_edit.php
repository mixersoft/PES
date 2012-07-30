<?php
class SharedEdit extends AppModel {
	var $name = 'SharedEdit';
	var $primaryKey = 'asset_hash';
	var $displayField = 'score';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $hasMany = array(
		'Asset' => array(
			'className' => 'Asset',
			'foreignKey' => 'asset_hash',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);
	
	/**
	 * UPDATE shared_edit score, votes, points values from user_edits table 
	 * @params asset_hash array of key values to limit update
	 * 
delete from shared_edits where asset_hash='';
delete from user_edits where asset_hash='';	
UPDATE shared_edits se
JOIN (
select asset_hash, sum(rating) as points,  count(rating) as votes, round(sum(rating)/count(rating),2) as score
from user_edits
group by asset_hash
having votes>1
) AS ue ON se.asset_hash = ue.asset_hash
SET se.points = ue.points, se.votes=ue.votes, se.score=ue.score;
	 * 
	 */
	public function update_score($asset_hash=array()) {
		if (is_string($asset_hash)) $asset_hash = explode(',',$asset_hash);
		if (!empty($asset_hash)) {
		$subselect_SQL = "
	select asset_hash, sum(rating) as points, count(rating) as votes, round(sum(rating)/count(rating),2) as score
	from user_edits
	group by asset_hash
	WHERE asset_hash IN ('".implode("','", $asset_hash)."')		
		";			
		} else {
		$subselect_SQL = "
	select asset_hash, sum(rating) as points,  count(rating) as votes, round(sum(rating)/count(rating),2) as score
	from user_edits
	group by asset_hash
	having votes>1		
		";			
		}
		$update_SQL = "
UPDATE shared_edits se
JOIN ( {$subselect_SQL}) AS ue ON se.asset_hash = ue.asset_hash
SET se.points = ue.points, se.votes=ue.votes, se.score=ue.score;";

		$this->query($update_SQL);
		return true;
	} 

}
?>