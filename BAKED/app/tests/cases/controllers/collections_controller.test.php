<?php
/* Collections Test cases generated on: 2010-03-30 05:03:01 : 1269923881*/
App::import('Controller', 'Collections');

class TestCollectionsController extends CollectionsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class CollectionsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.collection', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.assets_collection', 'app.assets_group', 'app.collections_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->Collections =& new TestCollectionsController();
		$this->Collections->constructClasses();
	}

	function endTest() {
		unset($this->Collections);
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