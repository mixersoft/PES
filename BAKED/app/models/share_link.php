<?php
class ShareLink extends AppModel {
	/*
	 * NOTE: use STATIC for security level, skip security_level lookup table
	 */ 
	public static $SECURITY_LEVEL = array(
		'NONE'=>1, 
		'PASSWORD'=>2, 
		'LOGIN'=>3
	);
	
	function __construct() {
       parent::__construct();
       $this->validate['security_level']['InList']['rule'] = array('inList', array_values(ShareLink::$SECURITY_LEVEL));
   }
	
	public $order = array('ShareLink.created' => 'desc');
	// public $belongsTo = array('SecurityLevel' => array('foreignKey' => 'security_level'));
	public $validate = array(
		'secret_key' => array('rule' => 'notEmpty'),
		'security_level' => array(
			'InList' => array('rule' =>  array('inList', array())),		// cant use static yet
			'NotEmpty' => array('rule' => 'notEmpty'),
			'hasPassword' =>  array('rule' => array('validateHasPassword')),
		),
		'target_id' => array(
			array('rule' => 'notEmpty'),
		),
		'target_url' => array(
			// array('rule' => array('url', true)),		// doesn't validate with :[PORT] values
			array('rule' => 'notEmpty'),
		),
		'active' => array(
			array('rule' => 'notEmpty'),
			array('rule' => 'boolean'),
		),
		'owner_id' => array('rule' => 'notEmpty'),
		'expiration_count' => array('rule' => 'numeric', 'message' => 'Must be numeric', 'allowEmpty' => true),
	);


	public $brwConfig = array(
		'paginate' => array('fields' => array('id', 'secret_key', 'security_level', 'target_url', 'target_id', 'owner_id', 'renewal_request')),
		'fields' => array('filter' => array('secret_key', 'security_level', 'target_url', 'target_id', 'owner_id', 'renewal_request')),
	);

	public function beforeValidate() {
		$this->data = $this->_addHashedPassword($this->data);
		$this->data = $this->_addSecretKey($this->data);
		return true;
	}


	private function _addHashedPassword($data) {
		if (!empty($data['ShareLink']['hashed_password'])) {
			$data['ShareLink']['hashed_password'] = Security::hash($data['ShareLink']['hashed_password']);
		}
		return $data;
	}


	private function _addSecretKey($data) {
		if (empty($data['ShareLink']['id'])) {
			$data['ShareLink']['secret_key'] = base_convert(str_replace('-', '', String::uuid()), 16, 36);
		}
		return $data;
	}


	public function getControllerAliasFromModel($model) {
		$map = array('Post' => 'posts', 'Comment' => 'comments');
		return empty($map[$model]) ? null : $map[$model];
	}


	public function validateHasPassword($data) {
		if ($data['security_level'] == ShareLink::$SECURITY_LEVEL['PASSWORD']) {
			if (empty($this->data['ShareLink']['hashed_password'])) {
				return false;
			}
		}
		return true;
	}


	public function createNew($data) {
		$defaults = array(
			'security_level' => ShareLink::$SECURITY_LEVEL['NONE'],
			'active' => 1,
			'expiration_count' => null,
			'expiration_date' => null,
			'owner_id' => null,
			'target_id' => null,
			'secret_key' => null,
		);
		$data = Set::merge($defaults, $data);
		$data['id'] = null;
		if ($this->save($data)) {
			return $this->findById($this->id);
		} else {
			debug($this->validationErrors);
			return false;
		}
	}


	/**
	* return array if ok, string with error code if error
	* */
	function get($secretKey) {
		$shareLink = $this->findBySecretKey($secretKey);
		if (empty($shareLink)) {
			return 'non-existent';
		} elseif (
			!empty($shareLink['ShareLink']['expiration_date'])
			and ($shareLink['ShareLink']['expiration_date'] != '0000-00-00 00:00:00')
			and ($shareLink['ShareLink']['expiration_date'] < date('Y-m-d h:i:s'))
		) {
			return 'expired-date';
		} elseif (
			($shareLink['ShareLink']['count'] >= $shareLink['ShareLink']['expiration_count'])
			and	ctype_digit($shareLink['ShareLink']['expiration_count'])
		) {
			return 'expired-count';
		} elseif (!$shareLink['ShareLink']['active']) {
			return 'inactive';
		}
		return $shareLink;
	}


	function increaseCount($id) {
		$shareLink = $this->findById($id);
		if ($shareLink) {
			return $this->save(array('id' => $id, 'count' => $shareLink['ShareLink']['count'] + 1));
		}
	}


	function askRenewal($secretKey, $comment) {
		$errorCode = $this->get($secretKey);
		if (!in_array($errorCode, array('expired-date', 'expired-count'))) {
			return 'not-expired';
		}
		$shareLink = $this->findBySecretKey($secretKey);
		$data = array(
			'id' => $shareLink['ShareLink']['id'],
			'renewal_request' => 1,
			'renewal_comment' => $comment
		);
		if ($this->save($data)) {
			return $this->findBySecretKey($secretKey);
		} else {
			return 'error-save';
		}
	}


	public function getAllForTarget($targetId) {
		return $this->find('all', array('conditions' => array('ShareLink.target_id' =>  $targetId)));
	}


	function getAllForOwner($onwerId, $onlyWithRenealRequest = false) {
		$params = array('conditions' => array('ShareLink.owner_id' => $onwerId));
		if ($onlyWithRenealRequest) {
			$params['conditions']['ShareLink.renewal_request'] = 1;
		}
		return $this->find('all', $params);
	}

}