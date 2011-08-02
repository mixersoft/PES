<?php

/*
 * utility class to load Group, ACL, and test data
 * - to execute, see run /groups/load as superuser, snappi
 * 
 */
class Data {
	
	function updateCaptions() {
		$Asset = ClassRegistry::init('Asset');
		$Asset->disablePermissionable();
		$Asset->recursive=-1;
		$data = $Asset->find('all');
		$data = array_slice($data, 0 ,4);
		$update = Set::combine($data, '/Asset/id', '/Asset/json_src');

		$basepath = Configure::read('path.original.basepath');
		foreach ($update as $key=>$json) {
			$src = json_decode($json);
			$update[$key] = array($update[$key],'caption'=>pathinfo($basepath.$src->orig, PATHINFO_FILENAME));
		}
//		debug($update); 		
		foreach ($data as & $row) {
			$row['Asset']['caption'] = $update[$row['Asset']['id']]['caption'];
			$Asset->id = $row['Asset']['id'];
//			$Asset->saveField('caption', $row['Asset']['caption']);
		}
	}
	
	
	function load_ACLs () {
		load_SystemGroups();
		load_AclGroups();
	}
	
	/**
	 * update ACLs to controller/actions via 'Auth.Permissions.actions'
	 * This method can be rerun without fk side effects, uuids are absolute
	 * @return unknown_type
	 */
	function update_ACLs() {
		Data::load_AclGroups();
	}

	function load_TestData(){
		load_testUsers();
		load_testMemberGroups();
		load_testMemberships();
	}
	

	/**
	 * load system Roles into DB
	 * This method can be rerun without fk side effects, uuids are absolute
	 */
	protected function load_SystemGroups(){
		$Group = ClassRegistry::init('Group');
		$Group->query('delete from Permissions where model="Group";');
		$Group->query('delete from Groups;');


		/* permissions for 'System' Groups:
		 * 		oid = 1 (snappi)
		 * 		gids:
		 * 			'root', '4bb1a76c-4978-494b-bbb0-0d80f67883f5'
		 * 			'admin', '4bb1a78e-f118-4efd-8554-0d80f67883f5'
		 * 			'manager', '4bb1a7b9-1590-4e9e-b9fb-0d80f67883f5'
		 * 			'editor', '4bb1a7d4-2d80-458a-b668-0d80f67883f5'
		 * 			'user', '4bb1a7e7-75dc-4286-8bf4-0d80f67883f5'
		 * 			'guest', '4bb1a808-2dd8-4090-a0ed-0d80f67883f5'
		 *
		 * 		read = rwd/r--/---/---   	= 1+2+4+ 8 = 15
		 * 		update = rwd/rw-/---/---   	= 1+2+4+ 8+16 = 31
		 * 		delete = rwd/rwd/---/---   	= 1+2+4+ 8+16+32 = 63
		 */


		$system_root = Permissionable::getRootGroupId(); // '12345678-1111-0000-0000-123456789abc';  // snappi user_id
		$root_group_id = 'role-----0123-4567-89ab-cdef----root';

		$role_groups[]=array('id'=>$root_group_id,'owner_id'=> $system_root, 'isSystem'=>1, 'title'=>'__root', 'description'=>'superuser group. no Permissions validation');
		$role_groups[]=array('id'=>'role-----0123-4567-89ab--------admin', 'owner_id'=> $system_root, 'isSystem'=>1, 'title'=>'__admin',  'description'=>'sysadmin group');
		$role_groups[]=array('id'=>'role-----0123-4567-89ab------manager', 'owner_id'=> $system_root, 'isSystem'=>1, 'title'=>'__manager',  'description'=>'backoffice group for supervisors, team leaders, and QA managers');
		$role_groups[]=array('id'=>'role-----0123-4567-89ab-------editor', 'owner_id'=> $system_root, 'isSystem'=>1, 'title'=>'__editor',  'description'=>'backoffice group for staff editors');
		$role_groups[]=array('id'=>'role-----0123-4567-89ab---------user', 'owner_id'=> $system_root, 'isSystem'=>1, 'title'=>'__user',  'description'=>'signed in user');
		$role_groups[]=array('id'=>'role-----0123-4567-89ab--------guest', 'owner_id'=> $system_root, 'isSystem'=>1, 'title'=>'__guest',  'description'=>'guest user, credentials provided by session/cookie');
		$role_groups[]=array('id'=>'role-----0123-4567-89ab------visitor', 'owner_id'=> $system_root, 'isSystem'=>1, 'title'=>'__visitor',  'description'=>'no cookie');

		Permissionable::setUserId(Permissionable::getRootUserId());	// role owner is root user
		Permissionable::setGroupId($root_group_id);
		Permissionable::setGroupIds(array($root_group_id));
		$i = 0;
		foreach ($role_groups as $role_group) {
			$data['Permission'] = array(
				'perms' => 15				// snappi=rwd, group=r--
			);
			$data['Group'] = $role_group;
			$Group->isCREATE = true;		// disable beforeSave Permissionable check on CREATE
			$Group->create();
			$ret = $Group->save($data, false);
			if (!$ret) {
				debug($ret);
				break;
			}
		}

	}

	/**
	 * load_AclGroups - load ACLs to controller/actions via 'Auth.Permissions.actions'
	 * This method can be rerun without side effects.
	 * @return unknown_type
	 */
	protected function load_AclGroups() {
		$Group = ClassRegistry::init('Group');
		$Group->query('delete from Permissions where model="Group" and foreignId like "acl%";');
		$Group->query('delete from Groups where id like "acl%";');

		Permissionable::setUserId(Permissionable::getRootUserId());	// ACL owner is root user
		// permission Actions setup
		$r = 15;
		$rw =31;
		$rwd = 63;
		// admin ACLs
		$acl_groups[]=array(  'isSystem'=>1, 'title'=>'*', 'role'=>'__admin', 'perm'=>$rw);

		// supervisor/manager ACLs
		$acl_groups[]=array(  'isSystem'=>1, 'title'=>'users:*', 'role'=>'__manager', 'perm'=>$r);

		// editor ACLs
		$acl_groups[]=array(  'isSystem'=>1, 'title'=>'users:*', 'role'=>'__editor', 'perm'=>$r);

		// user ACLs
		/*
		 * NOTE: strip '_' from controller names, but NOT action names
		 */
		$role = '__user';
		$acl_groups[]=array(  'title'=>'*:home', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'*:photos', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'*:groups', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'*:members', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'*:trends', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'*:favorite', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'*:photostreams', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'my:settings', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'my:edit', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'my:delete', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:create', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:delete', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:invite', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:join', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:contribute', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:contributephoto', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:settings', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:edit', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:tag', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:settings', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:edit', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:unshare', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:ungroup', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:setprop', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:delete', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:getcc', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:set_as_photo', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:set_as_group_cover', 'role'=>$role);
		
		$acl_groups[]=array(  'title'=>'provideraccounts:view', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'provideraccounts:home', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'provideraccounts:folders', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'provideraccounts:import', 'role'=>$role);		
		$acl_groups[]=array(  'title'=>'provideraccounts:add', 'role'=>$role);		
		$acl_groups[]=array(  'title'=>'tags:add', 'role'=>$role);	

		// guest ACLs
		$role = '__guest';
		$acl_groups[]=array(  'title'=>'groups:invite', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'groups:join', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'users:home', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'users:photos', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'users:trends', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'users:groups', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'users:photostreams', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:setprop', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'assets:getcc', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'tags:add', 'role'=>$role);
				
		$acl_groups[]=array(  'title'=>'provideraccounts:add', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'provideraccounts:home', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'provideraccounts:folders', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'provideraccounts:import', 'role'=>$role);
		$acl_groups[]=array(  'title'=>'users:guest_only', 'role'=>$role);
		
		// visitor ACLs
		$acl_groups[]=array(  'isSystem'=>1, 'title'=>'assets:view', 'role'=>'__visitor', 'perm'=>$r);
		$id_prefix =  'acl------0123-4567-89ab-';
		$i = 0;
		$roles = $Group->find('all',array('conditions'=>"Group.id like 'role%'", 'fields'=>"id, title",'recursive'=>-1) );
		foreach ($acl_groups as $acl) {
			$data['Permission']['perms'] = $r;				// snappi=rwd, group=rw-
			$data['Group'] = array_merge(array('isSystem'=>1, 'owner_id'=> Permissionable::getRootUserId()), $acl);
			$data['Group']['id'] = $id_prefix.str_pad(++$i, 12, "0", STR_PAD_LEFT);
			$Group->isCREATE = true;
			//		debug($roles);
			$role_id = array_shift(Set::extract('/Group[title='.$acl['role'].']/id', $roles));
			$data['Group']['gid'] = $role_id;
			Permissionable::setGroupId($role_id); 			// acl created as in admin group
			Permissionable::setGroupIds(array(Permissionable::$group_id));
			$Group->create();
			$ret = $Group->save($data, false);
			if (!$ret) {
				debug($data);
				break;
			}
			//		$updatedId = __update_group_id($Group->id, ++$i,'permission');
		}


	}

	public function load_testMemberGroups(){
		$Group = ClassRegistry::init('Group');
//		$Group->query('delete from Permissions where model="Group" and foreignId like "member%";');
//		$Group->query('delete from Groups where id like "member%";');
		
		// member group setup
		$ownerPerm = 7;  // rwd/---/---/--- = 7
		$memberPerm = 223; // rwd/rw-/rw-/--- = 223
		$publicPerm = 1759;  // rwd/rw-/rw-/rw-  = 1759
		$venice = '12345678-1111-0000-0000-venice------';
		$paris = '12345678-1111-0000-0000-paris-------';
		$newyork = '12345678-1111-0000-0000-newyork-----';
		$sardinia = '12345678-1111-0000-0000-sardinia----';
		$member_groups[]=array('isSystem'=>0, 'owner'=>Permissionable::getRootUserId(), 'title'=>'public group', 'perm'=>$publicPerm);
		$member_groups[]=array('isSystem'=>0, 'owner'=>$venice, 'title'=>"Italy", 'perm'=>$memberPerm);
		$member_groups[]=array('isSystem'=>0, 'owner'=>$paris, 'title'=>"Europe", 'perm'=>$memberPerm);
		$member_groups[]=array('isSystem'=>0, 'owner'=>$sardinia, 'title'=>"Island", 'perm'=>$memberPerm);
		$member_groups[]=array('isSystem'=>0, 'owner'=>$venice, 'title'=>"Venice private group", 'perm'=>$ownerPerm);
		$i = 0;
		$id_prefix =  'member---0123-4567-89ab-';
		foreach ($member_groups as $group) {
			$uuid = $id_prefix.str_pad(++$i, 12, "0", STR_PAD_LEFT);
			$data['Permission']['perms'] = $group['perm'];				// snappi=rwd, group=rw-
			$data['Group'] = $group;
			$Group->isCREATE = true;		// disable beforeSave Permissionable check on CREATE
			$data['Group']['id'] = $uuid;
			$data['Group']['owner_id']=$group['owner'];								// owner, deprecate
			Permissionable::setUserId($group['owner']);
			Permissionable::setGroupId($uuid);						// member groups MUST have gid = foreignId
			Permissionable::setGroupIds(array(Permissionable::$group_id)); 			// permission.gid == group.id
			$Group->create();
sleep(1);			
			$ret = $Group->save($data, false);
			if (!$ret) {
				debug($data);
				break;
			}
		}
	}


	protected function load_testUsers(){
		$User = ClassRegistry::init('User');
		$insert = "
INSERT INTO `users` (`id`, `username`, `password`, `email`, `active`, `primary_group_id`, `privacy`, `lastVisit`, `modified`, `created`) VALUES
('12345678-1111-0000-0000-123456789abc', 'snappi', '0247a37cfc1e1f3763cfa08a2d94614c85851d44', NULL, 1, 'role-----0123-4567-89ab-cdef----root', 0, '2010-03-30 17:36:08', '2010-03-30 08:38:46', '2010-03-30 08:38:46'),
('12345678-1111-0000-0000-paris-------', 'paris', '6395578cdac2db64eb62e05fd05fa5782e6f9e6b', '', 1, 'role-----0123-4567-89ab---------user', NULL, '2010-03-31 13:11:00', '2010-04-01 10:45:30', '2010-03-31 01:10:50'),
('12345678-1111-0000-0000-newyork-----', 'newyork', '6395578cdac2db64eb62e05fd05fa5782e6f9e6b', '', 1, 'role-----0123-4567-89ab---------user', NULL, '2010-03-31 13:11:00', '2010-04-01 10:45:30', '2010-03-31 01:10:50'),
('12345678-1111-0000-0000-venice------', 'venice', '6395578cdac2db64eb62e05fd05fa5782e6f9e6b', '', 1, 'role-----0123-4567-89ab---------user', NULL, '2010-03-31 13:11:00', '2010-04-01 10:45:30', '2010-03-31 01:10:50'),
('12345678-1111-0000-0000-sardinia----', 'sardinia', '6395578cdac2db64eb62e05fd05fa5782e6f9e6b', '', 1, 'role-----0123-4567-89ab---------user', NULL, '2010-03-31 13:11:00', '2010-04-01 10:45:30', '2010-03-31 01:10:50'),
('12345678-1111-0000-0000-sfbay-------', 'sfbay', '6395578cdac2db64eb62e05fd05fa5782e6f9e6b', '', 1, 'role-----0123-4567-89ab---------user', NULL, '2010-03-31 13:11:00', '2010-04-01 10:45:30', '2010-03-31 01:10:50'),
('12345678-1111-0000-0000-editor------', 'editor', '6395578cdac2db64eb62e05fd05fa5782e6f9e6b', '', 1, 'role-----0123-4567-89ab-------editor', NULL, '2010-03-31 13:11:00', '2010-04-01 10:45:30', '2010-03-31 01:10:50');
";
		$User->query($insert);
	}


	protected function load_testMemberships(){
		$User = ClassRegistry::init('User');
		$insert = "
INSERT INTO `groups_users` (`id`, `owner_id`, `group_id`, `isApproved`, `role`, `isActive`, `suspendUntil`, `lastVisit`) VALUES
('4bb46b3a-a674-44ad-aa01-0ee4f67883f5', '12345678-1111-0000-0000-venice------', 'member---0123-4567-89ab-000000000002', 1, 'member', 0, NULL, NULL),
('4bb46b3a-a674-44ad-aa02-0ee4f67883f5', '12345678-1111-0000-0000-sardinia----', 'member---0123-4567-89ab-000000000002', 1, 'member', 0, NULL, NULL),
('4bb46b3a-a674-44ad-bb01-0ee4f67883f5', '12345678-1111-0000-0000-venice------', 'member---0123-4567-89ab-000000000003', 1, 'member', 0, NULL, NULL),
('4bb46b3a-a674-44ad-bb02-0ee4f67883f5', '12345678-1111-0000-0000-sardinia----', 'member---0123-4567-89ab-000000000003', 1, 'member', 0, NULL, NULL),
('4bb46b3a-d3c4-4dda-bb03-0ee4f67883f5', '12345678-1111-0000-0000-paris-------', 'member---0123-4567-89ab-000000000003', 1, 'member', 0, NULL, NULL),
('4bb46dab-25b0-49a2-cc01-0ee4f67883f5', '12345678-1111-0000-0000-sardinia----', 'member---0123-4567-89ab-000000000004', 1, 'member', 0, NULL, NULL),
('4bb46dab-25b0-49a2-cc02-0ee4f67883f5', '12345678-1111-0000-0000-newyork-----', 'member---0123-4567-89ab-000000000004', 1, 'member', 0, NULL, NULL);
	";
		$User->query($insert);
	}

}



?>
