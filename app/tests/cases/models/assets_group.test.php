<?php
/* AssetsGroup Test cases generated on: 2010-03-30 05:03:51 : 1269923871*/
App::import('Model', 'AssetsGroup');

class AssetsGroupTestCase extends CakeTestCase {
	var $fixtures = array('app.assets_group', 'app.asset', 'app.photostream', 'app.user', 'app.group', 'app.groups_user', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->AssetsGroup =& ClassRegistry::init('AssetsGroup');
	}

	function endTest() {
		unset($this->AssetsGroup);
		ClassRegistry::flush();
	}

}
?>