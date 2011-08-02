<?php
/* AuthAccounts Test cases generated on: 2010-03-30 05:03:01 : 1269923881*/
App::import('Controller', 'AuthAccounts');

class TestAuthAccountsController extends AuthAccountsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class AuthAccountsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.auth_account', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.user_edit');

	function startTest() {
		$this->AuthAccounts =& new TestAuthAccountsController();
		$this->AuthAccounts->constructClasses();
	}

	function endTest() {
		unset($this->AuthAccounts);
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