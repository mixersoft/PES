<?php

/**
 * PermissionableComponent
 *
 * Sets user info for PermissionableBehavior
 *
 * @package     permissionable
 * @subpackage  permissionable.controllers.components
 * @see         PermissionableBehavior
 * @uses		Component
 * @author      Joshua McNeese <jmcneese@gmail.com>
	 * 			Modified by Michael, Lin 2010-03-31
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, Curtis J. Beeson
 */
class PermissionableComponent extends Object {
	/**
	 * @author  Joshua McNeese <jmcneese@gmail.com>
	 * @param   object	$controller
	 * @return	void
	 */
	public function initialize(&$controller) {
		App::import('Lib', 'Permissionable.Permissionable');
		if (Permissionable::$initialized && Permissionable::$user_id){
			return;
		} 	
		
		/**
		 * if the root user or root group are other than '1',
		 * set them here here, with:
		 */
		$root = Configure::read('AAA.Permissionable');
		Permissionable::setRootUserId($root['root_user_id']);	
		Permissionable::setRootGroupId($root['root_group_id']);

		$user = $controller->Auth->user();
		if(!empty($user)) {	// logged in user
			if ($user['User']['id'] === Permissionable::$root_user_id){
				// check for Permissionable Root Override
				Permissionable::setGroupIds(array(Permissionable::$root_group_id));
				$auth['Permissions']['group_ids'] = Permissionable::$root_group_id;
				$auth['Permissions']['actions'] = array('*');
				$controller->Session->write('Auth.Permissions', $auth['Permissions']);
				$controller->Session->setFlash("Permissionable: root user");
				$ownerid = Session::read('Auth.User.acts_as_ownerid');
				if ($ownerid && $ownerid != Permissionable::getUserId()) {
					Permissionable::setUserId($ownerid);					
					Permissionable::setGroupOwnershipsMemberships($ownerid);  
					$controller->Session->write('Auth.Permissions.group_ids', Permissionable::$group_ids);
				}
			} else {
				Permissionable::setUserId($user['User']['id']);
				Permissionable::setGroupId($user['User']['primary_group_id']);  // primary group = role
				$auth['Permissions'] = $controller->Session->read('Auth.Permissions');
				$User = ClassRegistry::init('User');

				
				if (isset($auth['Permissions']['actions'])&& !empty($auth['Permissions']['actions'])){
					// ACL actions all set up for Auth->isAuthorized()
				} else {
					/*
					 * Get All System Roles for current user
					 * 	from join to Groups_Users table,
					 *  Group.title=/^__./, i.e. __User, __Editor, etc.
					 * 	THIS QUERY DOES NOT CHECK Groups via PERMISSIONS
					 */
					$containsMembership = array(
						'fields' => 'id, title, isSystem',
	                    'conditions' => array('isSystem' => 1),
					);
					$options = array(
						'fields' => array('id', 'username' ,'primary_group_id'),
	                    'conditions' => array('User.id' => $user['User']['id']),
						'contain'=>array('Membership'=>$containsMembership)
					);
					$data = $User->find('all', $options);
					$roles = (array)@Set::extract($data, '/Membership[title=/^__./]/id');
					
					
					/*
					 * Get all System Groups as Permitted Role Actions, save in Auth.Permissions.actions
					 * 	Get group_ids for all System Groups with READ access by user/role
					 * 	use to set 'Auth.Permissions', i.e. ACL permissions on controller/actions
					 * 	USES Permissionable, DOES NOT JOIN WITH Groups_Users TABLE
					 */
					// check permissions through "role", i.e. group_id = __user
					Permissionable::$group_ids = array_merge(array(Permissionable::$group_id), $roles );
					$options = array(
						'fields' => array('Membership.id', 'Membership.title', 'Membership.isSystem'),
						'recursive'=>-1,
						'conditions'=>array('isSystem'=>1)
					);
					$data = $User->Membership->find('all', $options);
					$systemGroups = array_unique(@Set::extract($data, '/Membership[title=/^[^__]/]/title'));
					$controller->Session->write('Auth.Permissions.actions', $systemGroups);
				}
				if (isset($auth['Permissions']['group_ids']) && $auth['Permissions']['group_ids'][0] !=='none' ){
					Permissionable::setGroupIds($auth['Permissions']['group_ids']);
				} else {
					Permissionable::setGroupOwnershipsMemberships($user);  
					$controller->Session->write('Auth.Permissions.group_ids', Permissionable::$group_ids);
				}
			}
		}
		Permissionable::$initialized = true;
	}
	

}
?>