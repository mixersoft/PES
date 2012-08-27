<?php
/* ProviderAccount Test cases generated on: 2010-04-02 02:04:42 : 1270173102*/
App::import('Model', 'ProviderAccount');

class ProviderAccountTestCase extends CakeTestCase {
	var $fixtures = array('app.provider_account', 'app.user', 'app.group', 'app.asset', 'app.shared_edit', 'app.user_edit', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.groups_user', 'app.auth_account', 'app.photostream');

	function startTest() {
		$this->ProviderAccount =& ClassRegistry::init('ProviderAccount');
	}

	function endTest() {
		unset($this->ProviderAccount);
		ClassRegistry::flush();
	}

}
?>