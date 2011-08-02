<?php
/* GroupsUser Test cases generated on: 2010-03-30 05:03:54 : 1269923874*/
App::import('Model', 'GroupsUser');

class GroupsUserTestCase extends CakeTestCase {
	var $fixtures = array('app.groups_user', 'app.user', 'app.group', 'app.asset', 'app.photostream', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->GroupsUser =& ClassRegistry::init('GroupsUser');
	}

	function endTest() {
		unset($this->GroupsUser);
		ClassRegistry::flush();
	}

}
?>