<?php
App::import('Controller', 'Events');
class WeddingsController extends EventsController {
	var $name = 'Groups';
	var $viewPath = 'groups';
	var $uses = 'Group';
	var $controllerAlias = 'weddings';
	var $displayName = 'Wedding';
	
	function __construct() {
		parent::__construct();
	}
}
?>
