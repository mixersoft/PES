<?php
/* User Test cases generated on: 2010-03-30 05:03:57 : 1269923877*/
App::import('Model', 'User');

class UserTestCase extends CakeTestCase {
	var $fixtures = array('app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->User =& ClassRegistry::init('User');
	}

	function endTest() {
		unset($this->User);
		ClassRegistry::flush();
	}

}
?>