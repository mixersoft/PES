<?php
class ProviderAccount extends AppModel {
	var $name = 'ProviderAccount';
	var $displayField = 'provider_name';
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $validate = array(
		'provider_name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'provider_key' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'display_name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);
	var $belongsTo = array(
		'Owner' => array(
			'className' => 'User',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	var $hasMany = array(
		'Asset' => array(
			'className' => 'Asset',
			'foreignKey' => 'provider_account_id',
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
		'ThriftDevice' => array(
			'className' => 'ThriftDevice',
			'foreignKey' => 'provider_account_id',
			'dependent' => true,
		),
	);
	
	var $hasAndBelongsToMany = array(
		'Group' => array(
			'with' => 'GroupsProviderAccount',
			'className' => 'Group',
		),
	);
	/**
	 * @params $conditions, conditions to find existing provider_account by userid-provider_name
	 */
	function addIfNew($providerAccount, $conditions, & $response){
		$providerAccount['id'] = isset($providerAccount['id']) ? $providerAccount['id'] : null;
		$ret = true;
		$options['recursive'] = -1;
		$options['conditions'] = $conditions ? $conditions : array('ProviderAccount.id'=>$providerAccount['id']);
		$data = $this->find('first', $options);
		if (empty($data['ProviderAccount']['id'])) {
			// create providerAccount for provider='snappi'
			$this->create();
			if (empty($providerAccount['id'])) $providerAccount['id'] = String::uuid();
			if (empty($providerAccount['provider_key'])) $providerAccount['provider_key'] = $providerAccount['id'];
			if (empty($providerAccount['user_id'])) $providerAccount['user_id'] = AppController::$userid;
			if (empty($providerAccount['display_name'])) $providerAccount['display_name'] = Session::read('Auth.User.displayname');
			$data = array('ProviderAccount'=>$providerAccount);
			if ($ret = $this->save($data)) {
				$response['message'][] = "ProviderAccount created successfully. id={$providerAccount['id']}";
			} else 	{
				$response['message'][]="Error creating provider account, user_id={$providerAccount['user_id']}";
				$response['response']['Error: ProviderAccount']=$providerAccount;
			}
			
		} else {
			$data['ProviderAccount']['baseurl'] = isset($providerAccount['baseurl']) ? $providerAccount['baseurl'] : '';
			$this->save(array('id'=>$data['ProviderAccount']['id'], 'baseurl'=>$data['ProviderAccount']['baseurl'] ));
			$response['message'][] = "ProviderAccount already exists. id={$data['ProviderAccount']['id']}";
		}
		$response['success'] = isset($response['success']) ? $response['success'] && $ret : $ret;
		return $data; 			
	}
	/**
	 * find ProviderAccount.authToken for Thrift API
	 * @param $authToken String
	 * @param $providerKey String, should be the native-uploader DeviceID
	 */
	function thrift_findByAuthToken($authToken, $deviceId){
		$options = array(
				'contain' => 'Owner',
				'conditions'=>array(
					'ProviderAccount.auth_token'=>$authToken,
					'OR'=>array(
						'ProviderAccount.provider_key'=>array($deviceId,''),
						'ProviderAccount.provider_key IS NULL',
					),
				),
			);
		$data = $this->find('all', $options);
		$unbound_row=array();
		foreach($data as $i=>$row) {
			if ($row['ProviderAccount']['provider_key']==$deviceId) {
				return $row;		// found correct PA, we're done
			}
			if (empty($row['ProviderAccount']['provider_key'])) $unbound_row=$row;
		}
		if (!empty($unbound_row)) {
			$paData = $this->thrift_bindProviderKey($unbound_row['ProviderAccount']['id'], $deviceId);
			$unbound_row['ProviderAccount'] = $paData['ProviderAccount'];
			return $unbound_row;
		}
		return array();
	}
	/**
	 * for the nativeUploader Auth managerment, the authToken is issued BEFORE
	 * the providerKey/DeviceID is known. This binds the 2 values on first login
	 * from the Thrift API
	 */
	function thrift_bindProviderKey($paid, $providerKey) {
		$this->id = $paid;
		$ret = $this->saveField('provider_key',$providerKey);
		return $this->read(null, $paid);
	}
	
	function getByOwner($owner_id, $options) {
		
	}
	
	function getPaginateProviderAccountsByUserId ($userid , $paginate = array()) {
		$controllerClass = Configure::read('controller.class');
		$paginateModel = 'ProviderAccount';
//debug($paginateModel);	 
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for GroupId
		$conditions = $joins = array();
		
		$conditions[] = array("`ProviderAccount`.user_id" => $userid);
		
		// check of context == controller
		$skip = $context['keyName']  == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if ($context['keyName']  == 'Person') {
				// skip
			}
			if ($context['keyName']  == 'Group') {
				// Asset habtm Group
				$joins[] =  array(
						'table'=>'assets_groups',
						'alias'=>'AssetsGroup',
						'type'=>'INNER',
						'conditions'=>array("AssetsGroup.asset_id=`Asset`.id", "AssetsGroup.group_id"=>$context['uuid']),
				);				}
			if ($context['keyName']  == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Asset`.id AND `Tagged`.`model` = 'Asset'"),
					);
				$joins[] =	array(
							'table'=>'tags',
							'alias'=>'Tag',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`tag_id` = `Tag`.id AND `Tag`.`keyname` = '{$context['uuid']}'"),
					);
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;
	}
	
	function getPaginateProviderAccountsByGroupId ($groupid , $paginate = array()) {
		$paginateModel = 'ProviderAccount';
//debug($paginateModel);	 
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for GroupId
		$conditions = $joins = array();
		
		$joins[] = array(
			'table'=>'groups_provider_accounts',
			'alias'=>'GroupsProviderAccount',
			'type'=>'INNER',
			'conditions'=>array("`GroupsProviderAccount`.`provider_account_id` = `ProviderAccount`.`id`", 
								"`GroupsProviderAccount`.`group_id`" => $groupid),
		);
		
		// check of context == controller
		$skip = $context['keyName']  == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if ($context['keyName']  == 'Person') {
				//groups/photos
				$conditions[] = "`Asset`.owner_id='{$context['uuid']}'";
			}
			if ($context['keyName']  == 'Group') {
				// skip
			}
			if ($context['keyName']  == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Asset`.id AND `Tagged`.`model` = 'Asset'"),
					);
				$joins[] =	array(
							'table'=>'tags',
							'alias'=>'Tag',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`tag_id` = `Tag`.id AND `Tag`.`keyname` = '{$context['uuid']}'"),
					);
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;
	}
	
	function appendFilterConditions($options, $conditions) {
		return $conditions;
	}

}
?>