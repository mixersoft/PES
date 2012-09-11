<?php
class TestShell extends Shell {
	var $uses = array('User');
	function main() {
		$this->out("*************** Hello World ***********");
		
		$options = array(
			'fields'=>array("User.id, User.username"),
		);
		$data = $this->User->find('first', $options);
		$this->out(print_r($data, true));
	}
}
?>