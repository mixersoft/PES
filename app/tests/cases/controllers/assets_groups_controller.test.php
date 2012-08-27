<?php
/* AssetsGroups Test cases generated on: 2010-03-30 05:03:01 : 1269923881*/
App::import('Controller', 'AssetsGroups');

class TestAssetsGroupsController extends AssetsGroupsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class AssetsGroupsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.assets_group', 'app.asset', 'app.photostream', 'app.user', 'app.group', 'app.groups_user', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->AssetsGroups =& new TestAssetsGroupsController();
		$this->AssetsGroups->constructClasses();
	}

	function endTest() {
		unset($this->AssetsGroups);
		ClassRegistry::flush();
	}

	function testIndex() {

	}

	function testView() {

	}

	function testAdd() {

	}

	function testEdit() {

	}

	function testDelete() {

	}

}
?>