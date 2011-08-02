<?php
/* UserEdit Test cases generated on: 2010-04-02 03:04:58 : 1270174018*/
App::import('Model', 'UserEdit');

class UserEditTestCase extends CakeTestCase {
	var $fixtures = array('app.user_edit', 'app.user', 'app.group', 'app.asset', 'app.provider_account', 'app.shared_edit', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.groups_user', 'app.auth_account', 'app.photostream');

	function startTest() {
		$this->UserEdit =& ClassRegistry::init('UserEdit');
	}

	function endTest() {
		unset($this->UserEdit);
		ClassRegistry::flush();
	}

}
?>