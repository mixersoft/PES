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
			'subject' => __d('users', 'Account verification', true),
			'template' => 'account_verification');

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
			'subject' => __d('users', 'Password Reset', true),
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
					$this->Session->setFlash(__d('users', 'You should receive an email with further instructions shortly', true));
					$this->redirect(array('action' => 'login'));
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
		if (1 || !Session::check('Auth.redirect')){
			$msg['success'] .= " Why not get a jump on things by downloading the <a href='/pages/downloads' target='_blank'>Snaphappi Desktop Uploader</a>?";	
		}
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
			$this->redirect(array('action' => 'login'), null, true);
		}

		if ($type === 'email') {
			$data = $this->User->validateToken($token);
		} elseif($type === 'reset') {
			$data = $this->User->validateToken($token, true);
		} else {
			$this->Session->setFlash(__d('users', 'There url you accessed is not longer valid', true));
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
					$this->redirect(array('action' => 'login'));
				} else {
					$this->User->Profile->id = $data['Profile']['id'];
					$this->User->Profile->save($data);
					$this->Session->setFlash(__d('users', 'Your e-mail has been validated!', true));
					$this->redirect(array('action' => 'login'));
				}
			} else {
				$this->Session->setFlash(__d('users', 'There was an error trying to validate your e-mail address. Please check your e-mail for the URL you should use to verify your e-mail address.', true));
				$this->redirect('/');
			}
		} else {
			$this->Session->setFlash(__d('users', 'The url you accessed is not longer valid', true));
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
	
	public $helpers  = array(
		'Tags.TagCloud',
		'Time','Text',
		'CastingCallJson',
//		'Js' => array('Jquery'),
	);
	
//	public $components = array('Auth', 'Session', 'Email', 'Cookie', 'Search.Prg');
	
	/*
	 * reference: http://www.studiocanaria.com/articles/cakephp_auth_component_users_groups_permissions_revisited
	 */
	function beforeFilter() {
		// only for snaphappi user login, not rpxnow
		if ($this->action=='login' && !empty($this->data['User'])){
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
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$this->Auth->allow(			
			/*
			 * main
			 */
			'logout', 'login', 'rpxlogin', 'openidlogin', 'checkPermission',
			/*
			 * from Users.Users plugin
			 */
			'register', 'reset', 'verify', 'reset_password', 'resend_email_verify', 
			'change_password',	// add to ACLs
			/*
			 * experimental
			 */
			 'addACLs', 'odesk_photos', 'update_count'
		);
		// TODO: edit allowed for  'role-----0123-4567-89ab---------user'
		// TODO: groups allowed for  'role-----0123-4567-89ab--------guest', 'role-----0123-4567-89ab---------user'
		AppController::$writeOk = AppController::$userid  == AppController::$uuid || AppController::$userid == Permissionable::getRootUserId();
		if (get_class($this) == 'UsersController') Session::delete("nav.primary");
	}
	
	function update_count($id = null) {
		if (!Permissionable::isRoot() && $id===null) {
			echo "Root permission required.";
			exit;
		}
		if ($id) {
			$ret = $this->User->updateCounter($id);
			$this->__setRandomGroupCoverPhoto($id);
		} else {
			$this->User->updateAllCounts();
			$this->__setRandomGroupCoverPhoto();
		}
		if ($id) $this->redirect(array('action'=>'home', $id));
		else $this->redirect('/person/all');
	}
	function __setRandomGroupCoverPhoto($id = null){
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
			foreach ($data as $row) {
				if (!empty($row['User']['src_thumbnail'])) continue;	// skip 
				$uid = $row['User']['id'];
				$this->User->id = $uid;
				$sql = "
	SELECT Asset.src_thumbnail, SharedEdit.score, Asset.id
	FROM assets Asset
	LEFT JOIN shared_edits AS `SharedEdit` ON (`SharedEdit`.`asset_hash` = `Asset`.`asset_hash`)
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
		Configure::write('js.bootstrap_snappi', true);
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
			case 'aws.snaphappi.com':
			case 'dev2.snaphappi.com':
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
				
		return $hashedPwds;
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
		$this->layout = $layout = 'snappi-guest';
		$forceXHR = setXHRDebug($this, 0);		// xhr login is for AIR desktop uploader
		parent::register();
		$done = $this->renderXHRByRequest('json', 'xhr/register', null, $forceXHR);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
		
	}

	function login() {
		$this->layout = $layout = 'snappi-guest';
		$forceXHR = setXHRDebug($this, 0);		// xhr login is for AIR desktop uploader
		if($forceXHR) $this->data['User']['password'] = $this->Auth->password($this->data['User']['password']);
		$allow_guest_login = Configure::read('AAA.allow_guest_login');
		/*
		 * POST method
		 * local user registration not yet implemented
		 */
		if (isset($this->data['User'])) {
if ($this->RequestHandler->isAjax() || $forceXHR) {
	$this->log("   >>> ATTEMPTED XHR Sign-in for user={$this->data['User']['username']}", LOG_DEBUG);
	// $this->log($_COOKIE, LOG_DEBUG);	
	// $this->log("[HTTP_COOKIE]=".$_SERVER['HTTP_COOKIE'], LOG_DEBUG);	
	// $this->log("[HTTP_USER_AGENT]=".env('HTTP_USER_AGENT'), LOG_DEBUG);		
	// debug($_COOKIE);
}

			$login_ok = false;
			$response = array();
			if (empty($this->data['User']['username'])) {
				$guest_pass = isset($this->data['User']['guest_pass']) ? $this->data['User']['guest_pass'] : false ;
				if (!$guest_pass && $allow_guest_login) {
					// ONLY for AIR testing. 
					// use magic value as guest pass for AIR uploader
					$magic_uuid = isset($this->data['User']['magic']) ? $this->data['User']['magic'] : null;
					$guest_pass = $magic_uuid;
$this->log("set guest_pass for AIR uploader login, guest_pass={$guest_pass}", LOG_DEBUG);					
					$this->Session->write('Auth.guest_pass', $guest_pass);
				}
			}

			/*
			 * prepare postData for login
			 * Note: magic logins for DEV overwrite $guest_pass logins
			 */
			if (empty($this->data['User']['username']) || !empty($this->data['User']['magic'])) {
$this->log("check magic/cookie login for user=", LOG_DEBUG);
//$this->log($this->data['User'], LOG_DEBUG);				
				$this->User->recursive=0;
				/*
				 * for magic logins
				 */
				if (!empty($this->data['User']['magic']) && strlen($this->data['User']['magic'])==36){
//$this->log("using magic login for {$this->data['User']['magic']} ", LOG_DEBUG);
					$this->data = $this->User->read(null, $this->data['User']['magic'] );
					$this->log("magic login for user={$this->data['User']['username']}", LOG_DEBUG);
					// continue below
				} else if (empty($this->data['User']['username']) && $allow_guest_login) {
					/*
					 * for RETURNING Guest logins, read Cookie
					 */
					App::import('Component', 'Cookie');
					$Cookie = new CookieComponent($this);
					$guestid = $Cookie->read('guest_pass');
$this->log("using Cookie guestpass login for {$guestid}", LOG_DEBUG);						
					/*
					 * end read cookie
					 */	
					$this->data = $this->User->read(null, $guestid );
					
					// extend cookie another 2 weeks
					$Cookie->write('guest_pass', $guestid, false, '2 week');
					$this->log("returning Guest login for guestid={$this->data['User']['id']}", LOG_DEBUG);
					// continue below					
				}
			}
			// $this->data['User']['username'] will be set if id is valid
			if (empty($this->data['User']['username']) && $allow_guest_login) {
				/*
				 * NEW Guest login
				 */					
				// confirm guest_pass was issued by /users/login?optional
				// use magic login value, if available, if not, read from Session
				if ($guest_pass == $this->Session->read('Auth.guest_pass')) {
					$this->Session->delete('Auth.guest_pass');
					$this->log("creating NEW Guest login for guest id={$guest_pass}", LOG_DEBUG);					
					// create Guest Session, as necessary, and continue
					$this->data = $this->__createGuestSession($guest_pass);
					$login_ok = $this->__loginUser($this->data);
					if ($login_ok == false) {
						// login failed
						$this->Session->delete('Auth.guest_pass');
						$this->data['User']['password']='';
						$response['message'] = "Sorry, your guest pass was invalid. Please register as a User.";
						$this->Session->setFlash($response['message']);
					}
				} else {
					// spoofed guest pass???
				}
			} else {
				/*
				 * user, guest or magic login
				 */
				// delete existing Auth session before we try to authenticate a new user
// $this->log("after /users/login for user={$this->data['User']['username']}", LOG_DEBUG);		
// $this->log($this->data['User'], LOG_DEBUG);
// $this->log($_COOKIE, LOG_DEBUG);	
// $this->log("response cookie value above. ", LOG_DEBUG);
				$this->__reset($saveRedirect = true);
				$login_ok = $this->__loginUser($this->data);
				$this->data = $this->Auth->user();
// $this->log($this->data['User'], LOG_DEBUG);				
				if ($login_ok == false) {
					// login failed
					// check for email authorized token
					if ($this->User->checkIfPendingValidation($this->data)) {
						$response['message'] = "This account has not yet been activated. Please check your email to activate.";
					} else {
						$response['message'] = "The username and password did not match. Please try again.";
					}
					$this->Session->setFlash($response['message']);
					$this->data['User']['password']='';
					// try again below
				} else {
					$this->log("   >>> Successful sign-in for user={$this->data['User']['username']}", LOG_DEBUG);
				}
			}
			/*
			 * set jsonData for XHR login
			 */
			if ($this->RequestHandler->isAjax() || $forceXHR) {
				$response['success'] = $login_ok ? 'true' : 'false';
				$response['response']['User']  = $this->data['User'];
				$this->viewVars['jsonData'] = $response; 
				$this->viewVars['jsonData']['Session.Config'] = $_SESSION['Config'];
// loadComponent('Cookie', $this);
// $this->Cookie->write('test2', 'SetNewCookie', false, '2 week');				
				$this->viewVars['jsonData']['Cookie'] = $_COOKIE;
// debug($_COOKIE);
// $this->log($this->viewVars['jsonData'], LOG_DEBUG);				
			}
			
			/*
			 * respond to POST, either HTTP or XHR
			 */
			$done = $this->renderXHRByRequest('json', 'xhr_login_view', null, $forceXHR);
			if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
			if ($login_ok) $this->__continueToRedirect();
			// else try login again
		} else {	// status of current auth user
			if ($this->RequestHandler->isAjax() || $forceXHR) {
// $this->log("[HTTP_COOKIE]=".$_SERVER['HTTP_COOKIE'], LOG_DEBUG);	
				$response = array('success'=>true, 'message'=>'Current authenticated user');
				$response['response'] = $this->Auth->user();
				$response['Cookie'] = $_COOKIE;
				$this->viewVars['jsonData'] = $response;
// $this->log($this->viewVars['jsonData']['response'], LOG_DEBUG);
// $this->log("result for current authenticated user", LOG_DEBUG);
				$done = $this->renderXHRByRequest('json', null, null, $forceXHR);
				if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false			
			}			
		}

		/*
		 * GET method
		 */
		$rpxTokenUrl = htmlentities(Configure::read('ApiKey.rpxnow.signin'));
		$this->set(compact('isAuth', 'permissions', 'rpxTokenUrl'));

		// allow guest sign in
		if ($allow_guest_login && isset($this->params['url']['optional'])) {
			$guest_pass = String::uuid();
			$this->Session->write('Auth.guest_pass', $guest_pass);
			$this->set('guest_pass', $guest_pass);
		}


		$userlist = $this->User->find('list', array('fields'=>'User.username', 'conditions'=>array("id!=username")));
		$this->set('userlist', $userlist);
		
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
		$this->layout = $layout = 'snappi-plain';
		parent::reset_password($token, $user);
	}
	
	function change_password() {
		$this->layout = $layout = 'snappi-plain';
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

	function settings($id = null) {
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
			$allowed = array('ADMIN');
			$userid = AppController::$userid;
			if ( $userid== $id || in_array(AppController::$role, $allowed)) {
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
				$this->redirect(array('action'=> 'login'));
			}
		}
	}	
	
}






?>