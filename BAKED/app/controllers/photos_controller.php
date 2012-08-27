<?php
/*
 * Photos controller
 * 
 *  - overloads AssetsController, use same pattern for other asset/Media classes 
 */
App::import('Controller', 'Assets');
class PhotosController extends AssetsController {
	public $name = 'Assets';	// 'Photos	
	public $viewPath = 'assets';
	public $uses = 'Asset';
	
	public $titleName = 'Photos';
	public $displayName = 'Photo';	// section header
	
	public $paginateModel = 'Asset';
	
	function __construct() {
		parent::__construct();
	}
	
	function beforeFilter() {
		// only for snaphappi user login, not rpxnow
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$myAllowedActions = array(			
			/*
			 * experimental
			 */
			'export'
		);
		$this->Auth->allow( $myAllowedActions);
		// TODO: edit allowed for  'role-----0123-4567-89ab---------user'
		// TODO: groups allowed for  'role-----0123-4567-89ab--------guest', 'role-----0123-4567-89ab---------user'
	}	
	/**
	 * simple export photos from castingCall
	 * 	example:
	 * 		http://git:88/groups/photos/4df7588c-b968-4c60-b838-0290f67883f5/rating:3/perpage:99
	 * 		http://git:88/photos/export/1309848627/Peter%20and%20Allie
	 * 
	 * @param $ccid, CCID. NOTE: make sure /perpage set to get all rows
	 * @param $destname string, export filename before index
	 * @return unknown_type
	 */
	public function export ($ccid, $destname=null) {
		$this->autoRender = false;
		if (!isset($this->Export)) $this->Export = loadComponent('Export', $this);
//		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$CastingCall = Session::read("castingCall.{$ccid}");
//debug($CastingCall);		
		if (empty($CastingCall)) {
			// error msg
		} else {
			$assetIds = Set::extract('Auditions.Audition.{n}.id', $CastingCall );
//debug($assetIds);			
			$options = array(
				'conditions' => array('Asset.id'=>$assetIds),
				'fields'=>'Asset.json_src',
				'order'=>'Asset.dateTaken',
				'show_edits'=>false,
				'join_shots'=>false,
			);
			$data = $this->Asset->find('all', $options);
			
			if (empty($data)) {
				// error msg	
			} else {
				$exportOptions['type']='orig';
				switch($exportOptions['type']) {
					case 'orig':
						$src_basepath = null;
						$exportOptions['destname'] = $destname;
						break;
					case 'root':
						$src_basepath = $CastingCall['Auditions']['Baseurl'];
						$exportOptions['destname'] = $destname;
						break;
				}
				/*
				 * match CastingCall sort order, important for Group photos
				 */
				$sortedData = array();
				$data = Set::combine($data, '/Asset/id','/Asset/json_src');
				foreach ($assetIds as $assetId) {
					$sortedSrc[] = json_decode($data[$assetId], true);	
				}
				$errors = $this->Export->export($sortedSrc, $src_basepath, $exportOptions );		
				debug($errors);
			}
		}
	}
}
?>
