<?php
/* Assets Test cases generated on: 2010-04-02 03:04:50 : 1270174190*/
App::import('Controller', 'Assets');

class TestAssetsController extends AssetsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class AssetsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.asset', 'app.provider_account', 'app.user', 'app.group', 'app.assets_group', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.groups_user', 'app.auth_account', 'app.photostream', 'app.user_edit', 'app.shared_edit');

	function startTest() {
		$this->Assets =& new TestAssetsController();
		$this->Assets->constructClasses();
	}

	function endTest() {
		unset($this->Assets);
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