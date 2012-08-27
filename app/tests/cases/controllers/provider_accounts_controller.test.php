<?php
/* ProviderAccounts Test cases generated on: 2010-04-02 03:04:55 : 1270174075*/
App::import('Controller', 'ProviderAccounts');

class TestProviderAccountsController extends ProviderAccountsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class ProviderAccountsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.provider_account', 'app.user', 'app.group', 'app.asset', 'app.shared_edit', 'app.user_edit', 'app.collection', 'app.assets_collection', 'app.collections_group', 'app.assets_group', 'app.groups_user', 'app.auth_account', 'app.photostream');

	function startTest() {
		$this->ProviderAccounts =& new TestProviderAccountsController();
		$this->ProviderAccounts->constructClasses();
	}

	function endTest() {
		unset($this->ProviderAccounts);
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