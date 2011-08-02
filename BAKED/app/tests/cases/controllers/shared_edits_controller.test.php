<?php
/* SharedEdits Test cases generated on: 2010-04-02 03:04:13 : 1270174213*/
App::import('Controller', 'SharedEdits');

class TestSharedEditsController extends SharedEditsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class SharedEditsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.shared_edit', 'app.asset', 'app.provider_account', 'app.user', 'app.group', 'app.assets_group', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.groups_user', 'app.auth_account', 'app.photostream', 'app.user_edit');

	function startTest() {
		$this->SharedEdits =& new TestSharedEditsController();
		$this->SharedEdits->constructClasses();
	}

	function endTest() {
		unset($this->SharedEdits);
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