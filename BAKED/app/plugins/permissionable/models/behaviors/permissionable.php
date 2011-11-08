<?php

/**
 * PermissionableBehavior
 *
 * An implementation of *NIX-like bitwise permissions for row-level operations.
 *
 * @package     permissionable
 * @subpackage  permissionable.models.behaviors
 * @author      Joshua McNeese <jmcneese@gmail.com>
 */
final class PermissionableBehavior extends ModelBehavior {

	/**
	 * Permission bits, don't touch!
	 */
	const   OWNER_READ		= 1,		MEMBER_READ		= 64,
			OWNER_WRITE		= 2,		MEMBER_WRITE	= 128,
			OWNER_DELETE	= 4,		MEMBER_DELETE	= 256,

			GROUP_READ		= 8,		OTHER_READ		= 512,
			GROUP_WRITE		= 16,		OTHER_WRITE		= 1024,
			GROUP_DELETE	= 32,		OTHER_DELETE	= 2048;			

/**
 * INSTRUCTIONS FOR USAGE
 * 
 * USE Permissionable::setGroupIds() in component TO control access to Assets/Collections via membership and membership role='admin'
 * 
 * ASSET PERM SETTINGS
 * 
 * perm = 519 (rwd/---/---/r--) - public listing,
 * perm = 71 (rwd/---/r--/--) 	- members only 
 * perm = 7 (rwd/---/---/--) 	- private 
 * 
 * 
 * GROUP PERMS SETTINGS
 * perm = 567 (rwd/-wd/---/r--) - public listing, hide content, do NOT add to groupIds, content visible only by upgrading Asset perms to public
 * perm = 631 (rwd/-wd/r--/r--) - public listing, content is member only, for members, add to groupIds
 * perm = 119 (rwd/-wd/r--/--) 	- listing AND content is member only, for members, add to groupIds 
 * perm = 63 (rwd/rwd/---/--) 	- listing AND content is owner/admin only, for admins, add to groupIds 
 * 
 * 
 */			
			
			
	/**
	 * configured actions
	 *
	 * @var array
	 */
	private $_actions = array(
		'read',
		'write',
		'delete'
	);

	/**
	 * settings defaults
	 *
	 * @var array
	 */
	private $_defaults = array(
		'defaultBits'	=> 519, //rwd/r-/r-/r--
		'userModel'		=> 'User',
		'groupModel'	=> 'Group'
	);

	/**
	 * disable Permissionable
	 *
	 * @var boolean
	 */
	private $_disabled = false;
	private $_disableOtherPerm = false;
	
    /**
     * from branch 1.3
     * bind the permission model to the model in question
     *
     * @param  object	$Model
     * @return boolean
     */
    private function _bind(&$Model, $conditions = array()) {

        $this->_unbind($Model);

        $alias = $this->getPermissionAlias($Model);

        return $Model->bindModel(array(
            'hasOne' => array(
                $alias => array(
                    'className'		=> 'Permissionable.Permission',
//                	'table'			=> 'permissions',
//					'alias'			=> $alias,
                    'foreignKey'	=> 'foreignId',	// or false
                    'dependent'		=> true,
                    'type'			=> 'INNER',
                    'conditions'	=> array_merge($conditions, array(
                        "{$alias}.model" => $Model->name,	
                		"{$alias}.foreignId = {$Model->alias}.{$Model->primaryKey}",
                    ))
                )
            )
        ), false);

    }	

	/**
	 * Convenience method for getting the permission bit integer for an action
	 *
	 * @param   mixed    $action
	 * @return  integer
	 */
	private function _getPermissionBit($action = null) {

		$action = strtoupper($action);

		return (empty($action) || !defined("self::$action"))
			? 0
			: constant("self::$action");
		
	}
	
	

	/**
	 * helper to build the query for permission checks
	 *
	 * @param  object  $Model
	 * @param  string  $action
	 * @return array
	 */
	private function _getPermissionQuery(&$Model, $action = 'read', $alias = null) {

		extract($this->settings[$Model->alias]);

		if(empty($alias)) {

			$alias = $this->getPermissionAlias($Model);
			
		}
		
		$action	= strtoupper($action);
		$gids	= Permissionable::getGroupIds();
		$oid	= Permissionable::getUserId();
		if ($this->_disableOtherPerm) {
			$query	= array();
			$_disableOtherPerm = false;	// disable once
		} else {
			$query	= array(
				// first check if "other" has the requested action
				"$alias.perms & {$this->_getPermissionBit('OTHER_' . $action)} <> 0",
			);
		}

		if(!empty($gids)) {
			if($Model->name == $groupModel) {
				$query[] = array(
					"$alias.perms & {$this->_getPermissionBit('MEMBER_' . $action)} <> 0",
					"$alias.foreignId" => $gids
				);

			}
			// watch out, sometimes we unbind habtm Group to fetch paginated Group data manually. but we still need member bits here 
			elseif(isset($Model->hasAndBelongsToMany[$groupModel]) || in_array($Model->name, array('Asset', 'Collection', 'User'))) {
				// add condition to check permissions through group memberships
				if (is_array($gids)) {
					// array_push($gids, 'member---0123-4567-89ab-000000000001'); // global add public group to gids. 
					$subSelect = "SELECT DISTINCT `_ag`.`asset_id` FROM `assets_groups` AS `_ag` WHERE `_ag`.`group_id` IN ('".implode("','",$gids)."')";
					$query[] = array(
						"$alias.perms & {$this->_getPermissionBit('MEMBER_' . $action)} <> 0",
						$Model->getDataSource()->expression("`{$alias}`.`foreignId` IN ({$subSelect})")		
					);	
				}
			}
		}

		if(!empty($gids)) {

			/**
			 * otherwise, if the user is in a group that owns this row, and the
			 * "group" action is allowed
			 */
			$query[] = array(
				"$alias.perms & {$this->_getPermissionBit('GROUP_' . $action)} <> 0",
				"$alias.gid" => $gids
			);

		}

		if(!empty($oid)) {

			/**
			 * otherwise, if the user is the row owner and the "owner" action is allowed
			 */
			$query[] = array(
				"$alias.perms & {$this->_getPermissionBit('OWNER_' . $action)} <> 0",
				"$alias.oid" => $oid
			);

		}
		return $query;
		
	}

    /**
     * from branch 1.3
     * unbind the permission model from the model in question
     *
     * @param  object	$Model
     * @return boolean
     */
    private function _unbind(&$Model) {

        return $Model->unbindModel(array(
            'hasOne' => array(
                $this->getPermissionAlias($Model)
            )
        ), false);

    }

    /**
     * settings
     *
     * @var     array
     */
    public $settings = array();	
	
	/**
	 * afterSave model callback
	 *
	 * cleanup any related permission rows
	 *
	 * @param  object  $Model
	 * @param  boolean $created
	 * @return boolean
	 */
	public function afterSave(&$Model, $created) {
		if ($this->_disabled) {

			return true;

		}
		extract($this->settings[$Model->alias]);

		$user_id	= Permissionable::getUserId();
		$group_id	= Permissionable::getGroupId();
		$alias		= $this->getPermissionAlias($Model);
		$data		= array(
			'model'     => $Model->alias,
			'foreignId'=> $Model->id,
			'oid'       => $user_id,
			'gid'		=> $group_id,
			'perms'		=> $this->settings[$Model->alias]['defaultBits']
		);

		$requested = Set::extract('/Permission/.', $Model->data);
		if (!empty($requested)) {

			$data = Set::merge($data, $requested[0]);

		}
		
		if($Model->name == $userModel) {

			$assoc = $Model->hasAndBelongsToMany[$groupModel];
			$data = array_merge($data, array(
				'oid' => $Model->id,
				'gid' => $Model->data[$userModel][$assoc['associationForeignKey']]
			));

		}

		if (isset($data['id'])) {

			unset($data['id']);

		}
		
		
//		if ($created) {
//
//			$this->Permission->create();
//
//		} else {
//
//			// go get existing permission for this row
//			$previous = $this->getPermission($Model);
//
//			if (!empty($previous)) {
//
//				$this->Permission->id = $previous['id'];
//
//			}
//
//		}
//		
//		return $this->Permission->save($data);
		
		/*********************************
         * from branch 1.3: using _bind()
         */
		$this->_bind($Model);
		if ($created) {
			$Model->{$alias}->create();
		} else {
			// go get existing permission for this row
			$previous = $this->getPermission($Model);
			if (!empty($previous)) {
				$Model->{$alias}->id = $previous['id'];		// getPermission() uses _bind()
			}
		}
		return $Model->{$alias}->save($data);
		
	}

	/**
	 * beforeDelete model callback
	 *
	 * direct the callback to determine if user has delete permission on the row
	 *
	 * @param  object $Model
	 * @return boolean
	 */
	public function beforeDelete(&$Model) {

		if ($this->_disabled) {

			return true;

		}
		$ret = $this->hasPermission($Model, 'delete');
		/*
		 * NOTE: [conditions][0] on _bind() prevents deleting Permission
		 * */
// debug($this->getPermissionAlias($Model));		
		if ($ret) {
			unset($Model->hasOne[$this->getPermissionAlias($Model)]['conditions'][0]);
		}
// debug($Model->hasOne[$this->getPermissionAlias($Model)]);		
		return true;
	}
	
	
	/**
	 * beforeFind model callback
	 *
	 * if we are checking permissions, then the appropriate modifications are
	 * made to the original query to filter out denied rows
	 *
	 * @param  object  $Model
	 * @param  array   $queryData
	 * @return mixed
	 */
	public function beforeFind(&$Model, $queryData) {
		if (
			$this->_disabled ||
			(
				isset($queryData['permissionable']) &&
				$queryData['permissionable'] == false
			) || (
				isset($queryData['conditions']['permissionable']) &&
				$queryData['conditions']['permissionable'] == false
			)
		) {

			@(unset) $queryData['permissionable'];
			@(unset) $queryData['conditions']['permissionable'];

			return $queryData;

		} elseif(Permissionable::isRoot()) {

			/**
			 * if we are skipping checks or if the user is in the "root"
			 * group, just allow the query to continue unmodified
			 */
			return true;

		}

		extract($this->settings[$Model->alias]);		

		$alias = $this->getPermissionAlias($Model);
		
        if (empty($queryData['fields'])) {

            $queryData['fields'] = array("{$Model->alias}.*");

        }

        $queryData['fields'] = Set::merge(
                $queryData['fields'],
                array(
                    "{$alias}.*"
                )
        );

        
        
        /*********************************
         * from branch group bits 
         */
         
//		// use manual join instead of Model hasOne Permission
//		$queryData['joins'][] = array(
//			'table'			=> 'permissions',
//			'alias'			=> $alias,
//			'foreignKey'	=> false,
//			'type'			=> 'INNER',
//			'conditions'	=> array(
//				"{$alias}.model" => "{$Model->name}",
//				"{$alias}.foreignId = {$Model->alias}.{$Model->primaryKey}",
//				'or' => $this->_getPermissionQuery($Model)
//			)
//		);
////debug($queryData);
//		return $queryData;
		
		
		/*********************************
         * from branch 1.3: using _bind()
         */    
        $this->_bind($Model, array(
            'or' => $this->_getPermissionQuery($Model)
        ));
        
        // recursive = -1 will NOT join hasOne Permission
        // recursive = 1 will join all other tables. maybe use Model->contain() to remove habtm?
        $queryData['recursive']=0;	// required to join with Model hasOne {$alias}Permission
        return $queryData;
	}

	/**
	 * beforeSave model callback
	 *
	 * @param  object $Model
	 * @return boolean
	 */
	public function beforeSave(&$Model) {

		if ($this->_disabled) {

			return true;

		}
		
		$user_id	= Permissionable::getUserId();
		$group_id	= Permissionable::getGroupId();
		$group_ids	= Permissionable::getGroupIds();

		// if somehow we don't know who the logged-in user is, don't save!
		if (empty($user_id) || empty($group_id) || empty($group_ids)) {

			return false;

		}
		/*
		 * override permissions check on CREATE when $Model->id is set;
		 */
		if (isset($Model->isCREATE) && $Model->isCREATE ) return true;
	
		return ( !empty($Model->id) )
			? $this->hasPermission($Model, 'write')
			: true;
		
	}

	/**
	 * get the permissions for the record
	 *
	 * @param  object  $Model
	 * @param  mixed   $id
	 * @return mixed
	 */
	public function getPermission(&$Model, $foreignId = null) {

		$foreignId = (empty($foreignId))
			? $Model->id
			: $foreignId;

		if (empty($foreignId)) {

			return false;

		}
		
//		$permission = $this->Permission->find('first', array(
//			'contain' => array(),
//			'recursive' => -1,
//			'conditions' => array(
//				"Permission.model"		=> $Model->name,
//				"Permission.foreignId"	=> $Model->id   // $foreignId id always null
//			)
//		));
//		return !empty($permission) ? $permission['Permission'] : null;
		
		
		/*********************************
         * from branch 1.3: using _bind()
         */    
		$alias = $this->getPermissionAlias($Model);
		$this->_bind($Model);
		$permission = $Model->{$alias}->find('first', array(
            'conditions' => array(
                "{$alias}.model"		=> $Model->name,
                "{$alias}.foreignId"	=> $foreignId
            )
        ));		
        return !empty($permission) ? $permission[$alias] : null;
		
	}

	/**
	 * get alias for the Permissionable model
	 *
	 * @param  object  $Model
	 * @return mixed
	 */
	public function getPermissionAlias(&$Model) {

		return "{$Model->alias}Permission";
		
	}

	/**
	 * Determine whether or not a user has a certain permission on a row
	 *
	 * @param  object  $Model
	 * @param  string  $action
	 * @param  mixed   $id
	 * @return boolean
	 */
	public function hasPermission(&$Model, $action = 'read', $id = null) {

		if ($this->_disabled) {

			return true;

		}

		$user_id	= Permissionable::getUserId();
		$group_ids	= Permissionable::getGroupIds();
		$id			= (empty($id)) ? $Model->id : $id;

		// if somehow we don't know who the logged-in user is, don't save!
		if (!in_array($action, $this->_actions) || empty($id) || empty($user_id) || empty($group_ids)) {

			return false;

		} elseif(Permissionable::isRoot()) {

			return true;

		}

//		// do a quick count on the row to see if that permission exists
//		$perm = $this->Permission->find('count', array(
//			'conditions' => array(
//				"Permission.model"		=> $Model->name,
//				"Permission.foreignId"	=> $id,
//				'or'					=> $this->_getPermissionQuery($Model, $action, 'Permission')
//			)
//		));
//		
//		return !empty($perm) ? $perm : false;
		
		/*********************************
         * from branch 1.3: using _bind()
         */           
		$this->_bind($Model);
        // do a quick count on the row to see if that permission exists
        $alias	= $this->getPermissionAlias($Model);
        $perm	= $Model->{$alias}->find('count', array(
            'conditions' => array(
                "{$alias}.model"		=> $Model->name,
                "{$alias}.foreignId"	=> $id,
                'or'					=> $this->_getPermissionQuery($Model, $action)
            )
        ));		

		return !empty($perm) ? $perm : false;
		
	}

	/**
	 * disable Permissionable for the model
	 *
	 * @param  object   $Model
	 * @param  boolean  $disable
	 * @param  boolean  $disable_otherPerm - disables check on world/other bit for one query
	 * @return null
	 */
	public function disablePermissionable(&$Model, $disable = true, $disable_otherPerm = false) {
		$this->_disabled = $disable;
		$this->_disableOtherPerm = $disable_otherPerm;
	}

	/**
	 * getter to determine if Permissionable is enabled
	 *
	 * @return boolean
	 */
	public function isPermissionableDisabled() {

		return $this->_disabled;
		
	}

	/**
	 * Behavior configuration
	 *
	 * @param   object  $Model
	 * @param   array   $config
	 * @return  void
	 */
	public function setup(&$Model, $config = array()) {

		$config = (is_array($config) &&!empty($config))
			? Set::merge($this->_defaults, $config)
			: $this->_defaults;

		$this->settings[$Model->alias] = $config;

		// branch 1.3 uses _bind() to add Model hasOne $this->getPermissionAlias($Model)
//		$this->Permission = ClassRegistry::init('Permissionable.Permission');
		
	}

}

?>