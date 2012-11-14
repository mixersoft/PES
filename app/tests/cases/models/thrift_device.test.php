<?php
/* ThriftDevice Test cases generated on: 2012-11-14 20:47:49 : 1352926069*/
App::import('Model', 'ThriftDevice');

class ThriftDeviceTestCase extends CakeTestCase {
	var $fixtures = array('app.thrift_device', 'app.provider_account', 'app.user', 'app.group', 'app.groupshot', 'app.best_groupshot', 'app.asset', 'app.shared_edit', 'app.user_edit', 'app.best_usershot', 'app.usershot', 'app.assets_usershot', 'app.collection', 'app.assets_collection', 'app.collections_group', 'plugin.tags.tag', 'plugin.tags.tagged', 'app.assets_group', 'app.assets_groupshot', 'app.groups_user', 'app.groups_provider_account', 'app.profile', 'app.auth_account', 'app.thrift_session', 'app.thrift_folder');

	function startTest() {
		$this->ThriftDevice =& ClassRegistry::init('ThriftDevice');
	}

	function endTest() {
		unset($this->ThriftDevice);
		ClassRegistry::flush();
	}

	function testNewDevice() {

	}

	function testNewDeviceForAuthToken() {

	}

	function testFindAllByAuthToken() {

	}

	function testFindDeviceByDeviceId() {

	}

}
