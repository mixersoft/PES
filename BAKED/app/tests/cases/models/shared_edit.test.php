<?php
/* SharedEdit Test cases generated on: 2010-04-02 03:04:50 : 1270174010*/
App::import('Model', 'SharedEdit');

class SharedEditTestCase extends CakeTestCase {
	var $fixtures = array('app.shared_edit', 'app.asset', 'app.provider_account', 'app.user', 'app.group', 'app.assets_group', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.groups_user', 'app.auth_account', 'app.photostream', 'app.user_edit');

	function startTest() {
		$this->SharedEdit =& ClassRegistry::init('SharedEdit');
	}

	function endTest() {
		unset($this->SharedEdit);
		ClassRegistry::flush();
	}

}
?>