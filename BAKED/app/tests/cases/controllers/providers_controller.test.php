<?php
/* Providers Test cases generated on: 2010-03-30 05:03:02 : 1269923882*/
App::import('Controller', 'Providers');

class TestProvidersController extends ProvidersController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

class ProvidersControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.provider');

	function startTest() {
		$this->Providers =& new TestProvidersController();
		$this->Providers->constructClasses();
	}

	function endTest() {
		unset($this->Providers);
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