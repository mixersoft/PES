<?php
App::import('Controller', 'Events');
class WeddingsController extends EventsController {
	var $name = 'Groups';
	var $viewPath = 'groups';
	var $uses = 'Group';

	var $titleName = 'Weddings';
	var $displayName = 'Wedding';
	
	function __construct() {
		parent::__construct();
	}
}
?>
