<?php
/* all Test cases generated on: 2010-03-30 03:03:34 : 1269917794*/
App::import('t_e_s_t', 'all');

class allt_e_s_tTestCase extends CakeTestCase {
	function startTest() {
		$this->all =& new allt_e_s_t();
	}

	function endTest() {
		unset($this->all);
		ClassRegistry::flush();
	}

}
?>