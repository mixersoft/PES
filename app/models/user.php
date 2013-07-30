<?php
/**
 * Users Plugin User Model
 *	- Note: This class uses code borrowed from the CakeDC Users plugin
 * @package users
 * @subpackage users.models
 */
abstract class UserPlugin extends AppModel {
/**
 * Validation parameters
 *
 * @var array
 */
	public $validate = array();			// set in constructor
	
/**
 * Constructor
 *
 * @param string $id ID
 * @param string $table Table
 * @param string $ds Datasource
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->validate = array(
			'username' => array(
				'required' => array(
					'rule' => array('notEmpty'),
					'required' => true, 'allowEmpty' => false,
					'message' => __d('users', 'Please enter a username', true)),
				'alpha' => array(
					'rule'=>array('custom', '/^[a-z0-9_\-]*$/i'), 
					'message' => __d('users', 'The username must be alphanumeric', true)),
				'unique_username' => array(
					'rule'=>array('isUnique','username'),
					'message' => __d('users', 'This username is already in use.', true)),
				'username_min' => array(
					'rule' => array('minLength', '3'),
					'message' => __d('users', 'The username must have at least 3 characters.', true))),
			'email' => array(
				'isValid' => array(
					'rule' => 'email',
					'required' => false,
					'message' => __d('users', 'Please enter a valid email address.', true)),
				'isUnique' => array(
					'rule' => array('isUnique','email'),
					'message' => __d('users', 'This email is already in use.', true))),
			'password' => array(
				'to_short' => array(
					'rule' => array('minLength', '4'),
					'message' => __d('users', 'The password must have at least 4 characters.', true)),
				'required' => array(
					'rule' => 'notEmpty',
					'message' => __d('users', 'Please enter a password.', true))),
			'temppassword' => array(
				'rule' => 'confirmPassword',
				'message' => __d('users', 'The passwords are not equal, please try again.', true)),
			'tos' => array(
				'rule' => array('custom','[1]'),
				'message' => __d('users', 'You must agree to the terms of use.', true)));

		$this->validatePasswordChange = array(
			'new_password' => $this->validate['password'],
			'confirm_password' => array(
				'required' => array('rule' => array('compareFields', 'new_password', 'confirm_password'), 'required' => true, 'message' => __d('users', 'The passwords are not equal.', true))),
			'old_password' => array(
				'to_short' => array('rule' => 'validateOldPassword', 'required' => true, 'message' => __d('users', 'Invalid password.', true))));

//		$this->Detail->sectionSchema[$this->alias] = array(
//			'birthday' => array(
//				'type' => 'date',
//				'null' => null,
//				'default' => null,
//				'length' => null));
//
//		$this->Detail->sectionValidation[$this->alias] = array(
//			'birthday' => array(
//				'validDate' => array('rule' => array('date'), 'allowEmpty' => true, 'message' => __d('users', 'Invalid date', true))));
	}	
	

/**
 * After save callback
 *
 * @param boolean $created
 * @return void
 */
	public function afterSave($created) {
//		if ($created) {
//			if (!empty($this->data[$this->alias]['slug'])) {
//				if ($this->hasField('url')) {
//					$this->saveField('url', '/user/' . $this->data[$this->alias]['slug'], false);
//				}
//			}
//		}
	}

/**
 * afterFind callback
 *
 * @param array $results Result data
 * @param mixed $primary Primary query
 * @return array
 */
	public function afterFind($results, $primary = false) {
//		foreach ($results as &$row) {
//			if (isset($row['Detail']) && (is_array($row))) {
//				$row['Detail'] = $this->Detail->getSection($row[$this->alias]['id'], $this->alias);
//			}
//		}
		return $results;
	}

/**
 * Custom validation method to ensure that the two entered passwords match
 *
 * @param string $password Password
 * @return boolean Success
 */
	public function confirmPassword($password = null) {
		if ((isset($this->data[$this->alias]['password']) && isset($password['temppassword']))
			&& !empty($password['temppassword'])
			&& ($this->data[$this->alias]['password'] === $password['temppassword'])) {
			return true;
		}
		return false;
	}

/**
 * Compares the email confirmation
 *
 * @param array $email Email data
 * @return boolean
 */
	public function confirmEmail($email = null) {
		if ((isset($this->data[$this->alias]['email']) && isset($email['confirm_email']))
			&& !empty($email['confirm_email'])
			&& (strtolower($this->data[$this->alias]['email']) === strtolower($email['confirm_email']))) {
				return true;
		}
		return false;
	}

/**
 * Validates the user token
 *
 * @param string $token Token
 * @param boolean $reset Reset boolean
 * @param boolean $now time() value
 * @return mixed false or user data
 */
	public function validateToken($token = null, $reset = false, $now = null) {
		if (!$now) {
			$now = time();
		}

//		$this->recursive = -1;
		$data = false;
		$options = array(
			'fields'=>'User.id, User.email',
			'contain'=>array(
				'Profile'=>array(
					'conditions' => array('Profile.email_token' => $token),
					'fields'=>array('Profile.id','Profile.user_id','Profile.email_token','Profile.email_token_expires'),
				),
			)
		);
		$match = $this->find('first', $options);
		if (!empty($match)){
			$expires = strtotime($match['Profile']['email_token_expires']);
			if ($expires > $now) {
				
				$match['Profile']['email_authenticated'] = '1';

				if ($reset === true) {
					$match[$this->alias]['password'] = $this->generatePassword();
					$match['Profile']['password_token'] = null;
				}

				$match['Profile']['email_token'] = null;
				$match['Profile']['email_token_expires'] = null;
			}
		}
		return $match;
	}

/**
 * Updates the last activity field of a user
 * 
 * @param string $user User ID
 * @return boolean True on success
 */
	public function updateLastActivity($userId = null) {
		if (!empty($userId)) {
			$this->id = $userId;
		}
		if ($this->exists()) {
			return $this->saveField('last_activity', date('Y-m-d H:i:s', time()));
		}
		return false;
	}

/**
 * Checks if an email is in the system, validated and if the user is active so that the user is allowed to reste his password
 *
 * @param array $postData post data from controller
 * @return mixed False or user data as array on success
 */
	public function passwordReset($postData = array()) {
		$options = array(
			'contain'=>array('Profile.id','Profile.email_authenticated','Profile.password_token','Profile.email_token_expires'),
			'conditions' => array(
				$this->alias . '.active' => 1,
				$this->alias . '.email' => $postData[$this->alias]['email']),
		);
		$user = $this->find('first', $options );
		// if (!empty($user) && $user['Profile']['email_authenticated'] == 1) {
		/*
		 * allow password reset for non-authenticated email addresses
		 */ 	
		if (!empty($user)) {			
			$sixtyMins = time() + 43000;
			$token = $this->generateToken();
			$user['Profile']['password_token'] = $token;
			$user['Profile']['email_token_expires'] = date('Y-m-d H:i:s', $sixtyMins);
			$this->Profile->id = $user['Profile']['id'];
			$user = $this->Profile->save($user, false);
			return $user;
		} elseif (!empty($user) && $user['Profile']['email_authenticated'] == 0){
			$this->invalidate('email', __d('users', 'This Email Address exists but was never validated.', true));
		} else {
			$this->invalidate('email', __d('users', 'This Email Address does not exist in the system.', true));
		}
		return false;
	}

/**
 * Checks the token for a password change
 * 
 * @param string $token Token
 * @return mixed False or user data as array
 */
	public function checkPasswordToken($token = null) {
		$options = array(
			'fields'=>'User.id, User.email',
			'conditions' => array(
				$this->alias . '.active' => 1,
			),
		);		
		$options['joins'][] = array(
			'table'=>'profiles',
			'alias'=>'Profile',
			'type'=>'INNER',
			'fields'=>array('Profile.id', 'Profile.password_token','Profile.email_token_expires'),
			'conditions'=>array(
				'Profile.user_id = User.id',
				'Profile.password_token' => $token, 
				'Profile.email_token_expires >=' => date('Y-m-d H:i:s')
			)	
		);
		$user = $this->find('first', $options);
		// $user = $this->find('first', array(
			// 'contain' => array(),
			// 'conditions' => array(
				// $this->alias . '.active' => 1,
				// $this->alias . '.password_token' => $token,
				// $this->alias . '.email_token_expires >=' => date('Y-m-d H:i:s'))));
		if (empty($user)) {
			return false;
		}
		return $user;
	}

/**
 * Resets the password
 * 
 * @param array $postData Post data from controller
 * @return boolean True on success
 */
	public function resetPassword($postData = array()) {
		$result = false;
		$tmp = $this->validate;
		$this->validate = array(
			'new_password' => $this->validate['password'],
			'confirm_password' => array(
				'required' => array(
					'rule' => array('compareFields', 'new_password', 'confirm_password'), 
					'message' => __d('users', 'The passwords are not equal.', true))));

		$this->set($postData);
debug($postData);
debug($this->validates());		
		if ($this->validates()) {
			App::import('Core', 'Security');
			$this->data[$this->alias]['password'] = Security::hash($this->data[$this->alias]['new_password'], null, true);
			$this->data[$this->alias]['password_token'] = null;
			$result = $this->save($this->data, false);
debug($this->data);
debug($result);			
		}
		$this->validate = $tmp;
		return $result;
	}

/**
 * Changes the password for a user
 *
 * @param array $postData Post data from controller
 * @return boolean True on success
 */
	public function changePassword($postData = array()) {
		$this->set($postData);
		//$tmp = $this->validate;
		$this->validate = $this->validatePasswordChange;

		if ($this->validates()) {
			App::import('Core', 'Security');
			$this->data[$this->alias]['password'] = Security::hash($this->data[$this->alias]['new_password'], null, true);
			$this->save($postData, array(
				'validate' => false,
				'callbacks' => false));
			//$this->validate = $tmp;
			return true;
		}

		//$this->validate = $tmp;
		return false;
	}

/**
 * Validation method to check the old password
 *
 * @param array $password 
 * @return boolean True on success
 */
	public function validateOldPassword($password) {
		if (!isset($this->data[$this->alias]['id']) || empty($this->data[$this->alias]['id'])) {
			if (Configure::read('debug') > 0) {
				throw new OutOfBoundsException(__d('users', '$this->data[\'' . $this->alias . '\'][\'id\'] has to be set and not empty', true));
			}
		}
		$current_password = $this->field('password', array($this->alias . '.id' => $this->data[$this->alias]['id']));
		App::import('Core', 'Security');
		if ($current_password === Security::hash($password['old_password'], null, true)) {
			return true;
		}
		return false;
	}

/**
 * Validation method to compare two fields
 *
 * @param mixed $field1 Array or string, if array the first key is used as fieldname
 * @param string $field2 Second fieldname
 * @return boolean True on success
 */
	public function compareFields($field1, $field2) {
		if (is_array($field1)) {
			$field1 = key($field1);
		}
		if (isset($this->data[$this->alias][$field1]) && isset($this->data[$this->alias][$field2]) && 
			$this->data[$this->alias][$field1] == $this->data[$this->alias][$field2]) {
			return true;
		}
		return false;
	}
	

/**
 * Registers a new user
 *
 * @param array $postData Post data from controller
 * @param boolean $useEmailVerification If set to true a token will be generated
 * @return mixed
 */
	public function register($postData = array(), $useEmailVerification = true) {
		// move these fields to Profile table
		$defaultActive = !empty($postData[$this->alias]['active']) ? 1 : 0;
		if ($useEmailVerification == true) {
			$postData['Profile']['email_token'] = $this->generateToken();
			$postData['Profile']['email_token_expires'] = date('Y-m-d H:i:s', time() + 86400);
			$postData[$this->alias]['active'] = $defaultActive;	// set active in /validate/email/[token]?
		} else {
			$postData['Profile']['email_authenticated'] = 1;
			$postData[$this->alias]['active'] = 1;	
		}
		$postData['Profile']['tos'] = $postData['User']['tos'];
		// $this->_removeExpiredRegistrations();
// debug($postData);		
		$this->_removeExpiredEmailTokens();
		$this->set($postData);
		if ($this->validates()) {
			App::import('Core', 'Security');
			$postData[$this->alias]['password'] = Security::hash($postData[$this->alias]['password'], 'sha1', true);
			$postData[$this->alias]['primary_group_id'] = Configure::read('lookup.roles.USER'); 
			$this->create();
			$ret = $this->save($postData, false);
			if ($ret) {
				$postData['Profile']['user_id'] = $this->id;
				$ret = $this->Profile->save($postData, false);
			}
			return $ret ? $postData : false;
		}

		return false;
	}

/**
 * Resends the verification if the user is not already validated or invalid
 *
 * @param array $postData Post data from controller
 * @return mixed False or user data array on success
 */
	public function resendVerification($postData = array()) {
		if (!isset($postData[$this->alias]['email']) || empty($postData[$this->alias]['email'])) {
			$this->invalidate('email', __d('users', 'Please enter your email address.', true));
			return false;
		}
		$options = array(
			'fields'=>'User.id, User.username, User.email, User.active',
			'contain'=>array('Profile.id','Profile.email_authenticated'),
			'conditions' => array(
				$this->alias . '.email' => $postData[$this->alias]['email'])
		);
		$user = $this->find('first', $options);
//		$user = $this->find('first', array(
//			'contain' => array(),
//			'conditions' => array(
//				$this->alias . '.email' => $postData[$this->alias]['email'])));

		if (empty($user)) {
			$this->invalidate('email', __d('users', 'The email address does not exist in the system', true));
			return false;
		}

		if ($user['Profile']['email_authenticated'] == 1) {
			$this->invalidate('email', __d('users', 'Your account is already authenticaed.', true));
			return false;
		}

//		if ($user[$this->alias]['active'] == 0) {
//			$this->invalidate('email', __d('users', 'Your account is disabled.', true));
//			return false;
//		}

		$user['Profile']['email_token'] = $this->generateToken();
		$user['Profile']['email_token_expires'] = date('Y-m-d H:i:s', time() + 86400);
		$this->Profile->id = $user['Profile']['id'];
		return $this->Profile->save($user, false);
	}

/**
 * Generates a password
 *
 * @param int $length Password length
 * @return string
 */
	public function generatePassword($length = 10) {
		srand((double)microtime() * 1000000);
		$password = '';
		$vowels = array("a", "e", "i", "o", "u");
		$cons = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "u", "v", "w", "tr",
							"cr", "br", "fr", "th", "dr", "ch", "ph", "wr", "st", "sp", "sw", "pr", "sl", "cl");
		for ($i = 0; $i < $length; $i++) {
			$password .= $cons[mt_rand(0, 31)] . $vowels[mt_rand(0, 4)];
		}
		return substr($password, 0, $length);
	}

/**
 * Generate token used by the user registration system
 *
 * @param int $length Token Length
 * @return string
 */
	public function generateToken($length = 10) {
		$possible = '0123456789abcdefghijklmnopqrstuvwxyz';
		$token = "";
		$i = 0;

		while ($i < $length) {
			$char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
			if (!stristr($token, $char)) {
				$token .= $char;
				$i++;
			}
		}
		return $token;
	}

/**
 * Adds a new user
 * 
 * @param array post data, should be Controller->data
 * @return array
 */
// TODO: test	
	public function add($postData = null) {
		if (!empty($postData)) {
			$this->create();
			if ($this->save($postData)) {
				return true;
			}
		}
	}

/**
 * Edits an existing user
 *
 * @param string $userId User ID
 * @param array $postData controller post data usually $this->data
 * @return mixed True on successfully save else post data as array
 */
// TODO: test	
	public function edit($userId = null, $postData = null) {
		$user = $this->find('first', array(
			'contain' => array(
				'Detail'),
			'conditions' => array(
				$this->alias . '.id' => $userId)));

		$this->set($user);
		if (empty($user)) {
			throw new OutOfBoundsException(__d('users', 'Invalid User', true));
		}

		if (!empty($postData)) {
			$this->set($postData);
			$result = $this->save(null, true);
			if ($result) {
				$this->data = $result;
				return true;
			} else {
				return $postData;
			}
		}
	}

/**
 * Removes all users from the user table that are outdated
 *
 * Override it as needed for your specific project
 *
 * @return void
 */
	protected function _removeExpiredRegistrations() {
		// delete from both users and profiles table using cascade
		$now = date('Y-m-d H:i:s');
		$sql = <<<EOD
DELETE `User`, `Profile`
FROM `users` AS `User`
INNER JOIN `profiles` AS `Profile` ON (`Profile`.`user_id` = `User`.`id`)
WHERE `Profile`.email_authenticated=0 AND `Profile`.email_token_expires < '{$now}'
EOD;
		$this->query($sql);
//		$this->deleteAll(array(
//			'Profile.email_authenticated' => 0,
//			'Profile.email_token_expires <' => date('Y-m-d H:i:s')));
	}
	
/**
 * Removes expired email Tokens, but does not delete user
 *
 * Override it as needed for your specific project
 *
 * @return void
 */
	protected function _removeExpiredEmailTokens() {
		// delete from both users and profiles table using cascade
		$now = date('Y-m-d H:i:s');
$sql = <<<EOD
UPDATE profiles as `Profile` 
SET `Profile`.email_token_expires = NULL, `Profile`.email_token = NULL 
WHERE `Profile`.email_token_expires < '{$now}'
EOD;
		$this->query($sql);
//		$this->deleteAll(array(
//			'Profile.email_authenticated' => 0,
//			'Profile.email_token_expires <' => date('Y-m-d H:i:s')));
	}		
}






















/**
 * Snaphappi User Model
 * 
 * 
 */
class User extends UserPlugin {
	public $name = 'User';
	public $displayField = 'username';
	
/**
 * Validation parameters
 *
 * @var array
 */
	
	public $validate = array();
	
	// metadata validate
	public static $metadataValidate = array(
            'lightbox' => array(
            'regionAsJSON' => array(
				'rule' => 'alphaNumeric', // array('validateJsonString', null)
				'message' => 'must be alphaNumeric',
            ),
		)
	);

	
	public $actsAs = array(
		'Search.Searchable',
		'Utils.Sluggable' => array(
			'label' => 'username',
			'method' => 'multibyteSlug'),	
//		'Comments.Sluggable' => array('label' => 'username'),
		'Metadata.Metadata' => array( ),
	);
	

    function validateJsonString($data,$param){
		//return json_decode($data);
		return true;
    }

	/*
	 * Search plugin
	 */
	public $filterArgs = array(
		array('name' => 'username', 'type' => 'like', 'field' => 'username'),
		array('name' => 'slug', 'type' => 'like', 'field' => 'slug'),
		array('name' => 'email', 'type' => 'like', 'field' => 'email'),
	);

//	public function findByTags($data = array()) {
//		$this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
//		$this->Tagged->Behaviors->attach('Search.Searchable');
//		return $query;
//	}

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->validate = array_merge($this->validate_User, $this->validate);
	}
			
	public function beforeFind($queryData) {
		return true;
	}	
	
	public $validate_User = array(	);		// local validate rules, merged with parent
	
	public function afterFind($results, $primary) {
		if ($primary && !Configure::read('controller.isXhr')  && isset($results[0]['User']['id'])){
			if ($results[0]['User']['id'] == Configure::read('controller.xhrFrom.uuid')) {
				Configure::write('controller.isOwner', $results[0]['User']['id'] == AppController::$ownerid);
			}
		}
		return $results;
	}	
	
	
	
	/**
	 * since we are using owner key, we can ignore permissionable 
	 */
	function getOwnBatches() {
		$owner_id = AppController::$ownerid;
		$uniqueBatchSql = "select DISTINCT ProviderAccount.id, ProviderAccount.provider_name, Asset.batchId
from provider_accounts ProviderAccount
join assets Asset on Asset.provider_account_id = ProviderAccount.id
where ProviderAccount.user_id='{$owner_id}'
order by ProviderAccount.provider_name, ProviderAccount.created;
		";
		$data = $this->query($uniqueBatchSql);
		return $data;
	}
	
	
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $hasOne = array(
        'Profile' => array(
            'className'    => 'Profile',
			'foreignKey' => 'user_id',
			'type' => 'LEFT',
            'conditions'   => '',
            'dependent'    => true
        )
    ); 
	var $belongsTo = array(
		'PrimaryGroup' => array(				// primary group
			'className' => 'Group',
			'foreignKey' => 'primary_group_id',
			'conditions' => array('PrimaryGroup.isSystem'=>1,"PrimaryGroup.id like 'role-%'"),
			'fields' => '',
			'order' => ''
		)
	);
	
	var $hasMany = array(
		'Asset' => array(
			'className' => 'Asset',
			'foreignKey' => 'owner_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'AuthAccount' => array(
			'className' => 'AuthAccount',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'Collection' => array(
			'className' => 'Collection',
			'foreignKey' => 'owner_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'ProviderAccount' => array(
			'className' => 'ProviderAccount',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'UserEdit' => array(
			'className' => 'UserEdit',
			'foreignKey' => 'owner_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
		'Usershot' => array(					// Usershot hasOne Owner 
			'className' => 'Usershot',
			'foreignKey' => 'owner_id',
			'dependent' => true,
		),		
	);


	var $hasAndBelongsToMany = array(
		'Membership' => array(
//			'with' => 'groups_users',
			'with' => 'GroupsUser',
			'className' => 'Group',
		),
	);
	
	
	function __getProfileDefaults () {
		$profileDefaults = array();
		return $profileDefaults;
	}
	
	//TODO: this should be a method on provider_account
	function lookupPhotostreams($id) {
		$lookup = "SELECT -- DISTINCT
ProviderAccount.id as id, Asset.batchId, concat( ProviderAccount.display_name,'@',ProviderAccount.provider_name) AS provider_account_name, Owner.id, Owner.username as owner, count(*) as photos
FROM assets AS `Asset`
JOIN `provider_accounts` AS `ProviderAccount` ON `Asset`.`provider_account_id` = `ProviderAccount`.`id`
JOIN `users` AS `Owner` ON (`ProviderAccount`.`user_id` = `Owner`.`id`)
WHERE 1 = 1
AND `Asset`.`owner_id` = '{$id}'
GROUP BY ProviderAccount.id, Asset.batchId ORDER BY photos DESC;";
		$data=$this->query($lookup);
		return $data;
	}

	function appendFilterConditions($options, $conditions) {
		/*
		 * add filters, from $this->params['named']
		 */
		$filterConditions = array();
		if (isset($options['q'])) {
			// text search, 
			$searchKeys = array('username', 'slug', 'email');
			$filterConditions[] = $this->appendSearchConditions($options, $searchKeys );
		}	
		return @mergeAsArray( $conditions, $filterConditions);
	}	
	
	function getPaginateUsers ( $paginate = array(), $skipContext = true) {
		$paginateModel = 'User';
//debug($paginateModel);		
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for GroupId
		$conditions = $joins = array();
				
		$skip = $skipContext;
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
			}
			if ($context['keyName'] == 'Tag') {
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		
		return $paginate;
	}
	
	
	function getPaginateUsersByGroupId ($groupid , $paginate = array()) {
		$controllerClass = Configure::read('controller.class');
		$paginateModel = ($controllerClass == 'Group') ? 'Member' : 'User';
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for GroupId
		$conditions = $joins = array();
		$joins[] = 	array(
			'table'=>'groups',
			'alias'=>'GroupOwner',
			'type'=>'INNER',
			'conditions'=>array("`GroupOwner`.id = '{$groupid}'"),
		);		
		$joins[] = 	array(
			'table'=>'groups_users',
			'alias'=>'HABTM',
			'type'=>'LEFT',
			'conditions'=>array("`HABTM`.user_id = `{$paginateModel}`.id", "`HABTM`.`group_id`= '{$groupid}'"),
		);
		$conditions = array('OR'=>array("`Member`.id = `GroupOwner`.owner_id", "`HABTM`.user_id IS NOT NULL" ));
				
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				// no meaningful context restriction for /groups/members context=Users
//				$conditions[] = "`Member`.id='{$context['uuid']}'";
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip
			}
			if ($context['keyName'] == 'Tag') {
				// skip no tags on Members/Users
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		
		return $paginate;
	}			

	
/**
 * Returns all data about a user
 * - from cakedc Users plugin
 * @param string $slug user slug
 * @return array
 */
	public function getFromSlug($slug = null) {
		$user = $this->find('first', array(
			'contain' => array(),
			'conditions' => array(
				$this->alias . '.slug' => $slug,
				$this->alias . '.active' => 1,
//				$this->alias . '.email_authenticated' => 1,
			)));
//		if (empty($user)) {
//			throw new Exception(__d('users', 'The user does not exist.', true));
//		}
		return !empty($user) ? $user['User']['id'] : null;
	}
	
	
	public function checkIfPendingValidation($data) {
		$options = array(
			'fields'=>'User.id, User.email, User.active',
			'contain'=>array('Profile.id','Profile.email_token','Profile.email_token_expires'),
			'conditions' => array('User.username'=>$data['User']['username'], 'User.password'=>$data['User']['password'], 'User.active'=>0,
					'Profile.email_token IS NOT NULL', 'Profile.email_token_expires >'=>date('Y-m-d H:i:s')),
		);		
		$data = $this->find('first', $options);
		return !empty($data);
	}
	
	
	/*
	 * update Person counters for assets, memberships
	 * 		works with counterCache, 
	 */
	function updateCounter($uid) {
		return $this->updateAllCounts($uid);
	}	
	
	function updateAllCounts($uid = NULL) {
		$WHERE = $uid ? "WHERE u.id='{$uid}'" : '';
		$SQL = "
UPDATE users AS `User`
INNER JOIN (
	SELECT u.id as user_id, COUNT(DISTINCT a.id) AS `asset_count`,
	  COUNT(DISTINCT gu.group_id)+COUNT(DISTINCT g.id)  AS `groups_user_count`
	FROM `users` u
	LEFT JOIN assets a ON u.id = a.owner_id
	LEFT JOIN groups_users gu ON u.id = gu.user_id
	LEFT JOIN groups g ON (u.id = g.owner_id and g.isSystem=0)
	{$WHERE}
	GROUP BY u.id
) AS t ON (`User`.id = t.user_id)
SET `User`.asset_count = t.asset_count, `User`.groups_user_count = t.groups_user_count;";
		$result = $this->query($SQL);
		return $result;
	}

	function setRandomBadgePhoto($id){
		$options = array(
			'conditions'=>array('User.id'=>$id),
			'recursive' => -1, 
			'fields'=>array('User.id', 'User.src_thumbnail'),
		);
		$row = $this->find('first', $options);
		// $gids = Set::extract('/User/id', $data);
		if (!empty($row['User']['src_thumbnail'])) {
			$wwwroot = Configure::read('path.wwwroot');
			$imgFilepath = $wwwroot.Stagehand::getSrc($row['User']['src_thumbnail'], '');
			if (file_exists($imgFilepath)) {
				return; 	// already set and valid, skip
			} 			
		}  
		$uid = $row['User']['id'];
		$this->id = $uid;
		$sql = "
SELECT Asset.src_thumbnail, SharedEdit.score, Asset.id
FROM assets Asset
LEFT JOIN shared_edits AS `SharedEdit` ON (`SharedEdit`.`asset_id` = `Asset`.`id`)
WHERE Asset.owner_id='{$uid}'
order by score desc
LIMIT 5;";
		$asset = $this->query($sql);
		if ($asset) {
			$srcs = Set::extract('/Asset/src_thumbnail', $asset);
			shuffle($srcs);
			$ret = $this->saveField('src_thumbnail', array_shift($srcs));
		} else $ret = $this->saveField('src_thumbnail', '');	// set to null
		return;
	}		
	
	/**
	 * @params array of User ids
	 * @return aa of user badge data, indexed by uuid
	 */
	function getBadges($uuid) {
		if (!is_array($uuid)) $uuid = array($uuid);
		$options = array(
			'contain'=>'Profile',
			'conditions'=>array('User.id'=>$uuid),
			'fields'=>'User.id, User.username, Profile.fname, Profile.lname, User.slug, User.src_thumbnail, User.asset_count, User.groups_user_count'
		);		
		$data = $this->find('all', $options);
		$formatted = array();
		foreach ($data as & $row) {
			$row[0]['linkTo'] = Router::url(array('controller'=>'person', 'action'=>'home' , $row['User']['id']));	
			$row[0]['src'] =   Stagehand::getSrc($row['User']['src_thumbnail'], null, 'Person');
			if (empty($row['Profile']['fname']) && empty($row['Profile']['lname'])) {
				$row[0]['fullname'] = '';
			} else {
				$row[0]['fullname'] = ucFirst("{$row['Profile']['fname']} {$row['Profile']['lname']}");
			}
			$formatted[$row['User']['id']] = $row;
		}
		return $formatted;
	}
	
}
?>