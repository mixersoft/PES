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

	/**
	 * @DEPRECATE
	 * - USE /my/upload instead
	 */
    function import()
	{
		set_time_limit(600);
		$userid = Session::read('Auth.User.id');
		if ($this->Session->check('fileUploader.uploadFolder') == null) {
				$UPLOAD_FOLDER = Configure::read('path.wwwroot').DS.'svc'.DS.'upload'.DS.$userid.DS;
				Session::write('fileUploader.uploadFolder', $UPLOAD_FOLDER);
		}	
		$LOADPATH = Session::read('fileUploader.uploadFolder');	
		$SAVEPATH = Configure::read('path.stageroot.basepath');
		$relpath = ''; $recursive = false;
		$limit = 10;
		$limit = null;
		extract($this->params['url'], EXTR_IF_EXISTS);

		/*
		 * should this component be tied to the provider? i.e. snappi, flickr, etc.
		 */
		$Import = loadComponent('Import', $this);
		$photos = $Import->findPhotos($LOADPATH, $recursive, $limit);
	
		/*
		 * prepare $assets array to import Assets, insert into Assets table
		 */
		$this->ProviderAccount->contain();
		$options=array(
			'fields'=>'id, provider_name',
			'conditions'=>array('user_id'=>$this->Auth->user('id'), 'provider_name'=>'snappi'),
			'recursive'=>-1
		);
		$data = $this->ProviderAccount->find('first', $options);
		if (empty($data['ProviderAccount']['id'])) {
			// create providerAccount for provider='snappi'
			$this->__createProviderAccountSnappi();
			
		}
		$providerAccount = $data['ProviderAccount'];	
		
		$timestamp = time();
		$assetTemplate = array(
			'owner_id'=>$userid,						// owner, might be redundant
			'provider_account_id' => $providerAccount['id'],
			'provider_name'=>$providerAccount['provider_name'],
			'batchId' => $Import->getBatchId($timestamp),
			'uploadId' => $timestamp,
		);
		$assets = array();
		$prepare_to_move=array();
		foreach($photos as $photoPath)
		{
			$file_relpath = cleanPath(substr($photoPath,strlen($LOADPATH)),'http');
			$filename_no_counter = $this->__stripCounterFromFilename($file_relpath);	
			$uuid = String::uuid();
			$shardPath = $Import->shardKey($uuid, $uuid);
			$src['orig']= $filename_no_counter;		// original relpath in the clear
			$src['root']= $shardPath;
			$src['preview']= $Import->getImageSrcBySize($shardPath, 'bp');
			$src['thumb']= $Import->getImageSrcBySize($shardPath, 'tn');
			/*
			 * resize/rotate before getting Meta???
			 */
				
			// 		Meta should be the same for root/preview src, but what about originals?
				
			/*
			 * auto-rotated
			 */
				
			$meta = $Import->getMeta($photoPath);
			/*
			 * update asset_hash function to match SNAPPI AIR, see php_lib.php
			 */
			$asset_hash = getAssetHash($meta['exif'], $photoPath, $filename_no_counter );
			$asset = array(
				'id' => $uuid,
				'provider_key' => $uuid,
				'asset_hash' =>  $asset_hash,	
				'json_src' => $src,
				'src_thumbnail' => $src['thumb'],
				'json_exif' => $meta['exif'],
				'json_iptc' => $meta['iptc'],
				'dateTaken' => !empty($meta['exif']['DateTimeOriginal']) ? $meta['exif']['DateTimeOriginal'] : null,
				'isFlash' => !empty($meta['exif']['Flash']) ? $meta['exif']['Flash'] > 1 : null,
				'isRGB' => !empty($meta['exif']['ColorSpace']) ? $meta['exif']['ColorSpace'] == 1 : null,
			);
				
			if (isset($meta['iptc']['Keyword'])) $asset['keyword']= $meta['iptc']['Keyword'];
			if (isset($meta['iptc']['Caption'])) $asset['caption']= $meta['iptc']['Caption'];
			// TODO: double check Caption. this did not work on last import
			if (empty($asset['caption']))   {
				$asset['caption'] = pathinfo($filename_no_counter, PATHINFO_FILENAME);
			}
				
			pack_json_keys($asset);
			$prepare_to_move[$asset['id']]=array('src'=>$photoPath, 'dest'=>$src['root']);
			$assets[] = array_merge($assetTemplate, $asset);
		}
//debug($assets); exit;
		/*
		 * Save Assets
		 * 	1) first try to saveAll in one atomic trn, (17 secs)
		 * 	if something fails, possible because of unique key index,
		 * 	2) save each Asset individually (67 secs)
		 */
		// TODO: process import in chunks via message queue
		$Asset = $this->ProviderAccount->Asset;

		/*
		 * set default Asset perms from Profile
		 *		override Plugin default set in Asset model
		 */
		$profile = $this->getProfile();
		if (isset($profile['Profile']['privacy_assets'])) {
			// probably easier to set default bits here than use ['Permission']['perms']
			$Asset->Behaviors->Permissionable->settings['Asset']['defaultBits'] = $profile['Profile']['privacy_assets'];
		}


		// config Permissionable for CREATE to skip 'write' permission check
		$Asset->isCREATE = true;
		//		$assets = array_slice($assets, 0, 10);
		$ret = @$Asset->saveAll($assets);
		if ($ret){
			$result['saved'] = Set::extract($assets, '/id');
			$message['Ok'] = count($assets). " photos saved.";
		} else {
			$ret = array('saved'=>array(),'failed'=>array());
			$result['saved']=array();
			foreach ($assets as & $asset) {
				$saveData['Asset'] = $asset;
				$Asset->create();
				if ($ret = @$Asset->save($saveData, false)) {
					$result['saved'][] = $asset['id'];
				} else {
					$result['failed'][] = $asset['id'];
				}
			}
			$saved = count($result['saved']); $total = count($assets);
			$message['Warning'][] = "Warning: importing photos from relpath={$relpath}, only {$saved} of {$total} photos saved.";
			$message['Warning'][] = "   > Is it possible some photos were already imported?";
		}

		
		/*
		 * stage imported photos
		 */
		
		
		/*
		 * NOTE: this processing should be done from a message queue
		 * 1) move/copy saved assets into STAGING, 
		 * 2) then autoRotate staged assets
		 * 	$prepare_to_move - array of files to copy/move on success
		 *  - keep track of successful staged files, for later autoRotate
		 * IMPORTANT: Exif.orientation is value BEFORE autoRotate
		 */
		$stagingErrors = array();
		$prepare_autoRotate = array();
		
		
		// only stage sucessfully imported files
		foreach ($result['saved'] as $id) {	
			$stage = $prepare_to_move[$id];

		
//		// 	CANNOT stage ALL imported files, (in case staging failed on previous attempt)
//		//     because UUID won't match Asset.id. need to switch to asset_hash||owner_id
		
//		foreach ($prepare_to_move as $id=>$stage) {	
			if (empty($stage)) {
				debug('Error: saved asset not in prepare_to_move array. cannot stage. asset.id='.$id);
			} else {
				$ret = $Import->import2stage($stage['src'], $stage['dest'], null, $move = true);
				if (!$ret) $stagingErrors[] = array($id=>$stage);
				$prepare_autoRotate[] = $stage['dest'];
			}
		}
		if (!empty($prepare_autoRotate)) {
			$autoRotateErrors = $Import->autoRotate($prepare_autoRotate);
		}
		if (!empty($stagingErrors)) {
			$message['Error'][] = "Error: the following saved assets were not properly staged.";
			$message['Error'][] = pr( "<PRE>".$stagingErrors."</PRE>", true);
			$message['Error'][] = "Error: the following STAGED assets were not properly autoRotated.";
			$message['Error'][] = pr( "<PRE>".$autoRotateErrors."</PRE>", true);			
		}

		/*
		 * ouput ok/warning/error messages
		 */
		if (isset($message)) {
			if (isset($message['Error'])) {
				debug($message['Error']);
			}
			if (isset($message['Ok'])) {
				$this->Session->setFlash($message['Ok']);
			}
			if (isset($message['Warning'])) {
				$this->Session->setFlash(print_r( $message['Warning'], true));
			}
			if (is_array($message)) $message = print_r($message, true);
			$this->Session->setFlash($message);
		}
//debug($message);		
		$this->redirect('/my/photos');
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