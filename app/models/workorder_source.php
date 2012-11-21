<?php

class WorkorderSource extends AppModel {
	public $useDbConfig = 'workorders';
	public $useTable = 'workorder_sources';		// set to 'users' or 'groups' depending on Workorder.source_model
	public $displayField = 'label';
	
	/*
	 * create view sql
	 CREATE VIEW `workorder_sources` AS 
	 * select `u`.`id` AS `id`,`u`.`username` AS `label`,`u`.`src_thumbnail` AS `src_thumbnail`,'User' AS `model_name`,'person' AS `controller` 
	 * from (`snappi`.`users` `u` 
	 * join `snappi_wms`.`workorders` `w` on((`w`.`source_id` = `u`.`id`))) 
	 * union select `g`.`id` AS `id`,`g`.`title` AS `label`,`g`.`src_thumbnail` AS `src_thumbnail`,'Group' AS `model_name`,'groups' AS `controller` from (`snappi`.`groups` `g` join `snappi_wms`.`workorders` `w` on((`w`.`source_id` = `g`.`id`)));
	 * 
	 */
	

}