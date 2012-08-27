<?php
/* Permission Test cases generated on: 2010-03-30 05:03:55 : 1269923875*/
App::import('Model', 'Permission');

class PermissionTestCase extends CakeTestCase {
	var $fixtures = array('app.permission');

	function startTest() {
		$this->Permission =& ClassRegistry::init('Permission');
	}

	function endTest() {
		unset($this->Permission);
		ClassRegistry::flush();
	}

}
?>