<?php

class ShareLinksController extends AppController {
	public $layout = 'snappi-guest';
	
	public function test_links(){
		$TARGET_UUID = '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d';
		$OWNER_UUID = '12345678-1111-0000-0000-venice------';
		$this->set(compact('TARGET_UUID', 'OWNER_UUID'));
	}

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('*');
		$this->Auth->deny('view_login');
	}
		
	public function form_create($targetId, $ownerId, $securityHash) {
		if (empty($_GET['link'])) {
			$this->cakeError('error404');
		}
		$link = $_GET['link'];

		$securityCheck = Security::hash($targetId . $ownerId . $link);
		if ($securityCheck != $securityHash) {
			debug('Security check not passed');
			$this->cakeError('error404');
		}

		if (!empty($this->data)) {
			$linkData = array(
				'target_url' => $link,
				'target_id' => $targetId,
				'owner_id' => $ownerId,
				'security_level' => $this->data['ShareLink']['security_level'],
			);
			if ($this->data['ShareLink']['hashed_password']) {
				$linkData['hashed_password'] = $this->data['ShareLink']['hashed_password'];
			}
			if (!empty($this->data['ShareLink']['expiration_days'])) {
				$date = date("Y-m-d h:m:s");// current date
				$date = strtotime(date("Y-m-d h:m:s", strtotime($date)) . " +{$this->data['ShareLink']['expiration_days']} day");
				$date = date ( "Y-m-d h:m:s" , $date );
				$linkData['expiration_date'] = $date;
			}
			if (!empty($this->data['ShareLink']['expiration_count'])) {
				$linkData['expiration_count'] = $this->data['ShareLink']['expiration_count'];
			}
			$result = $this->ShareLink->createNew($linkData);
			if (is_array($result)) {
				$this->redirect(array('action' => 'created', $result['ShareLink']['id']));
			} else {
				$this->Session->setFlash('Error creating link');
			}
		} else {
			$this->data['ShareLink']['security_level'] = 1;
		}
		$this->set(compact(array('link', 'targetId', 'ownerId', 'securityHash')));
	}


	public function create() {
		$fields = array(
			'hashed_password', 'security_level', 'expiration_date',
			'expiration_count', 'target_id', 'target_type', 'target_owner',
			'active', 'owner_id', 'count',
		);
		$data = array();
		foreach ($fields as $field) {
			if (!empty($this->params['named'][$field])) {
				$data[$field] = $this->params['named'][$field];
			}
		}
		$result = $this->ShareLink->createNew($data);
		$this->set(array('result' => $result));
	}


	function created($id) {
		$shareLink = $this->ShareLink->findById($id);
		if (!$shareLink) {
			$this->cakeError('error404');
		}
		$this->set(compact('shareLink'));
	}


	function view($secretId) {
		$shareLink = $this->ShareLink->get($secretId);
		if (is_string($shareLink)) {
			$this->_renderError($secretId, $shareLink);
		} else {
			switch ($shareLink['ShareLink']['security_level']) {
				case ShareLink::$SECURITY_LEVEL['NONE']:
					$this->_redirectToTarget($shareLink);
				break;
				case ShareLink::$SECURITY_LEVEL['PASSWORD']:
					$this->redirect(array('action' => 'ask_password', $secretId));
				break;
				case ShareLink::$SECURITY_LEVEL['LOGIN']:
					$this->redirect(array('action' => 'view_login', $secretId));
				break;
			}
		}
	}


	function ask_password($secretId) {
		$shareLink = $this->ShareLink->get($secretId);
		$this->set(array('secretId' => $secretId));
		if (is_string($shareLink)) {
			$this->_renderError($shareLink, $shareLink);
		} else {
			if (!empty($this->data['ShareLink']['password'])) {
				if ($shareLink['ShareLink']['hashed_password'] == Security::hash($this->data['ShareLink']['password'])) {
					$this->_redirectToTarget($shareLink);
				} else {
					$this->Session->setFlash('Invalid password');
				}
			}
		}
	}


	function view_login($secretId) {
		$shareLink = $this->ShareLink->get($secretId);
		if (is_string($shareLink)) {
			$this->_renderError($secretId, $shareLink);
		} else {
			$this->_redirectToTarget($shareLink);
		}
	}


	function _redirectToTarget($shareLink) {
		$this->ShareLink->increaseCount($shareLink['ShareLink']['id']);
		$this->set(array('shareLink' => $shareLink));
		$this->redirect($shareLink['ShareLink']['target_url']);
	}


	function _renderError($secretKey, $errorCode) {
		$this->set(array(
			'secretKey' => $secretKey,
			'errorCode' => $errorCode,
		));
		if (in_array($errorCode, array('inactive', 'non-existent'))) {
			$this->render('error');
		} else {
			$this->render('expired');
		}
	}


	function ask_renewal($secretKey) {
		$comment = empty($this->data['ShareLink']['renewal_comment']) ? null : $this->data['ShareLink']['renewal_comment'];
		$result = $this->ShareLink->askRenewal($secretKey, $comment);
		switch ($result) {
			case 'not-expired':
				$msg = __('The renewal request was not sent because the link is not expired', true);
			break;
			case 'error-save':
				$msg = __('The renewal request could not be sent due to database error', true);
			break;
			default:
				$msg = __('Renewal request successfully sent', true);
			break;
		}
		$this->set(array('msg' => $msg));
	}


	function find() {
		$named = $this->params['named'];
		if (!empty($named['target_id'])) {
			$shareLinks = $this->ShareLink->getAllForTarget($named['target_id']);
		} elseif (!empty($named['owner_id'])) {
			$onlyWithRenealRequest = empty($named['filter_renewal']) ? false : true;
			$shareLinks = $this->ShareLink->getAllForOwner($named['owner_id'], $onlyWithRenealRequest);
		} else {
			debug('not target or owner provided');
			$this->CakeError('error404');
		}
		$this->set(array('shareLinks' => $shareLinks));
	}



}