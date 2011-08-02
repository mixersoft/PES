<?php
/* Asset Test cases generated on: 2010-04-02 02:04:55 : 1270172995*/
App::import('Model', 'Asset');

class AssetTestCase extends CakeTestCase {
	var $fixtures = array('app.asset', 'app.provider_account', 'app.user', 'app.group', 'app.assets_group', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.groups_user', 'app.auth_account', 'app.photostream', 'app.user_edit', 'app.shared_edit');

	function startTest() {
		$this->Asset =& ClassRegistry::init('Asset');
	}

	function endTest() {
		unset($this->Asset);
		ClassRegistry::flush();
	}

}
?>