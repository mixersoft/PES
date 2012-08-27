<?php

/**
 * Permissionable
 *
 * Provides static class app-wide for Permissionable info getting/setting
 *
 * @package     permissionable
 * @subpackage  permissionable.libs
 * @author      Joshua McNeese <jmcneese@gmail.com>
 */
final class Permissionable {

	/**
	 * @var mixed
	 */
	public static $user_id = 0;

	/**
	 * @var mixed
	 */
	public static $group_id	= 0;

	/**
	 * @var mixed
	 */
	public static $group_ids = array();

	/**
	 * @var mixed
	 */
	public static $root_user_id = 1;

	/**
	 * @var mixed
	 */
	public static $root_group_id = 1;

	/**
	 * @return void
	 */
	private function  __construct() {}

	/**
	 * @return mixed
	 */
	public static function getUserId() {

		return Permissionable::$user_id;

	}

	/**
	 * @return mixed
	 */
	public static function getGroupId() {

		return Permissionable::$group_id;

	}

	/**
	 * @return mixed
	 */
	public static function getGroupIds() {

		return Permissionable::$group_ids;

	}

	/**
	 * @param	mixed $user_id
	 * @return	mixed
	 */
	public static function setUserId($user_id = null) {

		Permissionable::$user_id = $user_id;

	}

	/**
	 * @param	mixed $group_id
	 * @return	mixed
	 */
	public static function setGroupId($group_id = null) {

		Permissionable::$group_id = $group_id;

	}

	/**
	 * @param	mixed $group_ids
	 * @return	mixed
	 */
	public static function setGroupIds($group_ids = null) {

		Permissionable::$group_ids = $group_ids;

	}

	/**
	 * @return	mixed
	 */
	public static function getRootUserId() {

		return Permissionable::$root_user_id;

	}

	/**
	 * @return	mixed
	 */
	public static function getRootGroupId() {

		return Permissionable::$root_group_id;

	}

	/**
	 * @param	mixed $user_id
	 * @return	mixed
	 */
	public static function setRootUserId($user_id) {

		Permissionable::$root_user_id = $user_id;

	}

	/**
	 * @param	mixed $group_id
	 * @return	mixed
	 */
	public static function setRootGroupId($group_id) {

		Permissionable::$root_group_id = $group_id;

	}

	/**
	 * helper to determine if the user
	 * is the root user or member of the root group
	 *
	 * @return boolean
	 */
	public static function isRoot() {
		return (
		Permissionable::$user_id == Permissionable::$root_user_id ||
			in_array(Permissionable::$root_group_id, Permissionable::$group_ids)
		);

	}
	
	public static $initialized = false;
	
	/*
	 * added for Snaphappi
	 * set Permissionable::$group_ids to 'user' owned and membership groups, excludes isSystem=1  
	 */ 
	public static function setGroupOwnershipsMemberships ($user) {
		$User = ClassRegistry::init('User');
		if (is_string($user)) {
			$options = array(
				'fields' => array('id', 'username' ,'primary_group_id'),
                'conditions' => array('User.id' => $user),
                'recursive' => -1,
			);
			$user = $User->find('first', $options);
		}
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
		Permissionable::setGroupIds($membership_group_ids);
		return $membership_group_ids;
	}

}

?>
