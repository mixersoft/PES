<?php
/* AuthAccount Test cases generated on: 2010-03-30 05:03:52 : 1269923872*/
App::import('Model', 'AuthAccount');

class AuthAccountTestCase extends CakeTestCase {
	var $fixtures = array('app.auth_account', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.user_edit');

	function startTest() {
		$this->AuthAccount =& ClassRegistry::init('AuthAccount');
	}

	function endTest() {
		unset($this->AuthAccount);
		ClassRegistry::flush();
	}

}
?>