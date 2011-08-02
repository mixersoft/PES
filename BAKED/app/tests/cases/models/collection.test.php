<?php
/* Collection Test cases generated on: 2010-03-30 05:03:52 : 1269923872*/
App::import('Model', 'Collection');

class CollectionTestCase extends CakeTestCase {
	var $fixtures = array('app.collection', 'app.user', 'app.group', 'app.groups_user', 'app.asset', 'app.photostream', 'app.assets_collection', 'app.assets_group', 'app.collections_group', 'app.auth_account', 'app.user_edit');

	function startTest() {
		$this->Collection =& ClassRegistry::init('Collection');
	}

	function endTest() {
		unset($this->Collection);
		ClassRegistry::flush();
	}

}
?>