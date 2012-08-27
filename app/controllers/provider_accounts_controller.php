<?php
class ProviderAccountsController extends AppController {

	var $name = 'ProviderAccounts';

	function beforeFilter() {
		// only for snaphappi user login, not rpxnow
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$this->Auth->allow( 'index', 'folders', 'import');
	}
	
	/*
	 * counter spec for filenames = filename~XXX.jpg
	 * this is the opposite of __incrementCounterToFilename() in fileUploader
	 */
    public function __stripCounterFromFilename($filename) {
    	if(preg_match('/(.*)~(\d*)(\..*)$/',$filename, $matches)) {
    		$filename = "{$matches[1]}{$matches[3]}";
    	}
    	return $filename;
    }

	function folders ($folder=null) {
		$provider ='snappi';
		$this->RequestHandler->renderAs($this, $provider);
		$this->layout='..\default';
		if (@isne($this->params['url']['relpath'])) {
			$folder = $this->params['url']['relpath'];
		}
		if (@isne($this->params['named']['relpath'])) {
			$folder = $this->params['named']['relpath'];
		}

		// get config
		$config = Configure::read('path.local');

		$orig_basepath = cleanPath($config['original']['basepath']);
		$orig_basepath = $folder ? $orig_basepath.DS.$folder : $orig_basepath;
		$src_folder = new Folder($orig_basepath , false, false);
		if (empty($src_folder->path))
		{
			$this->Session->setFlash("source folder does not exist.");
			$this->render('message');
			return;
		}

		list($dirs,$files) = $src_folder->read();
		$reltree = array();
		foreach($dirs as $absolutePath)
		{
			if ($absolutePath==$orig_basepath) $reltree[] = ".";
			else $reltree[] = str_replace($orig_basepath.DS,'',$absolutePath);

		}
		$this->set('tree', $reltree);
	}

	
	function __createProviderAccountSnappi() {
		// auto-create snappi provider account, if not found
		$uuid = String::uuid();
		$user = Session::read('Auth.User');
		$data['ProviderAccount']=array(
			'id'=>$uuid,
			'user_id'=>$user['id'],
			'provider_name'=>'snappi',
			'provider_key'=>$uuid,
			'display_name'=> $user['username'] 
		);
		$this->ProviderAccount->create();
		if (!$this->ProviderAccount->save($data)) {
			debug($data);
		} else {
			// reload page now that snappi provider account exists
			$this->redirect($this->here, null, true);
		}
		return $uuid;	
	}

	function index() {
		$this->ProviderAccount->recursive = 0;
		$this->set('providerAccounts', $this->paginate());
	}

	function view($id = null) {
		if (!$id) {
			if ( isset($this->params['named']['src']) && $this->params['named']['src']=='snappi') {
				// find snappi ProviderAccount by user_id
				$this->ProviderAccount->contain();
				$options=array(
					'fields'=>'id',
					'conditions'=>array('user_id'=>$this->Auth->user('id'), 'provider_name'=>'snappi'),
					'recursive'=>-1
				);
				$data = $this->ProviderAccount->find('first', $options);
				$id = array_shift(Set::extract($data, '/ProviderAccount/id'));
				if (!$id) {
					// auto-create snappi provider account, if not found
					$this->__createProviderAccountSnappi();
				}
				$this->redirect('/provider_accounts/view/'.$data['ProviderAccount']['id']);
			} else {
				$this->Session->setFlash(sprintf(__('Invalid %s', true), 'provider account'));
				$this->redirect(array('action' => 'index'));
			}
		}
		/*
		 * Set Session to Provider
		 */
		$data = $this->ProviderAccount->read(null, $id);
		shuffle($data['Asset']);
		$data['Asset'] = array_slice($data['Asset'],0,5);
		Configure::write('ProviderAccount', $data['ProviderAccount']);	// Configure or Session??
		$this->set('providerAccount', $data);
	}

	function add() {
		if (!empty($this->data)) {
			$this->ProviderAccount->create();
			if (empty($this->data['ProviderAccount']['provider_key'])) {
				$this->data['ProviderAccount']['provider_key'] == String::uuid();
							}
			if ($this->ProviderAccount->save($this->data, false)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'provider account'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'provider account'));
			}
		}
		$owners = $this->ProviderAccount->Owner->find('list');
		$this->data['ProviderAccount']['provider_name'] = 'snappi';
		$this->set(compact('owners'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'provider account'));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->ProviderAccount->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'provider account'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'provider account'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->ProviderAccount->read(null, $id);
		}
		$owners = $this->ProviderAccount->Owner->find('list');
		$this->set(compact('owners'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'provider account'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->ProviderAccount->delete($id)) {
			$this->Session->setFlash(sprintf(__('%s deleted', true), 'Provider account'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Provider account'));
		$this->redirect(array('action' => 'index'));
	}
}
?>