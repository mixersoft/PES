<?php
class SharedEdit extends AppModel {
	var $name = 'SharedEdit';
	var $primaryKey = 'asset_id';
	var $displayField = 'score';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $hasMany = array(
		'Asset' => array(
			'className' => 'Asset',
			'foreignKey' => 'asset_id',
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
	 * @params asset_id array of key values to limit update
	 * 
set @aid:='';
delete from shared_edits where asset_id=@aid;
-- delete from user_edits where asset_id=@aid;	
UPDATE shared_edits se
JOIN (
select asset_id, sum(rating) as points,  count(rating) as votes, round(sum(rating)/count(rating),2) as score
from user_edits ue
where ue.asset_id=@aid
group by asset_id
-- having votes>1
) AS ue ON se.asset_id = ue.asset_id
SET se.points = ue.points, se.votes=ue.votes, se.score=ue.score;
	 * 
	 */
	public function update_score($asset_id=array()) {
		if (is_string($asset_id)) $asset_id = explode(',',$asset_id);
		if (!empty($asset_id)) {
		$subselect_SQL = "
	select asset_id, sum(rating) as points, count(rating) as votes, round(sum(rating)/count(rating),2) as score
	from user_edits
	group by asset_id
	WHERE asset_id IN ('".implode("','", $asset_id)."')		
		";			
		} else {
		$subselect_SQL = "
	select asset_id, sum(rating) as points,  count(rating) as votes, round(sum(rating)/count(rating),2) as score
	from user_edits
	group by asset_id
	having votes>1		
		";			
		}
		$update_SQL = "
UPDATE shared_edits se
JOIN ( {$subselect_SQL}) AS ue ON se.asset_id = ue.asset_id
SET se.points = ue.points, se.votes=ue.votes, se.score=ue.score;";

		$this->query($update_SQL);
		return true;
	} 

}
?>