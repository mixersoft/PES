<?php
/**
 * Users Users Controller
 *	- Note: This class uses code borrowed from the CakeDC Users plugin
 * @package users
 * @subpackage users.controllers
 */
class UsersPluginController extends AppController {

/**
 * beforeFilter callback
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
//		$this->Auth->allow('register', 'reset', 'verify', 'logout', 'index', 'view', 'reset_password');

		if ($this->action == 'register') {
			$this->Auth->enabled = false;
		}

//		if ($this->action == 'login') {
//			$this->Auth->autoRedirect = false;
//		}

		$this->set('model', $this->modelClass);

		if (!Configure::read('email.noreply')) {
			Configure::write('email.noreply', 'noreply@' . env('HTTP_HOST'));
		}
	}

	
/**
 * Sends the verification email
 *
 * This method is protected and not private so that classes that inherit this
 * controller can override this method to change the varification mail sending
 * in any possible way.
 *
 * @param string $to Receiver email address
 * @param array $options EmailComponent options
 * @return boolean Success
 */
	protected function _sendVerificationEmail($to = null, $options = array()) {
		$from = Configure::read('email.noreply');
		$defaults = array(
			'from' => $from,
			'subject' => __d('users', 'Verify your Snaphappi email address', true),
			'template' => 'email_verification');

		$options = array_merge($defaults, $options);
		$this->Email = loadComponent('Email', $this);
		$this->Email->to = $to;
		$this->Email->from = $options['from'];
		$this->Email->subject = $options['subject'];
		$this->Email->template = $options['template'];
//		$this->Email->delivery = 'debug';
//		return $this->Email->send();
		return $this->_sendOverSMTP();
	}

/**
 * Checks if the email is in the system and authenticated, if yes create the token
 * save it and send the user an email
 *
 * @param boolean $admin Admin boolean
 * @param array $options Options
 * @return void
 */
	protected function _sendPasswordReset($admin = null, $options = array()) {
		$from = Configure::read('email.noreply');
		$defaults = array(
			'from' => $from,
			'subject' => __d('users', 'Reset Password Request', true),
			'template' => 'password_reset_request');

		$options = array_merge($defaults, $options);
		if (!empty($this->data)) {
			$this->Email = loadComponent('Email', $this);
			$user = $this->User->passwordReset($this->data);
			if (!empty($user)) {
				$this->set('token', $user['Profile']['password_token']);
				$this->Email->to = $user[$this->modelClass]['email'];
				$this->Email->from = $options['from'];
				$this->Email->subject = $options['subject'];
				$this->Email->template = $options['template'];
//				$this->Email->delivery = 'debug';
//				$this->Email->send();
				$this->_sendOverSMTP();
				if ($admin) {
					$this->Session->setFlash(sprintf(
						__d('users', '%s has been sent an email with instruction to reset their password.', true),
						$user[$this->modelClass]['email']));
					$this->redirect(array('action' => 'index', 'admin' => true));
				} else {
					$this->Session->setFlash(__d('users', 'You should receive an email shortly to help reset your password.', true));
					$this->redirect($this->Auth->loginAction);
				}
			} else {
				$this->Session->setFlash(__d('users', 'No user was found with that email.', true));
				$this->redirect('/users/reset_password');
			}
		}
		$this->render('request_password_change');
	}

/**
 * Sets the cookie to remember the user
 *
 * @param array Cookie properties
 * @return void
 * @access protected
 * @link http://api13.cakephp.org/class/cookie-component
 */
	protected function _setCookie($options = array()) {
		if (!isset($this->data[$this->modelClass]['remember_me'])) {
			$this->Cookie->delete($this->modelClass);
		} else {
			$validProperties = array('domain', 'key', 'name', 'path', 'secure', 'time');
			$defaults = array(
				'name' => 'rememberMe');

			$options = array_merge($defaults, $options);
			foreach ($options as $key => $value) {
				if (in_array($key, $validProperties)) {
					$this->Cookie->{$key} = $value;
				}
			}

			$cookie = array();
			$cookie[$this->Auth->fields['username']] = $this->data[$this->modelClass][$this->Auth->fields['username']];
			$cookie[$this->Auth->fields['password']] = $this->data[$this->modelClass][$this->Auth->fields['password']];
			$this->Cookie->write($this->modelClass, $cookie, true, '1 Month');
		}
		unset($this->data[$this->modelClass]['remember_me']);
	}

/**
 * This method allows the user to change his password if the reset token is correct
 *
 * @param string $token Token
 * @return void
 */
	protected function __resetPassword($token) {
		$user = $this->User->checkPasswordToken($token);
		if (empty($user)) {
			$this->Session->setFlash(__d('users', 'Invalid password reset token, try again.', true));
			$this->redirect(array('action' => 'reset_password'));
		}

		if (!empty($this->data)) {
			if ($this->User->resetPassword(Set::merge($user, $this->data))) {
				$this->Session->setFlash(__d('users', 'Password changed, you can now login with your new password.', true));
				$this->redirect($this->Auth->loginAction);
			}
		}

		$this->set('token', $token);
	}	
	
	
/**
 * Sets a list of languages to the view which can be used in selects
 * - from cakedc Users plugin
 * @param string View variable name, default is languages
 * @return void
 */
	protected function _setLanguages($viewVar = 'languages') {
		App::import('Lib', 'Utils.Languages');
		$Languages = new Languages();
		$this->set($viewVar, $Languages->lists('locale'));
	}

/**
 * Admin Index
 *
 * @return void
 */
	public function admin_index() {
		$this->Prg->commonProcess();
		$this->{$this->modelClass}->data[$this->modelClass] = $this->passedArgs;
		$parsedConditions = $this->{$this->modelClass}->parseCriteria($this->passedArgs);

		$this->paginate[$this->modelClass]['conditions'] = $parsedConditions;
		$this->paginate[$this->modelClass]['order'] = array($this->modelClass . '.created' => 'desc');

		$this->{$this->modelClass}->recursive = 0;
		$this->set('users', $this->paginate());
	}

/**
 * Admin view
 *
 * @param string $id User ID
 * @return void
 */
	public function admin_view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__d('users', 'Invalid User.', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->set('user', $this->User->read(null, $id));
	}

/**
 * Admin add
 *
 * @return void
 */
	public function admin_add() {
		if ($this->User->add($this->data)) {
			$this->Session->setFlash(__d('users', 'The User has been saved', true));
			$this->redirect(array('action' => 'index'));
		}
	}

/**
 * Admin edit
 *
 * @param string $id User ID
 * @return void
 */
	public function admin_edit($userId = null) {
		try {
			$result = $this->User->edit($userId, $this->data);
			if ($result === true) {
				$this->Session->setFlash(__d('users', 'User saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->data = $result;
			}
		} catch (OutOfBoundsException $e) {
			$this->Session->setFlash($e->getMessage());
			$this->redirect(array('action' => 'index'));
		}

		if (empty($this->data)) {
			$this->data = $this->User->read(null, $userId);
		}
	}

/**
 * Delete a user account
 *
 * @param string $userId User ID
 * @return void
 */
	public function admin_delete($userId = null) {
		if ($this->User->delete($userId)) {
			$this->Session->setFlash(__d('users', 'User deleted', true));
		} else {
			$this->Session->setFlash(__d('users', 'Invalid User', true));
		}

		$this->redirect(array('action' => 'index'));
	}

/**
 * Search for a user
 *
 * @return void
 */
	public function admin_search() {
		$this->search();
	}
	
		
/**
 * User register action
 * - from cakedc Users plugin
 * @return void
 */
	public function register() {
		if ($this->Auth->user()) {
			$this->Session->setFlash(__d('users', 'You are already registered and logged in!', true));
			$this->redirect('/');
		}
		$msg['success'] = __d('users', 'Your account has been created. You should receive an e-mail shortly to verify your email address.', true);
		if (!empty($this->data)) {
			$register_cfg = Configure::read('register');
			$this->data['User']['active'] = $register_cfg['active'];
			$user = $this->User->register($this->data, $register_cfg['email_verify']);
			if ($user !== false) {
				$this->set('user', $user);
				$this->_sendVerificationEmail($user[$this->modelClass]['email'], $user);
				$this->Session->setFlash($msg['success']);
				if ($register_cfg['auth_on_success'] && $register_cfg['active']) {
					$this->data['User']['password'] = $this->Auth->password($this->data['User']['password']); 
					$login_ok = $this->__loginUser($this->data);
				}
				$this->__continueToRedirect($register_cfg['success_redirect']);
			} else {
				unset($this->data[$this->modelClass]['password']);
				unset($this->data[$this->modelClass]['temppassword']);
				$msg = 'Your account could not be created for the reasons shown below. Please, try again';
				$errors = $this->User->invalidFields(); // contains validationErrors array
				if (!empty($errors)) {
					$msg = 'Your account could not be created for the following reasons:<ul>';					
				} else $msg = 'Your account could not be created. Please, try again.';
				
				$this->Session->setFlash(__d('users', $msg, true), 'default', array('class' => 'message warning'));
			}
		}

		$this->_setLanguages();
	}
	
/**
 * Confirm email action
 * - from cakedc Users plugin
 * @param string $type Type
 * @return void
 */
	public function verify($type = 'email') {
		if (isset($this->passedArgs['1'])){
			$token = $this->passedArgs['1'];
		} else {
			$this->redirect($this->Auth->loginAction, null, true);
		}

		if ($type === 'email') {
			$data = $this->User->validateToken($token);
		} elseif($type === 'reset') {
			$data = $this->User->validateToken($token, true);
		} else {
			$this->Session->setFlash(__d('users', 'The link you accessed has already expired.', true));
			$this->redirect('/');
		}

		if ($data !== false) {
			$email = $data[$this->modelClass]['email'];
			unset($data[$this->modelClass]['email']);

			if ($type === 'reset') {
				$newPassword = $data[$this->modelClass]['password'];
				$data[$this->modelClass]['password'] = $this->Auth->password($newPassword);
			}

			if ($type === 'email') {
				$data[$this->modelClass]['active'] = 1;
			}
			$this->User->id = $data['User']['id'];
			if ($this->User->save($data, false)) {
				if ($type === 'reset') {
					$this->Email = loadComponent('Email', $this);
					$this->Email->to = $email;
					$this->Email->from = Configure::read('email.noreply');
					$this->Email->replyTo = Configure::read('email.noreply');
					$this->Email->return = Configure::read('email.noreply');
					$this->Email->subject = env('HTTP_HOST') . ' ' . __d('users', 'Password Reset', true);
					$this->Email->template = null;
					$content[] = __d('users', 'Your password has been reset', true);
					$content[] = __d('users', 'Please login using this password and change your password', true);
					$content[] = $newPassword;
					$this->Email->send($content);
					$this->Session->setFlash(__d('users', 'Your password was sent to your registered email account', true));
					$this->redirect($this->Auth->loginAction);
				} else {
					$this->User->Profile->id = $data['Profile']['id'];
					$this->User->Profile->save($data);
					$this->Session->setFlash(__d('users', 'Your e-mail has been verified!', true));
					$this->redirect($this->Auth->loginAction);
				}
			} else {
				$this->Session->setFlash(__d('users', 'There was an error trying to verify your e-mail address. Please check your e-mail for the link you should use to verify your e-mail address.', true));
				$this->redirect('/');
			}
		} else {
			$this->Session->setFlash(__d('users', 'The link you accessed has already expired.', true));
			$this->redirect('/');
		}
	}

	
/**
 * Allows the user to enter a new password, it needs to be confirmed
 * - from cakedc Users plugin
 * @return void
 */
	public function change_password() {
		if (!empty($this->data)) {
			$this->data[$this->modelClass]['id'] = $this->Auth->user('id');
			if ($this->User->changePassword($this->data)) {
				$this->Session->setFlash(__d('users', 'Password changed.', true));
				$this->redirect('/');
			}
		}
	}
	

/**
 * Reset Password Action
 * - from cakedc Users plugin
 * Handles the trigger of the reset, also takes the token, validates it and let the user enter
 * a new password.
 *
 * @param string $token Token
 * @param string $user User Data
 * @return void
 */
	public function reset_password($token = null, $user = null) {
		if (empty($token)) {
			$admin = false;
			if ($user) {
				$this->data = $user;
				$admin = true;
			}
			$this->_sendPasswordReset($admin);
		} else {
			$this->__resetPassword($token);
		}
	}
}







/**
 * Snaphappi Users Controller
 * 
 * 
 */
class UsersController extends UsersPluginController {
	public $name = 'Users';
	public $titleName = 'People';
	public $displayName = 'Person';	// section header
	
	public $layout = 'snappi-guest';
	
	public $components = array(
		'Permissionable.Permissionable',
	);
	
	public $helpers  = array(
		'Tags.TagCloud',
		'Time','Text',
		// 'CastingCallJson',
//		'Js' => array('Jquery'),
	);
	
//	public $components = array('Auth', 'Session', 'Email', 'Cookie', 'Search.Prg');
	
	/*
	 * reference: http://www.studiocanaria.com/articles/cakephp_auth_component_users_groups_permissions_revisited
	 */
	function beforeFilter() {
		// only for snaphappi user login, not rpxnow
		if (in_array($this->action, array('signin', 'login')) && !empty($this->data['User'])){
			//Override default fields used by Auth component
			$this->Auth->userModel = 'User';
			//Extend auth component to include authorisation via isAuthorized action
			$this->Auth->authorize = 'controller';
			//Restrict access to only users with an active account
			$this->Auth->userScope = array('User.active = 1');
			
			if (strpos($this->data['User']['username'],'@')){
				// use email for auth
				$this->data['User']['email'] = $this->data['User']['username'];
				unset($this->data['User']['username']);
				$this->Auth->fields = array('username'=>'email', 'password'=>'password');
			} else {
				// use username for auth
				$this->Auth->fields = array('username'=>'username', 'password'=>'password');
			}
		}
		$this->Auth->loginAction = '/users/signin';
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$this->Auth->allow(			
			/*
			 * main
			 */
			'signout', 'signin', 'checkauth',
			'logout', 'login', 
			/*
			 * from Users.Users plugin
			 */
			'register', 'reset', 'verify', 'reset_password', 'resend_email_verify', 
			/*
			 * experimental
			 */
			 'addACLs', 'odesk_photos', 'update_count', 'rpxlogin', 'openidlogin', 'checkPermission'
		);
		// TODO: edit allowed for  'role-----0123-4567-89ab---------user'
		// TODO: groups allowed for  'role-----0123-4567-89ab--------guest', 'role-----0123-4567-89ab---------user'
		AppController::$writeOk = AppController::$ownerid  == AppController::$uuid || AppController::$userid == Permissionable::getRootUserId();
		if (get_class($this) == 'UsersController') Session::delete("nav.primary");
	}

	function updateExif($uuid) {
		set_time_limit (600);
		return parent::__updateExif($uuid);
	}
	function update_count($id = null) {
		$this->autoRender = false;
		if (!Permissionable::isRoot() && $id===null) {
			echo "Root permission required.";
			exit;
		}
		if ($id) {
			$ret = $this->User->updateCounter($id);
			$this->__setRandomBadgePhoto($id);
		} else {
			$this->User->updateAllCounts();
			$this->__setRandomBadgePhoto();
		}
		if ($id) $this->redirect(array('controller'=>'person','action'=>'home', $id));
		else $this->redirect('/person/all');
	}
	function __setRandomBadgePhoto($id = null){
		if ($id) {
			$this->User->setRandomBadgePhoto($id);
		} else {
			$options = array(
				'recursive' => -1, 
				'fields'=>array('User.id', 'User.src_thumbnail'),
			);
			if ($id) $options['conditions']['User.id'] = $id;
			$data = $this->User->find('all', $options);
			// $gids = Set::extract('/User/id', $data);
			$wwwroot = Configure::read('path.wwwroot');
			foreach ($data as $row) {
				if (!empty($row['User']['src_thumbnail'])) {
					// check if file exists
					$imgFilepath = $wwwroot.Stagehand::getSrc($row['User']['src_thumbnail'], '');
					if (file_exists($imgFilepath)) {
						continue; 	// already set and valid, skip
					} 
				} 
				$uid = $row['User']['id'];
				$this->User->id = $uid;
				$sql = "
	SELECT Asset.src_thumbnail, SharedEdit.score, Asset.id
	FROM assets Asset
	LEFT JOIN shared_edits AS `SharedEdit` ON (`SharedEdit`.`asset_id` = `Asset`.`id`)
	WHERE Asset.owner_id='{$uid}'
	order by score desc
	LIMIT 5;";
				$asset = $this->User->query($sql);
				if ($asset) {
					$srcs = Set::extract('/Asset/src_thumbnail', $asset);
					shuffle($srcs);
					$ret = $this->User->saveField('src_thumbnail', array_shift($srcs));
				}
			}
		}	
		return;
	}

	
	/*
	 * WARNING: backdoor action for oDesk project
	 */
	function odesk_photos (){
		$id = $this->passedArgs[0];
		if (strpos($id, '12345678') === 0) {
			$data = $this->User->read(null, $id );
			$ret = $this->Auth->login($data);
			$this->__cacheAuth();
			$this->Permissionable->initialize($this);
			$this->action='photos'; 
			$this->photos($id);
		} else {
			exit;
		}
	}	

	
	function beforeRender() {
		parent::beforeRender(); 
	}
	
	/**
	 * config SMTP Server for sending emails
	 * 	- used by code borrowed from CakeDC Users plugin 
	 * @return unknown_type
	 */
	protected function _sendOverSMTP(){
	   /* SMTP Options */
	   $this->Email->smtpOptions = Configure::read('email.auth');

		/* Set delivery method */
		switch (env('SERVER_NAME')) {
			case 'preview.snaphappi.com':
			case 'dev.snaphappi.com':
				$this->Email->delivery = 'smtp';
				break;
			default:
				$this->Email->delivery = 'smtp';
				// $this->Email->delivery = 'debug';
				break;		
		}	   
		    
		/* Do not pass any args to send() */
		$this->Email->send();
		
		/* Check for SMTP errors. */
		$this->set('smtp_errors', $this->Email->smtpError);
	}
	
	
	/**
	 * reset Session 'Auth' key used by Auth component
	 *
	 * @params boolean - save Auth.redirect for subsequent retry
	 */
	function __reset($saveRedirect = false) {
		if ($saveRedirect) $redirect = $this->Session->read('Auth.redirect');
		$this->Auth->logout();
		$this->Session->delete();
		if ($saveRedirect) $this->Session->write('Auth.redirect', $redirect);
	}

	function __continueToRedirect($default=null) {
		if ($default===null) $default = $this->Auth->loginRedirect;
		$redirect = @if_e($this->Session->read('Auth.redirect'), $default);
		$this->Session->delete('Auth.redirect');
		$this->Session->setFlash(null, null, null, 'auth');
		$this->redirect($redirect, null, true);
	}

	function __createGuestSession($uuid=null) {
		
		$found = $this->User->findById($uuid);
		if ($found) {
			$this->log("creating RETURNING Guest session for guest id={$uuid}", LOG_DEBUG);
			return $found;
		}
		
		$this->__reset($saveRedirect = true);
		// clear Auth Error before retry
		$this->Session->setFlash(null, null, null, 'auth');
		$data = array('User'=>array());
		$uuid = $uuid ? $uuid : String::uuid();
		$data['User']['id'] = $uuid;
		$data['User']['username'] = $uuid;
		$data['User']['password'] = $uuid;
		$data['User']['primary_group_id'] = 'role-----0123-4567-89ab--------guest';
/*
 * give guest "user" role for testing
 */
// $data['User']['primary_group_id'] = 'role-----0123-4567-89ab---------user';
		$data['User']['privacy'] = 0;
		$data['User']['active'] = 1;
		$this->User->create();
		$hashedPwds = $this->Auth->hashPasswords($data);
		$ret = $this->User->save($hashedPwds);
		if (!$ret) {
			debug("ERROR: this->User->save(), data=".print_r($data, true));
			debug($this->User->validationErrors); 
			return false;
		} else {
			// insert default profile row on new user
			$this->__createDefaultProfile($this->User->id);
			$this->log("creating NEW Guest session for guest id={$uuid}", LOG_DEBUG);
		}
		$hashedPwds['User']['id'] = $this->User->id;
		
		/*
		 * save as Cookie
		 */
		App::import('Component', 'Cookie');
		$Cookie = new CookieComponent($this);
		$Cookie->write('guest_pass', $this->User->id, false, '2 week');
		/*
		 * end save cookie
		 */
		return $ret;
	}

	function __createDefaultProfile($id){
		$data['Profile']['user_id'] = $id;
		return $this->User->Profile->save($data);
	}

	function __loginUser($data) {
		$ret = $this->Auth->login($data);
		if (!$ret) {
			$this->log("ERROR: this->Auth->login(), data=".print_r($data, true), LOG_DEBUG);
			return false;
		} else {
			// $data = $this->Auth->user();
		}
		// get valid user, Profile data after auth ok;
		$options = array(
			'contain'=>array('Profile'),
			'conditions'=>array('User.id'=>$this->Auth->user('id')),
			'recursive'=>0);
		$data = $this->User->find('first', $options);
		// authorize User data. set Auth.Permissions
		$ret = $ret && parent::isAuthorized();
		// update lastVisit
		$this->User->id = $data['User']['id'];
		$this->User->saveField('lastVisit', date('Y-m-d H:i:s',time()), false);
		$this->User->saveField('last_login', date('Y-m-d H:i:s',time()), false);
		$ret = $ret && $this->__initProfile($data);	// for testing
		return $ret;
	}


	function __initProfile($data) {
		/*
		 * Temp function to initialize Profie
		 */
		$puid = !empty($data['Profile']['user_id']) ? $data['Profile']['user_id'] : null;
		if (!$puid) {
			$data['Profile'] = array(
				'privacy_assets'=> 519,			// public
				'privacy_groups'=> 567,			// public/members
			);
			$data['Profile']['user_id'] = $data['User']['id'];
			if ($this->User->Profile->save($data)) {
				return true;
			} else {
				$this->Session->setFlash('ERROR: problem initializing User Profile');
				return false;
			}
		}
		return true;
	}
	function register() {
		// $this->layout = 'snappi-aui-960';
		$forceXHR = setXHRDebug($this, 0, 1);		// xhr login is for AIR desktop uploader
		$isJson =  ($this->RequestHandler->ext == 'json');
		$success = false; $message = $response = array();
		/**
		 * parent::register();
		 */
		if ($this->Auth->user() && isset($this->params['url']['min'])) {
			$success = true;
			$message = __d('users', 'You are already registered and logged in!', true);
			$user = $this->Auth->user();
			unset($user['User']['password']);
			$response = $user;
			$isJson = true;
		} else if ($this->Auth->user() && !$isJson) {
			$this->Session->setFlash(__d('users', 'You are already registered and logged in!', true));
			$this->redirect('/');
		}
		if (!empty($this->data)) {
			$msg['success'] = __d('users', 'Your account has been created. You should receive an e-mail shortly to verify your email address.', true);
			$register_cfg = Configure::read('register');
			$this->data['User']['active'] = $register_cfg['active'];
			$user = $this->User->register($this->data, $register_cfg['email_verify']);
			if ($user !== false) {
				$this->set('user', $user);
				$this->_sendVerificationEmail($user[$this->modelClass]['email'], $user);
				if ($register_cfg['auth_on_success'] && $register_cfg['active']) {
					$this->data['User']['password'] = $this->Auth->password($this->data['User']['password']); 
					$login_ok = $this->__loginUser($this->data);
				}
				if ($this->Auth->user() && $isJson) {
					$success = true;
					$message = $msg['success'];
					$user = $this->Auth->user();
					unset($user['User']['password']);
					$response = $user;
				} else {
					$this->Session->setFlash($msg['success']);
					$this->__continueToRedirect($register_cfg['success_redirect']);
				}
			} else {
				unset($this->data[$this->modelClass]['password']);
				unset($this->data[$this->modelClass]['temppassword']);
				$msg = 'Your account could not be created for the reasons shown below. Please, try again';
				$errors = $this->User->invalidFields(); // contains validationErrors array
				if (!empty($errors)) {
					$msg = 'Your account could not be created for the following reasons:<ul>';					
				} else $msg = 'Your account could not be created. Please, try again.';
				
				if ($isJson) {
					$success = false;
					$message = $msg;
					$response = compact('errors');
				} else {
					$this->Session->setFlash(__d('users', $msg, true), 'default', array('class' => 'message warning'));
				}
			}
		}

		$this->_setLanguages();
		
		if ($isJson) $this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json', null, null, $forceXHR);
		if (!$done) {
			if (isset($this->params['url']['min'])) {
				$this->Session->write('Auth.redirect', '/my/upload');
				$this->render('register_thatsme', 'thatsme-iframe');
			} else {
				$this->render('register', 'snappi-guest');
			}
		}		
	}
	
	/*
	 * special method to check current user auth from iframe
	 * sets document.domain = 'snaphappi.com' for same origin policy
	 */
	function checkauth() {
		$auth = $this->Auth->user();
		unset($auth['User']['password']);
		$this->set('auth',$auth);
		$this->layout='plain';
	}
	/**
	 * NOTE: to verify current user, use /users/signin/.json?&forcexhr=1&debug=2
	 * 	- /thats-me/users must use /users/checkauth + same origin policy in iframe to connect to session
	 */
	function signin() {
		$done = $this->login();
		if (!$done) {
			if (isset($this->params['url']['min'])) {
				$this->Session->write('Auth.redirect', '/my/upload');
				$this->render('signin-thatsme', 'thatsme-iframe');
			} else {
				$this->render('login', 'snappi-guest');
			}
		}
	}
	
	function login() {
		$this->layout = 'snappi-guest';
		$forceXHR = setXHRDebug($this, 0, 0);		// xhr login is for AIR desktop uploader
		$isJson =  ($this->RequestHandler->ext == 'json');
		$success = false; $message = $response = array();
		
		if($forceXHR && !empty($this->data['User']['password'])){
			// salt password
			$this->data['User']['password'] = $this->Auth->password($this->data['User']['password']);
		}
		
		$allow_guest_login = Configure::read('AAA.allow_guest_login');
		if ($allow_guest_login) {
			/*
			 * get or create Cookie on server for guest_pass
			 */
			App::import('Component', 'Cookie');
			$Cookie = new CookieComponent($this);
			$cookie_guest_pass = $Cookie->read('guest_pass');
			if (empty($cookie_guest_pass)) {
				$cookie_guest_pass = String::uuid();
// debug('issuing new cookie guest_pass='. $cookie_guest_pass);				
			}
			// create/write Cookie in XHR POST request (1 step)
			if (isset($this->data['User']['guest_pass']) 
				&& ($this->RequestHandler->isAjax() || $forceXHR)) {
// debug('Auth.guest_pass='. $cookie_guest_pass);				
				$this->Session->write('Auth.guest_pass', $cookie_guest_pass);
				// extend cookie another 2 weeks
				$Cookie->write('guest_pass', $cookie_guest_pass, false, '2 week');	
			}
			$this->set('guestpass', $cookie_guest_pass);
		}
		$method = isset($this->data) ? 'POST' : 'GET';
		$done = false;
		switch ($method) {
		case 'GET':
			/*
			 * GET method
			 */	
		 	if ($allow_guest_login && isset($this->params['url']['optional'])) {
		 		$this->Session->write('Auth.guest_pass', $cookie_guest_pass);
				$Cookie->write('guest_pass', $cookie_guest_pass, false, '2 week');
				$this->set('cookie_guest_pass', $cookie_guest_pass); // used in views/users/login.ctp
		 	}
			$rpxTokenUrl = htmlentities(Configure::read('ApiKey.rpxnow.signin'));
			$this->set(compact('isAuth', 'permissions', 'rpxTokenUrl'));
	
			// NOTE: for snappi-dev: use ?optional to allow guest sign in, 
			if (Configure::read('AAA.allow_magic_login')) {
				$userlist = $this->User->find('list', array('fields'=>'User.username', 'conditions'=>array("id!=username")));
				$this->set('userlist', $userlist);
			}
			// XHR GET returns status of current auth user
			if ($this->RequestHandler->isAjax() || $forceXHR) {
				$auth = $this->Auth->user();
				$response['success'] = !empty($auth);
				$response['message'] = !empty($auth) ? "Current authenticated user, username={$auth['User']['username']}" : 'authentication failed';
				unset($auth['User']['password']);
				$response['response'] = $auth;
				// $response['Cookie'] = $Cookie->read();
				$this->viewVars['jsonData'] = $response;
				$done = $this->renderXHRByRequest('json', null, null, $forceXHR);
			} else {
				// not done
				debug("login GET ");				
			}
			return $done;
				
		case 'POST':
			
debug($this->data);			
$this->log($this->data, LOG_DEBUG);
			/*
			 * POST method
			 * local user registration not yet implemented
			 */
			$login_ok = false;
			$response = array();
			// no username => check/set guest_pass
			if ($allow_guest_login  && empty($this->data['User']['username'])) {
				$guest_pass = isset($this->data['User']['guest_pass']) ? $this->data['User']['guest_pass'] : false ;
			}

			/*
			 * prepare postData for login
			 * Note: magic logins for DEV overwrite $guest_pass logins
			 */
			 
			 
			 // magic logins for DEV only
			if (empty($this->data['User']['username']) && !empty($this->data['User']['magic'])) {
$this->log("check magic/cookie signin for user=", LOG_DEBUG);
				$this->User->recursive=0;
				/*
				 * for magic logins
				 */
				if (strlen($this->data['User']['magic'])==36){
					$this->data = $this->User->read(null, $this->data['User']['magic'] );
					$this->log("magic signin for user={$this->data['User']['username']}", LOG_DEBUG);
					// continue below
				}
			}
			// $this->data['User']['username'] will be set if id is valid
			if (empty($this->data['User']['username']) && $allow_guest_login) {
				/*
				 * NEW Guest login
				 */					
				// confirm guest_pass was issued by /users/signin?optional
				// use magic login value, if available, if not, read from Session
				if ($guest_pass == $this->Session->read('Auth.guest_pass')) {
					$this->Session->delete('Auth.guest_pass');
					// create Guest Session, as necessary, and continue
					$this->data = $this->__createGuestSession($guest_pass);
					$login_ok = $this->__loginUser($this->data);
					if ($login_ok == false) {
						// login failed
						$this->Session->delete('Auth.guest_pass');
						$this->data['User']['password']='';
						$message =  "Sorry, your guest pass was invalid. Please register as a User.";
					} else {
						$success = true;
						$message = "You are signed in as Guest. Please don't delete your browser cookie.";
					}
				} else {
					// spoofed guest pass???
					$this->__reset();
					$Cookie->destroy('guest_pass');
					$message =  "There was a problem with your guest pass, it does not match our records.";
				}
			} else {
				/*
				 * user, guest or magic login
				 */
				// delete existing Auth session before we try to authenticate a new user
// $this->log("after /users/signin for user={$this->data['User']['username']}", LOG_DEBUG);		
// $this->log($this->data['User'], LOG_DEBUG);
// $this->log($_COOKIE, LOG_DEBUG);	
// $this->log("response cookie value above. ", LOG_DEBUG);
				$this->__reset($saveRedirect = true);
				$login_ok = $this->__loginUser($this->data);
				$this->data = $this->Auth->user();
// $this->log($this->data['User'], LOG_DEBUG);				
				if ($login_ok == false) {
					// login failed
					$this->data['User']['password']='';
					// check for email authorized token
					if ($this->User->checkIfPendingValidation($this->data)) {
						$message = "This account has not yet been activated. Please check your email to activate.";
					} else {
						$message = "The username and password did not match. Please try again.";
						$errors['password'] = "The username and password did not match.";
					}
					$success = false;
					$response = compact ('errors');
				} else {
					$success = true;
					$message = "Welcome back!";
					$this->log("   >>> Successful sign-in for user={$this->data['User']['username']}", LOG_DEBUG);
				}
			}
			
			/*
			 * set jsonData for XHR login, used by AIR
			 * deprecate
			 */
			if ($isJson || $this->RequestHandler->isAjax() || $forceXHR) {
				if ($success) {
					$user = $this->Auth->user();
					unset($user['User']['password']);
					$response = $user;
				}
				$this->viewVars['jsonData'] = compact('success', 'message', 'response');
				// $this->viewVars['jsonData']['Session.Config'] = $_SESSION['Config'];
				// $this->viewVars['jsonData']['Cookie'] = $_COOKIE;
				$done = $this->renderXHRByRequest('json', '/elements/users/signin', null, $forceXHR);				
			} else {
				/*
				 * respond to POST, HTTP only
				 */ 
				$this->Session->setFlash($message);	
				if ($login_ok) $this->__continueToRedirect();
			}
			return $done;
		}		// END POST
	}
	function signout() {
		$this->logout();
		$this->render('logout');
	}
	function logout() {
		/*
		 * delete everything
		 */
		$this->__reset();
		$this->Session->setFlash('You are now signed out.');
		$this->Session->destroy();
		$this->redirect($this->Auth->logout());
	}
	
	function reset_password($token = null, $user = null) {
		$this->layout = 'snappi-plain';
		parent::reset_password($token, $user);
	}
	
	function change_password() {
		$this->layout = 'snappi';
		parent::change_password();
	}

	/**
	 * Check authorization by group
	 * params:
	 * 	checkPermission.xml?aro=[access request object], lookup required permissions
	 * @return xml returns <auth status='[0 | 1]' /> depending on authorization status
	 */
	function checkPermission() {
		if ($this->RequestHandler->isXML()) {
			// setup xml response
			Configure::write('debug', 0);
			$this->header('Content-Type: text/xml');
			Controller::disableCache();
			$this->layout = 'xml';


			// /users/checkPermission.xml?aro=snappi-gallery
			$aro = @if_e($this->params['url']['aro'], null);
			switch ($aro) {
				case 'snappi-gallery':
					$permissions = 'guest,user,admin,';
					break;
				default:
					$permissions = null;
					break;
			}
			$thisGroups = $this->User->find(array('User.id'=>$this->Auth->user('id')));
			$ok = 0;
			foreach ($thisGroups['AuthGroup'] as $group) {
				if (strpos($permissions, $group['name']) !== false) {
					$ok = 1;
					break;
				}
			}
			$this->set('isAuthorized', $ok);
		} else {
			$permissions = $this->Session->read('Auth.Permissions');
			$isAuth = $this->isAuthorized() ? "true" : "false";
			$this->set(compact('isAuth', 'permissions'));
		}
	}

	function users_only(){
		$this->autoRender=false;
		debug($this->Session->read('Auth.Permissions'));
	}

	function index() {
		$this->redirect(array('controller'=>'person', 'action'=>'all'));
	}

	function __getPrivacyConfig(){
		/*
		 * configure privacy defaults for radio buttons
		 */
		// The Photos and Page Galleries I import, upload or create:
		$privacy['Asset'][519]="<b>Public</b> - are publicly listed and visible to anyone.";
		$privacy['Asset'][71]="<b>Members only </b> - are NOT publicly listed, and are visible only when shared in Groups or Events, and only by Group members.";
		$privacy['Asset'][7]="<b>Private</b> - are NOT publicly listed and visible only to me.";

		// The Groups and Events I create:
		$privacy['Groups'][567]="<b>Public</b> - are publicly listed and visible to anyone. Contributors are also asked to make their shared Content <b>Public</b>.";
		$privacy['Groups'][631]="<b>Public Listings Only</b> -  are publicly listed and visible to anyone, but shared Content is visible to Group members only.";
		$privacy['Groups'][119]="<b>Members only</b> - are NOT listed, and visible to Group members only.";
		$privacy['Groups'][63]="<b>Admin</b> - are NOT listed, and visible only to me and other Group admins.";

		$privacy['SecretKey'][1]="Members - are visible to all site members.";
		$privacy['SecretKey'][2]="Admins - are visible only to Owners and Admins ";
		$privacy['SecretKey'][4]="Nobody - disable Secret Key sharing";
		return $privacy;
	}


	function __getModeratorConfig(){
		/*
		 * configure moderator defaults for radio buttons
		 */
		$moderator['Comments'][1]="Public - anyone can add Comments";
		$moderator['Comments'][2]="Registered Members - only site members can add Comments.";
		$moderator['Comments'][4]="Nobody - disable Commenting feature.";
		$moderator['Tags'][1]="Public - anyone can add Tags";
		$moderator['Tags'][2]="Registered Members - only site members can add Tags.";
		$moderator['Tags'][4]="Admins - only I or my designated Group/Event admins can add Tags.";
		$moderator['Tags'][8]="Nobody - disable Tagging feature.";
		return $moderator;
	}

	function admin_settings($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'user'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!AppController::$writeOk) {
			$this->Session->setFlash(__('You are not authorized to view this page'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		
		if (!empty($this->data)) {
			/*
			 * redirect to edit with setting=[form id]
			 */
			$qs = @http_build_query(array('setting'=>$this->data['User']['setting']));
			$redirect = Router::url(array('action'=>'edit', $id)). ($qs ? "?{$qs}" : '');
			$this->redirect($redirect, null, true);
		}
		$privacy = $this->__getPrivacyConfig();
		$moderator =  $this->__getModeratorConfig();
		$this->set(compact('privacy', 'moderator'));
		
		
		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$id));
		$data = $this->User->find('first', $options);
		
		$this->data = $data;
		$this->set('data', $data);
		$xhrFrom = Configure::read('controller.xhrFrom');
		if ($xhrFrom) {
			$viewElement = '/elements/users/'.$xhrFrom['view'];
		} else $viewElement = null;
		$done = $this->renderXHRByRequest(null, $viewElement, 0);		
		return;
	}

	//TODO: insert default profile row on new user
	function add() {
		if (!empty($this->data)) {
			$this->User->create();
			if ($this->User->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'user'));
				// insert default profile row on new user
				$this->__createDefaultProfile($this->User->id);
				$this->redirect(array('action' => 'settings'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'user'));
			}
		}
	}

	function edit($id = null) {
		if (0 || $this->RequestHandler->isAjax()) {
			Configure::write('debug', 0);
			$this->layout='ajax';
			$this->autoRender=false;
		}			
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'user'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!AppController::$writeOk) {
			$this->Session->setFlash(__('You are not authorized to view this page'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!empty($this->data)) {
			/*
			 * update
			 */
			// check role permissions
			$userid = AppController::$userid;
			if ( $userid== $id || in_array(AppController::$role, array('ADMIN', 'ROOT'))) {
				if (empty($this->data['User']['password'])) unset($this->data['User']['password']);
				if (empty($this->data['User'])) {
					$this->data['User']['id'] = $userid;
				}
				
				// redirect on success
				if ($this instanceof MyController) {
					$redirect = Router::url(array('action' => 'settings'));
				} else $redirect = Router::url(array('action' => 'settings', $userid));
				if (isset($this->data['Profile']['setting'])) {
					$tabParts = explode('-',$this->data['Profile']['setting']);
					Session::write('settings.tabName', $tabParts[1] );
					unset($this->data['Profile']['setting']);		
				}		
		
				
				$ret = 1;
				if (count($this->data['User'])>1) {
					$this->User->id = $this->data['User']['id'];
					$ret = $ret && $this->User->save($this->data, true, array_keys($this->data['User']));	
				}
				if (!empty($this->data['Profile'])) {
					$userid = $this->data['User']['id'];
					$options = array('fields'=>'Profile.id', 'recursive'=>-1,'conditions'=>array('Profile.user_id'=>$userid));
					$profile = $this->User->Profile->find('first', $options);
					$this->data['Profile']['id'] = @ifed($profile['Profile']['id'],null);
					$this->data['Profile']['user_id'] = $this->data['User']['id'];
					$ret = $ret && $this->User->Profile->save($this->data);
				}
				if ($ret) {
					$this->Session->setFlash(sprintf(__('Your %s settings have been saved', true), 'user'));
				} else {
					$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'user'));
				}
				if (0 || $this->RequestHandler->isAjax()) {
					return $ret;
				} else  {
					if ($redirect) $this->redirect($redirect, null, true);			
				}					
			}
			
		}
		/*
		 * diplay current values
		 */
		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$id));
		$data = $this->User->find('first', $options);

		if (isset($this->data['User']['password'])) unset($this->data['User']['password']);

		$privacy = $this->__getPrivacyConfig();
		$moderator =  $this->__getModeratorConfig();
		$this->set(compact('privacy', 'moderator'));

		//		$primaryGroups = $this->User->PrimaryGroup->find('list', array('conditions'=> array('PrimaryGroup.isSystem'=>1,"PrimaryGroup.id like 'role-%'")));
		//		$memberships = $this->User->Membership->find('list',array('conditions'=> array('Membership.isSystem'=>0)));
		//		$this->set(compact('primaryGroups', 'memberships'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'user'));
			$this->redirectSafe();
		}
		if ($this->User->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'User'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'User'));
		$this->redirect(array('action' => 'index'));
	}
	
	
/**
 * resend_emailVerification
 */
	public function resend_email_verify() {
		if ($this->data) {
			$user = $this->User->resendVerification($this->data);
			if ($user) {
				$this->set('user', $user);
				$this->_sendVerificationEmail($user[$this->modelClass]['email'], $user);	
				$this->Session->setFlash(__d('users', 'You should receive a new e-mail shortly to authenticate your account. Once validated you will be able to login.', true));
				$this->redirect($this->Auth->loginAction);
			}
		}
	}	
	
}






?>