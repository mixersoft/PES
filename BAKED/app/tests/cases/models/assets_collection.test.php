<?php
/* AssetsCollection Test cases generated on: 2010-03-30 05:03:50 : 1269923870*/
App::import('Model', 'AssetsCollection');

class AssetsCollectionTestCase extends CakeTestCase {
	var $fixtures = array('app.assets_collection', 'app.collection', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.assets_group', 'app.collections_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->AssetsCollection =& ClassRegistry::init('AssetsCollection');
	}

	function endTest() {
		unset($this->AssetsCollection);
		ClassRegistry::flush();
	}

}
?>