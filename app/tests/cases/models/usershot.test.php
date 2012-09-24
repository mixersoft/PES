<?php
/* Usershot Test cases generated on: 2012-09-21 20:12:42 : 1348258362*/
App::import('Model', 'Usershot');

class UsershotTestCase extends CakeTestCase {
	var $fixtures = array(
		'app.usershot', 'app.assets_usershot', 'app.user', 'app.asset', 'app.shared_edit', 'app.user_edit', 'app.best_usershot', 
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
		AppController::$userid = '12345678-1111-0000-0000-venice------'; 
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


	function testRemoveFromShot() {
		$this->Usershot =& ClassRegistry::init('Usershot');
		// remove 1 asset from shot_count=8, should leave 7 shots, does not change bestshot
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
		// remove bestshot asset from shot_count=8, should leave 7 shots, reset bestshot
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
		
		// remove 1 asset from shot_count=2, should delete shot
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
		return;
	}


	// function testGroupAsShot() {
// 
	// }
// 
	// function testUnGroupShot() {
// 
	// }	
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
