<?php
/* CollectionsGroup Test cases generated on: 2010-03-30 05:03:53 : 1269923873*/
App::import('Model', 'CollectionsGroup');

class CollectionsGroupTestCase extends CakeTestCase {
	var $fixtures = array('app.collections_group', 'app.collection', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.assets_collection', 'app.assets_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->CollectionsGroup =& ClassRegistry::init('CollectionsGroup');
	}

	function endTest() {
		unset($this->CollectionsGroup);
		ClassRegistry::flush();
	}

}
?>