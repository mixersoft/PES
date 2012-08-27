<?php
/* Group Test cases generated on: 2010-03-30 05:03:53 : 1269923873*/
App::import('Model', 'Group');

class GroupTestCase extends CakeTestCase {
	var $fixtures = array('app.group', 'app.user', 'app.groups_user', 'app.asset', 'app.photostream', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->Group =& ClassRegistry::init('Group');
	}

	function endTest() {
		unset($this->Group);
		ClassRegistry::flush();
	}

}
?>