<?php
/* CollectionsGroups Test cases generated on: 2010-03-30 05:03:01 : 1269923881*/
App::import('Controller', 'CollectionsGroups');

class TestCollectionsGroupsController extends CollectionsGroupsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class CollectionsGroupsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.collections_group', 'app.collection', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.assets_collection', 'app.assets_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->CollectionsGroups =& new TestCollectionsGroupsController();
		$this->CollectionsGroups->constructClasses();
	}

	function endTest() {
		unset($this->CollectionsGroups);
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