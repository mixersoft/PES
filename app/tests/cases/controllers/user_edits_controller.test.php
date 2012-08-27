<?php
/* UserEdits Test cases generated on: 2010-04-02 03:04:29 : 1270174229*/
App::import('Controller', 'UserEdits');

class TestUserEditsController extends UserEditsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class UserEditsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.user_edit', 'app.user', 'app.group', 'app.asset', 'app.provider_account', 'app.shared_edit', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.groups_user', 'app.auth_account', 'app.photostream');

	function startTest() {
		$this->UserEdits =& new TestUserEditsController();
		$this->UserEdits->constructClasses();
	}

	function endTest() {
		unset($this->UserEdits);
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