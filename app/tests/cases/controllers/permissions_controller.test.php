<?php
/* Permissions Test cases generated on: 2010-03-30 05:03:01 : 1269923881*/
App::import('Controller', 'Permissions');

class TestPermissionsController extends PermissionsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class PermissionsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.permission');

	function startTest() {
		$this->Permissions =& new TestPermissionsController();
		$this->Permissions->constructClasses();
	}

	function endTest() {
		unset($this->Permissions);
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