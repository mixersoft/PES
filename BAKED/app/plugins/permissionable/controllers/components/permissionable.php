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
				return;
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
					/*
					 * Member Groups for Permissionable::$group_ids
					 * 	get group ids for each Group Membership (i.e. not System group, isSystem==0),
					 * 	from join to Groups_Users table,
					 *  this query MUST check Group permissions
					 */
					$containsMembership = array(
						'fields' => 'id, title, isSystem',
	                    'conditions' => array('isSystem' => 0),
					);
					$userId = $user['User']['id'];
					$options = array(
						'fields' => array('id', 'username' ,'primary_group_id'),
	                    'conditions' => array('User.id' => $userId),
						'contain'=>array('Membership'=>$containsMembership)
					);
	$membershipSQL =
"SELECT `Membership`.`id`,  `GroupsUser`.`user_id`, `GroupsUser`.`role`, `GroupsUser`.`isActive`
  FROM `groups` AS `Membership`
  JOIN `groups_users` AS `GroupsUser` ON (`GroupsUser`.`group_id` = `Membership`.`id` AND `GroupsUser`.`user_id` = '{$userId}' 
  		AND `GroupsUser`.`isActive`=1 )
  INNER JOIN permissions AS `GroupPermission` ON (`GroupPermission`.`model` = 'Group' AND `GroupPermission`.`foreignId` = `Membership`.`id` AND ( 
  	1=0
/*  OR (`GroupPermission`.`perms` & 512 <> 0) -- NOTE: only include in groupIds if user is a member or owner, non-member public assets require Asset World=READ  */
    OR ((((`GroupPermission`.`perms` & 512 <> 0) OR (`GroupPermission`.`perms` & 64 <> 0)) AND (`GroupPermission`.`foreignId` = `GroupsUser`.`group_id` )))
    OR (((`GroupPermission`.`perms` & 8 <> 0) AND (`GroupPermission`.`foreignId` = `GroupsUser`.`group_id` AND `GroupsUser`.`role`='admin')))
/*  OR (((`GroupPermission`.`perms` & 1 <> 0) AND (`GroupPermission`.`oid` = '{$userId}'))) -- add owned groups separately  */
   ) )
  WHERE `Membership`.`isSystem` = 0;"; 
					$data = $User->query($membershipSQL);
//TODO: make sure groupIds are updated when permissions on Group is changed
					$membership_group_ids = (array)@Set::extract('/Membership/id', $data);
/*
 * add owned groups to membership_group_ids so we can have permissions on assets shared by other group members
 */					
	 $ownerSQL = "SELECT `Membership`.`id`
  FROM `groups` AS `Membership`
  INNER JOIN permissions AS `GroupPermission` ON (`GroupPermission`.`model` = 'Group' AND `GroupPermission`.`foreignId` = `Membership`.`id`) AND (`GroupPermission`.`perms` & 1 <> 0) AND (`GroupPermission`.`oid` = '{$userId}')
  WHERE `Membership`.`isSystem` = 0;"; 
					$data = $User->query($ownerSQL);
					$owner_group_ids = (array)@Set::extract('/Membership/id', $data);	

					$membership_group_ids = array_unique(array_merge($membership_group_ids, $owner_group_ids));
//debug($membership_group_ids);					
					if ($user['User']['primary_group_id']=='role-----0123-4567-89ab-------editor') array_push($membership_group_ids, $user['User']['primary_group_id']);
					// TODO: optimize. should we change 'none' to array(null), or even exclude condition from Permissionable join?
					if (empty($membership_group_ids)) $membership_group_ids = array('none'); // permissionable fails if empty(Permissionable::$group_ids) 
//					$test = Set::combine($data, '/Membership/id', '/Membership/title');
					Permissionable::setGroupIds($membership_group_ids);  // now set GroupIds to exclude role
					$controller->Session->write('Auth.Permissions.group_ids', Permissionable::$group_ids);
				}
			}
		}
	}
}
?>