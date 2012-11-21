<?php
/* Usershot Test cases generated on: 2012-09-21 20:12:42 : 1348258362*/
App::import('Model', 'Usershot');

class UsershotTestCase extends CakeTestCase {
	var $fixtures = array(
		'app.usershot', 'app.assets_usershot', 'app.user', 'app.asset', 'app.shared_edit', 'app.user_edit', 'app.best_usershot',
		'app.asset_permission', 
		'app.profile', 'app.auth_account', 
		'app.provider_account', 'app.groups_provider_account',  
		'app.collection', 'app.assets_collection', 'app.collections_group', 
		'app.groups_user', 'app.group', 'app.assets_group', 
		'app.groupshot', 'app.assets_groupshot', 'app.best_groupshot', 
		'plugin.tags.tag', 'plugin.tags.tagged','plugin.metadata.metadatum',		
	);
	
	function setup_AppController (){
		App::import('Controller', 'app');
		$ac = new AppController();
	}

	function startTest() {
		$this->setup_AppController();
		$this->Usershot =& ClassRegistry::init('Usershot');
	}

	function endTest() {
		unset($this->Usershot);
		unset($ac);
		ClassRegistry::flush();
	}
	
	function setUser_Venice() {
		AppController::$userid = '12345678-1111-0000-0000-venice------'; 
		AppController::$role = 'USER';
		
		App::import('Lib', 'Permissionable.Permissionable');
		Permissionable::setUserId(AppController::$userid );
		Permissionable::setGroupId('role-----0123-4567-89ab---------user');
		Permissionable::setGroupIds(array('member---0123-4567-89ab-000000000002','member---0123-4567-89ab-000000000003'));
	}

	function setEditor_Operator() {
		AppController::$userid = '5013de12-1150-4c93-a9a3-20160afc480d'; // Users.username='operator'
		AppController::$role = 'OPERATOR';
		
		$this->Usershot->AssetsUsershot->Asset->Behaviors->attached('WorkorderPermissionable', array());
	}


	function testGroupAsShot() {
		$this->setUser_Venice();
		
		$this->Usershot =& ClassRegistry::init('Usershot');
		// $shot_8['aids'][]='4bbb3976-9a7c-4e05-900d-11a0f67883f5';	// bestshot
		// $shot_8['aids'][]='4bbb3976-3268-4073-878d-11a0f67883f5';
		// $shot_8['aids'][]='4bbb3976-3498-40ea-803d-11a0f67883f5';		
		// $shot_2['aids'][]='4ffc306c-9514-44b3-a572-4d04f67883f5';	// bestshot
		
		$groupAsShot_aids[] = '4bbb3976-9a7c-4e05-900d-11a0f67883f5';	// bestshot of 8
		$groupAsShot_aids[] = '4bbb3976-22b8-442c-b29a-11a0f67883f5';	// bestshot of 2
		
		/*
		 *  GroupAsShot, group 3 assets, taken from 2 bestshots
		 */
		$result = $this->Usershot->groupAsShot($groupAsShot_aids);
		$new_shotId = $result['response']['groupAsShot']['shotId'];
		$expected = Array
			(
			    'success' => 1,
			    'message' => Array
			        (
			            '0' => 'Usershot->groupAsShot: OK',
			            '1' => 'Usershot->unGroupShot: OK',
			        ),
			    'response' => Array
			        (
			            'groupAsShot' => Array
			                (
			                    'shotId' => $new_shotId,
			                    'bestshotId' => '4bbb3976-9a7c-4e05-900d-11a0f67883f5',
			                ),
			            'unGroupShot' => Array
			                (
			                    'shotIds' => Array
			                        (
			                            '0' => '4ffc3062-3a94-4f37-981b-4d04f67883f5',
			                            '1' => '4ffc306c-9514-44b3-a572-4d04f67883f5',
			                        )
			                )
			        )
			);
		$this->assertEqual($result, $expected);	
		
		/*
		 *  GroupAsShot, no permissions on submitted assetId
		 */
		$no_permission_aids = $groupAsShot_aids;
		$no_permission_aids[] = '4bbb3907-82f4-4452-90a5-11a0f67883f5';	// owner=sardinia
		$result = $this->Usershot->groupAsShot($no_permission_aids);
		$expected = Array
			(
			    'success' => 0,
			    'message' => Array
			        (
			            '0' => 'Error: No Permission on the following assetIds'
			        ),
			    'response' => Array
			        (
			            'asset_ids' => Array
			                (
			                    '2' => '4bbb3907-82f4-4452-90a5-11a0f67883f5'
			                )
			        )
			);
		$this->assertEqual($result, $expected);	
		
		/*
		 *  GroupAsShot, Wo/Operator tries to change User created usershot, no privilege
		 */
		$this->setEditor_Operator();
		$result = $this->Usershot->groupAsShot($groupAsShot_aids);
		$expected = Array
			(
			    'success' => 0,
			    'message' => 'Error: Current role has lower priority, no privilege to change existing Shot, role=OPERATOR',
			    'response' => Array(
			    	'existing_shots' => Array
		                (
		                    $new_shotId => 10
		                )
				)
			
			);
		$this->assertEqual($result, $expected);		
		
		
		/*
		 *  GroupAsShot, User tries to change Operator created usershot, priority OK
		 */
		// manually change owner/priority of Shot to workorderEditor
		$this->Usershot->id = $new_shotId;
		$this->Usershot->set(array('owner_id'=>AppController::$userid, 'priority'=>20));
		$this->Usershot->save();
		
		// switch back to User/priority
		$this->setUser_Venice();
		$result = $this->Usershot->groupAsShot($groupAsShot_aids);
		$new_shotId = $result['response']['groupAsShot']['shotId'];
		$expected = Array
			(
			    'success' => 1,
			    'message' => Array
			        (
			            '0' => 'Usershot->groupAsShot: OK',
			            '1' => 'Usershot->deactivateShot: OK'
			        ),
			    'response' => Array
			        (
			            'groupAsShot' => Array
			                (
			                    'shotId' => $new_shotId,
			                    'bestshotId' => '4bbb3976-9a7c-4e05-900d-11a0f67883f5',
			                ),
			            'deactivateShot' => Array
			                (
			                    'shotIds' => Array
			                        (
			                            '0' => '506460fb-fd70-4ee4-98ca-0904f67883f5'
			                        )
			                )
			        )
			); 
			
			
		/*
		 * GroupAsShot,  SCRIPT shot includes assets in existing (SCRIPT) shot
		 *    is it OK if asset is in existing USER or EDITOR shot??? 
		 * if (count($existing_shots) && $shot_priority == $this->_get_ShotPriority('SCRIPT'))  
		 */
			
			
	}
	
	
	function testRemoveFromShot() {
		$this->setUser_Venice();
		$this->Usershot =& ClassRegistry::init('Usershot');
		/*
		 * remove 1 asset from shot_count=8, should leave 7 shots, does not change bestshot
		 */
		$result = $this->Usershot->removeFromShot(array('4bbb3976-3268-4073-878d-11a0f67883f5'), '4ffc3062-3a94-4f37-981b-4d04f67883f5', '12345678-1111-0000-0000-venice------');
		$expected = Array
		(
		    'success' => 1,
		    'message' => Array
		        (
		            0 => 'Usershot->removeFromShot: OK',
		            1 => 'Usershot->updateShotCounterCache: OK',
		            2 => 'Usershot->updateBestShotSystem: OK. bestShotSystem NOT changed',
		        ),
		    'response' => Array
		        (
		            'removeFromShot' => Array
		                (
		                    'assetIds' => Array
		                        (
		                            0 => '4bbb3976-3268-4073-878d-11a0f67883f5'
		                        ),
		                ),
		            'updateBestShotSystem' => Array
		                (
		                    'asset_id' => '4bbb3976-9a7c-4e05-900d-11a0f67883f5',
		                    'changed' => ''
		                ),
		        )
		);
		$this->assertEqual($result, $expected);
		/*
		 *  remove bestshot asset from shot_count=7, should leave 6 shots, reset bestshot
		 */
		$result = $this->Usershot->removeFromShot(array('4bbb3976-9a7c-4e05-900d-11a0f67883f5'), '4ffc3062-3a94-4f37-981b-4d04f67883f5', '12345678-1111-0000-0000-venice------');
		$expected = Array
		(
		    'success' => 1,
		    'message' => Array
		        (
		            0 => 'Usershot->removeFromShot: OK',
		            1 => 'Usershot->updateShotCounterCache: OK',
		            2 => 'Usershot->updateBestShotSystem: OK. bestShotSystem changed',
		        ),
		    'response' => Array
		        (
		            'removeFromShot' => Array
		                (
		                    'assetIds' => Array
		                        (
		                            0 => '4bbb3976-9a7c-4e05-900d-11a0f67883f5',
		                        ),
		                    
		                ),
		            'updateBestShotSystem' => Array
		                (
		                    'asset_id' => '4bbb3976-3498-40ea-803d-11a0f67883f5',
		                    'changed' => 1
		                ),
		        )
		);
		$this->assertEqual($result, $expected);
		
		// remove 1 asset from shot_count=2, shot_count=1, DOES NOT unGroupShot
		$result = $this->Usershot->removeFromShot(array('4bbb3976-22b8-442c-b29a-11a0f67883f5'), '4ffc306c-9514-44b3-a572-4d04f67883f5', '12345678-1111-0000-0000-venice------');
		$expected = Array
		(
		    'success' => 1,
		    'message' => Array
		        (
		            0 => 'Usershot->removeFromShot: OK',
		            1 => 'Usershot->updateShotCounterCache: OK',
		            2 => 'Usershot->updateBestShotSystem: OK. bestShotSystem changed',
		        ),
		    'response' => Array
		        (
		            'removeFromShot' => Array
		                (
		                    'assetIds' => Array
		                        (
		                            0 => '4bbb3976-22b8-442c-b29a-11a0f67883f5'
		                        ),
		                    
		                ),
		            'updateBestShotSystem' => Array
		                (
		                    'asset_id' => '4bbb3976-ea30-4216-99ee-11a0f67883f5',
		                    'changed' => 1
		                ),
		        )
		);
		$this->assertEqual($result, $expected);	
		// TODO: check Usershot.assets_usershot_count
		// TODO: check BestUsershotSystem: best_usershots.usershot_id, asset_id, user_id=null
		
		/*
		 * a different USER tries to remove from USER usershot
		 * 	Should remove nothing	 
		 */
		AppController::$userid = '12345678-1111-0000-0000-sardinia----';
		$result = $this->Usershot->removeFromShot(array('4bbb3976-3498-40ea-803d-11a0f67883f5'), '4ffc3062-3a94-4f37-981b-4d04f67883f5', '12345678-1111-0000-0000-venice------');
		$expected = Array
			(
			    'success' => 0,
			    'message' => "Error: no privilege to remove from this Shot, Shot.owner_id=12345678-1111-0000-0000-venice------",
			    'response' => Array
			        (
			            'Shot.owner_id' => "12345678-1111-0000-0000-venice------"
			        )
			
			);
		$this->assertEqual($result, $expected);	
		 
		
		/*
		 * EDITOR tries to remove from  USER usershot
		 *  remove asset from shot_count=6, should give priority error
		 */
		$this->setEditor_Operator();
		$result = $this->Usershot->removeFromShot(array('4bbb3976-3498-40ea-803d-11a0f67883f5'), '4ffc3062-3a94-4f37-981b-4d04f67883f5', '12345678-1111-0000-0000-venice------');
		$expected = Array
			(
			    'success' => 0,
			    'message' => 'Error: Current role has lower priority, no privilege to remove from existing Shot, role=OPERATOR',
			    'response' => Array(
			    	'existing_shot' => Array
	                (
	                    '4ffc3062-3a94-4f37-981b-4d04f67883f5' => 10
	                )
				)
			
			);
		$this->assertEqual($result, $expected);		
		return;
	}

	function testUnGroupShot() {
		$this->Usershot =& ClassRegistry::init('Usershot');
		/*
		 * wrong Usershot.owner_id tried to delete Shot
		 */
		$this->setUser_Venice();
		AppController::$userid = '12345678-1111-0000-0000-sardinia----';
		$result = $this->Usershot->unGroupShot(array('4ffc3062-3a94-4f37-981b-4d04f67883f5', '4ffc306c-9514-44b3-a572-4d04f67883f5'));
		$expected = Array
			(
			    'success' => 0,
			    'message' => "Error: no privilege to remove from this Shot, Shot.owner_id=12345678-1111-0000-0000-venice------",
			    'response' => Array
			        (
			            'Usershot' => Array
			                (
			                    'id' => '4ffc3062-3a94-4f37-981b-4d04f67883f5',
			                    'owner_id' => '12345678-1111-0000-0000-venice------',
			                    'priority' => 10,
			                )
			
			        )
			
			);
		$this->assertEqual($result, $expected);	
		
		/*
		 * wrong priority, EDITOR tried to delete USER Shot
		 */
		$this->setEditor_Operator();
		$result = $this->Usershot->unGroupShot(array('4ffc3062-3a94-4f37-981b-4d04f67883f5', '4ffc306c-9514-44b3-a572-4d04f67883f5'));
		$expected = Array
			(
			    'success' => 0,
			    'message' => "Error: Current role has lower priority, no privilege to change existing Shot, role=OPERATOR",
			    'response' => Array
			        (
			            'existing_shots' => Array
			                (
			                    '0' => Array
			                        (
			                            'id' => '4ffc3062-3a94-4f37-981b-4d04f67883f5',
			                            'owner_id' => '12345678-1111-0000-0000-venice------',
			                            'priority' => 10,
			                        )
			                )
			        )
			);
		$this->assertEqual($result, $expected);	
		
		
		$this->setUser_Venice();
		/*
		 * unGroup 2 shots, correct priority and owner_id
		 */
		$result = $this->Usershot->unGroupShot(array('4ffc3062-3a94-4f37-981b-4d04f67883f5', '4ffc306c-9514-44b3-a572-4d04f67883f5'));
		$expected = Array
			(
			    'success' => 1,
			    'message' => Array
			        (
			            '0' => 'Usershot->unGroupShot: OK'
			        ),
			    'response' => Array
			        (
			            'unGroupShot' => Array
			                (
			                    'shotIds' => Array
			                        (
			                            '0' => '4ffc3062-3a94-4f37-981b-4d04f67883f5',
			                            '1' => '4ffc306c-9514-44b3-a572-4d04f67883f5'
			                        )
			                )
			        )
			);
		$this->assertEqual($result, $expected);	
		
		/*
		 * unGroup lower priority shot, should deactivate
		 */
		$this->setEditor_Operator();
		$groupAsShot_aids[] = '4bbb3976-9a7c-4e05-900d-11a0f67883f5';	// bestshot of 8
		$groupAsShot_aids[] = '4bbb3976-22b8-442c-b29a-11a0f67883f5';	// bestshot of 2
		$result = $this->Usershot->groupAsShot($groupAsShot_aids);
		$new_shotId = $result['response']['groupAsShot']['shotId']; 
		
		$this->setUser_Venice();  // USER has priority over EDITOR
		$result = $this->Usershot->unGroupShot(array($new_shotId));
// debug($result);		
		$expected = Array
			(
			    'success' => 1,
			    'message' => Array
			        (
			            '0' => 'Usershot->deactivateShot: OK'
			        ),
			    'response' => Array
			        (
			            '0' => 'Usershot->deactivateShot: OK',
			            'deactivateShot' => Array
			                (
			                    'shotIds' => Array
			                        (
			                            '0' => $new_shotId,
			                        )
			                )
			        )
			);
			
			$this->assertEqual($result, $expected);
		// TODO: check assets_usershots for deletion
		// TODO: check best_usershots for deletion
		return;
	}	
		
// 	
	// function testUpdateBestShotSystem() {
// 
	// }
// 
	// function testUpdateBestShotFromTopRated() {
// 
	// }
// 
	// function testSetBestshot() {
// 
	// }
// 
	// function testUpdateShotCounterCache() {
// 
	// }
// 
	// function testCopyToGroupshot() {
// 
	// }
// 
	// function testFindShotsByAssetId() {
// 
	// }
// 
	// function testUpdateGroupshotCounterByGroupId() {
// 
	// }

}
