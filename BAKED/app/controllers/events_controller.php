<?php
App::import('Controller', 'Groups');
class EventsController extends GroupsController {
	var $name = 'Groups';		// paginate/context processing unchanged if we use Groups here, vs. Events
	var $viewPath = 'groups';
	var $uses = 'Group';
	
	var $titleName = 'Events';
	var $displayName = 'Event';		// section header
	
	function __construct() {
		parent::__construct();
	}
}
?>
