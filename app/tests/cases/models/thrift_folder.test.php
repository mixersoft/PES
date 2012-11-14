<?php
/* ThriftFolder Test cases generated on: 2012-11-14 20:48:26 : 1352926106*/
App::import('Model', 'ThriftFolder');

class ThriftFolderTestCase extends CakeTestCase {
	var $fixtures = array('app.thrift_folder', 'app.thrift_device', 'app.provider_account', 'app.user', 'app.group', 'app.groupshot', 'app.best_groupshot', 'app.asset', 'app.shared_edit', 'app.user_edit', 'app.best_usershot', 'app.usershot', 'app.assets_usershot', 'app.collection', 'app.assets_collection', 'app.collections_group', 'plugin.tags.tag', 'plugin.tags.tagged', 'app.assets_group', 'app.assets_groupshot', 'app.groups_user', 'app.groups_provider_account', 'app.profile', 'app.auth_account', 'app.thrift_session');

	function startTest() {
		$this->ThriftFolder =& ClassRegistry::init('ThriftFolder');
	}

	function endTest() {
		unset($this->ThriftFolder);
		ClassRegistry::flush();
	}

	function testAddFolder() {

	}

	function testDeleteFolder() {

	}

	function testFindByDeviceUUID() {

	}

	function testFindByThriftDeviceId() {

	}

	function testUpdateFolderByNativePath() {

	}

	function testFindByNativePath() {

	}

	function testGetFile() {

	}

}
