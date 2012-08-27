<?php
/* AssetsCollections Test cases generated on: 2010-03-30 05:03:01 : 1269923881*/
App::import('Controller', 'AssetsCollections');

class TestAssetsCollectionsController extends AssetsCollectionsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class AssetsCollectionsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.assets_collection', 'app.collection', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.assets_group', 'app.collections_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->AssetsCollections =& new TestAssetsCollectionsController();
		$this->AssetsCollections->constructClasses();
	}

	function endTest() {
		unset($this->AssetsCollections);
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