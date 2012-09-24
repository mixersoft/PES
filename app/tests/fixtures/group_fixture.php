<?php
/* Group Fixture generated on: 2012-09-24 15:45:12 : 1348501512 */
class GroupFixture extends CakeTestFixture {
	var $name = 'Group';
	var $import = array('model' => 'Group');


	var $records = array(
		array(
			'id' => 'member---0123-4567-89ab-000000000001',
			'owner_id' => '12345678-1111-0000-0000-123456789abc',
			'isSystem' => 0,
			'type' => 'Group',
			'title' => 'public group',
			'description' => NULL,
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => '1',
			'privacy_secret_key' => '0',
			'src_thumbnail' => 'stage3/tn~4bbbe67f-b5a4-4280-92ca-11a0f67883f5.jpg',
			'assets_group_count' => '423',
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => '1',
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2012-09-06 19:53:46',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2012-09-06 19:53:46'
		),
		array(
			'id' => 'member---0123-4567-89ab-000000000002',
			'owner_id' => '12345678-1111-0000-0000-venice------',
			'isSystem' => 0,
			'type' => 'Group',
			'title' => 'Italy',
			'description' => 'pictures from Italy (edited)',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => '1',
			'privacy_secret_key' => '2',
			'src_thumbnail' => 'stage7/tn~4bbb3907-da18-4296-b97e-11a0f67883f5.jpg',
			'assets_group_count' => '641',
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => '3',
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2012-09-06 19:53:46',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2012-09-06 19:53:46'
		),
		array(
			'id' => 'member---0123-4567-89ab-000000000003',
			'owner_id' => '12345678-1111-0000-0000-paris-------',
			'isSystem' => 0,
			'type' => 'Group',
			'title' => 'Europe',
			'description' => 'Owner=Paris, Admin=Venice. Member=Sardinia
perm = 631 (rwd/-wd/r--/r--) - public listing, content is member only, for members, add to groupIds ',
			'membership_policy' => '1',
			'invitation_policy' => '1',
			'submission_policy' => '1',
			'privacy_secret_key' => '1',
			'src_thumbnail' => 'stage3/tn~4bbb38cc-1d00-4321-845d-11a0f67883f5.jpg',
			'assets_group_count' => '868',
			'collections_group_count' => NULL,
			'comment_count' => '15',
			'groups_user_count' => '4',
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2012-09-06 19:53:46',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2012-09-06 19:53:46'
		),
		array(
			'id' => 'member---0123-4567-89ab-000000000004',
			'owner_id' => '12345678-1111-0000-0000-sardinia----',
			'isSystem' => 0,
			'type' => 'Group',
			'title' => 'Island',
			'description' => 'photo of Islands from around the world',
			'membership_policy' => '1',
			'invitation_policy' => '2',
			'submission_policy' => '1',
			'privacy_secret_key' => '2',
			'src_thumbnail' => 'stage7/tn~4bbb3907-ab04-4b63-a3a9-11a0f67883f5.jpg',
			'assets_group_count' => '765',
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => '2',
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2012-09-06 19:53:46',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2012-09-06 19:53:46'
		),
		array(
			'id' => 'member---0123-4567-89ab-000000000005',
			'owner_id' => '12345678-1111-0000-0000-venice------',
			'isSystem' => 0,
			'type' => 'Group',
			'title' => 'Venice private group',
			'description' => NULL,
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => '1',
			'privacy_secret_key' => '2',
			'src_thumbnail' => 'stage3/tn~4bbb3976-5204-4280-8a76-11a0f67883f5.jpg',
			'assets_group_count' => '244',
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => '1',
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2012-09-06 19:53:46',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2012-09-06 19:53:46'
		),
		array(
			'id' => 'role-----0123-4567-89ab---------user',
			'owner_id' => 'role-----0123-4567-89ab-cdef----root',
			'isSystem' => 1,
			'type' => 'Group',
			'title' => '__user',
			'description' => 'signed in user',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => NULL,
			'privacy_secret_key' => '0',
			'src_thumbnail' => NULL,
			'assets_group_count' => NULL,
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => NULL,
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2010-09-07 13:26:55',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2010-09-07 06:26:55'
		),
		array(
			'id' => 'role-----0123-4567-89ab--------admin',
			'owner_id' => 'role-----0123-4567-89ab-cdef----root',
			'isSystem' => 1,
			'type' => 'Group',
			'title' => '__admin',
			'description' => 'sysadmin group',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => NULL,
			'privacy_secret_key' => '0',
			'src_thumbnail' => NULL,
			'assets_group_count' => NULL,
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => NULL,
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2010-09-07 13:26:55',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2010-09-07 06:26:55'
		),
		array(
			'id' => 'role-----0123-4567-89ab--------guest',
			'owner_id' => 'role-----0123-4567-89ab-cdef----root',
			'isSystem' => 1,
			'type' => 'Group',
			'title' => '__guest',
			'description' => 'guest user, credentials provided by session/cookie',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => NULL,
			'privacy_secret_key' => '0',
			'src_thumbnail' => NULL,
			'assets_group_count' => NULL,
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => NULL,
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2010-09-07 13:26:55',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2010-09-07 06:26:55'
		),
		array(
			'id' => 'role-----0123-4567-89ab-------editor',
			'owner_id' => 'role-----0123-4567-89ab-cdef----root',
			'isSystem' => 1,
			'type' => 'Group',
			'title' => '__editor',
			'description' => 'backoffice group for staff editors',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => NULL,
			'privacy_secret_key' => '0',
			'src_thumbnail' => NULL,
			'assets_group_count' => NULL,
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => NULL,
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2010-09-07 13:26:55',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2010-09-07 06:26:55'
		),
		array(
			'id' => 'role-----0123-4567-89ab------manager',
			'owner_id' => 'role-----0123-4567-89ab-cdef----root',
			'isSystem' => 1,
			'type' => 'Group',
			'title' => '__manager',
			'description' => 'backoffice group for supervisors, team leaders, and QA managers',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => NULL,
			'privacy_secret_key' => '0',
			'src_thumbnail' => NULL,
			'assets_group_count' => NULL,
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => NULL,
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2010-09-07 13:26:55',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2010-09-07 06:26:55'
		),
		array(
			'id' => 'role-----0123-4567-89ab------visitor',
			'owner_id' => 'role-----0123-4567-89ab-cdef----root',
			'isSystem' => 1,
			'type' => 'Group',
			'title' => '__visitor',
			'description' => 'no cookie',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => NULL,
			'privacy_secret_key' => '0',
			'src_thumbnail' => NULL,
			'assets_group_count' => NULL,
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => NULL,
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2010-09-07 13:26:55',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2010-09-07 06:26:55'
		),
		array(
			'id' => 'role-----0123-4567-89ab-cdef----root',
			'owner_id' => 'role-----0123-4567-89ab-cdef----root',
			'isSystem' => 1,
			'type' => 'Group',
			'title' => '__root',
			'description' => 'superuser group. no Permissions validation',
			'membership_policy' => NULL,
			'invitation_policy' => NULL,
			'submission_policy' => NULL,
			'privacy_secret_key' => '0',
			'src_thumbnail' => NULL,
			'assets_group_count' => NULL,
			'collections_group_count' => NULL,
			'comment_count' => NULL,
			'groups_user_count' => NULL,
			'slug' => NULL,
			'isNC17' => 0,
			'lastVisit' => '2010-09-07 13:26:55',
			'created' => '2010-04-06 04:23:21',
			'modified' => '2010-09-07 06:26:55'
		),
	);
}
