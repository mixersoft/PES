<?php
/* Provider Test cases generated on: 2010-03-30 05:03:56 : 1269923876*/
App::import('Model', 'Provider');

class ProviderTestCase extends CakeTestCase {
	var $fixtures = array('app.provider');

	function startTest() {
		$this->Provider =& ClassRegistry::init('Provider');
	}

	function endTest() {
		unset($this->Provider);
		ClassRegistry::flush();
	}

}
?>