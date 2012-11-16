<?php

App::import('Sanitize');

class ThriftFolder extends AppModel {
	public $name = 'ThriftFolder';
	// public $useDbConfig = 'default';
	public $displayField = 'native_path';
	
	public $belongsTo = array(
		'ThriftDevice' => array(
			'foreignKey' => 'thrift_device_id',
			'conditions' => '',
			'fields' => '',
			'counterCache' => true,
		)
	);	
	
	public static $DEVICE_SEPARATOR = '~';	// Asset.native_path == deviceID~origPath in DB
	public static $ALLOWED_UPDATE_KEYS = array('count', 'is_scanned', 'is_watched', 'is_not_found' );
	
	private function hashPath($path) {
		return sprintf('%u', crc32($path));
	} 
	
	public function beforeSave() {
		if (isset($this->data[$this->alias]['native_path'])) {
			$this->data[$this->alias]['native_path_hash'] = $this->hashPath($this->data[$this->alias]['native_path']);
		}
		return true;
	}	
	
	/**
	 * @param $paid UUID, provider_account_id for name=native-uploader
	 */
	public function addFolder($thriftDeviceId, $nativePath, $options=array()) {
		$data = $this->create();
		$data['ThriftFolder']['thrift_device_id'] = $thriftDeviceId;
		$data['ThriftFolder']['native_path'] = $nativePath;
		if (!empty($options['is_scanned'])) $data['ThriftFolder']['is_scanned'] = $options['is_scanned'];
		if (!empty($options['is_watched'])) $data['ThriftFolder']['is_watched'] = $options['is_watched'];
		$ret = $this->save($data);
		if (!$ret) {
			// return DB error
			$db =& ConnectionManager::getDataSource($this->useDbConfig);   
			$this->dbError = explode(':', $db->lastError(), 2);  
			if ($this->dbError[0] == '1062') throw new Exception("Error: Folder already exists, native_path={$nativePath}");
		}
		return  $ret ? $this->read(null, $this->id) : false;
	}	
	/**
	 * @param $paid UUID, provider_account_id for name=native-uploader
	 */
	public function deleteFolder($thrift_device_id, $nativePath) {
		$folder = $this->findByNativePath($thrift_device_id, $nativePath);
		if (!empty($folder['ThriftFolder']['id'])) {
			$this->id = $folder['ThriftFolder']['id'];
			$this->delete();
			return true;
		} else return false;
	}
		
	public function findByDeviceUUID($device_UUID, $is_watched = false) {
		$options['contain'] = array('ThriftDevice'=>array(
				'conditions'=>array('ThriftDevice.device_UUID'=>$device_UUID)
			));
		$options['conditions'] = array('`ThriftDevice`.device_UUID IS NOT NULL');	
		return $this->findByThriftDeviceId(null, $is_watched, $options);	
	}
	/*
	 * Add options to Model->find() to include the count of uploaded files for each folder
	 *	TODO: storing Asset.native_path with embedded device_id is causing a very bad join
	 * 			but how else can we catch nested folders in the folderlist?  
	 */
	private function _addUploadCount($options){
		$options['fields'][] = 'ThriftFolder.*';
		$options['fields'][] = 'COUNT(Asset.id) AS uploaded';
		$options['joins'][] = array(
			'table'=>'assets',
			'alias'=>'Asset',
			'type'=>'INNER',
			'conditions'=>array(
				"Asset.owner_id"=>AppController::$userid,
				"Asset.native_path LIKE (concat(ThriftFolder.thrift_device_id, '~',  replace(ThriftFolder.native_path,'\\\\','\\\\\\\\'), '%') )",
			),
		);	
		$options['group'] = array('`ThriftFolder`.`id`');
		return $options;
	}
	
	public function findByThriftDeviceId($thrift_device_id, $is_watched = false, $options = array()) {
		if ($thrift_device_id) {
			$options['conditions']['ThriftFolder.thrift_device_id'] =  $thrift_device_id;
		}
		if ($is_watched === false) {
			$options['conditions']['ThriftFolder.is_watched'] = 0;
			$options['conditions']['ThriftFolder.is_scanned'] = 0;
			$options['order'] = array('ThriftFolder.count'=>'DESC', 'ThriftFolder.native_path'=>'ASC') ;
		} else if ($is_watched) {
			$options['conditions']['ThriftFolder.is_watched'] = 1;
			$options['order'] = array('ThriftFolder.count'=>'DESC', 'ThriftFolder.native_path'=>'ASC') ;
		} else if ($is_watched===null) {
			// return all folders
			$options['order'] = array('ThriftFolder.is_watched'=>'ASC', 'ThriftFolder.count'=>'DESC', 'ThriftFolder.native_path'=>'ASC') ;
		}		
		$options = $this->_addUploadCount($options);
		$data = $this->find('all', $options);
		Sanitize::clean($data);
// ThriftController::log("ThriftFolder::findByDeviceId OK", LOG_DEBUG);
// ThriftController::log($options, LOG_DEBUG);
		return $data;
	}
	
	/**
	 * update ThriftFolder attributes using a nativePath as key 
	 * @param $folderData, $data['ThriftFolder']['native_path'], must include $folderData['ThriftFolder']['native_path']
	 */
	public function updateFolderByNativePath($thrift_device_id, $folderData){
		$found = $this->findByNativePath($thrift_device_id, $folderData['ThriftFolder']['native_path']);
		if (!empty($found)) {
			$this->id = $found['ThriftFolder']['id'];
			$data['ThriftFolder'] = array_filter_keys($folderData['ThriftFolder'], ThriftFolder::$ALLOWED_UPDATE_KEYS);
			$ret = $this->save($data, array('fieldList'=>array_keys($data['ThriftFolder'])));
			return $ret;
		} else return false;	// native_path not found
	}

	public function findByNativePath($thrift_device_id, $nativePath){
		$folder_hash = $this->hashPath($nativePath);
		$options = array(
			'conditions'=>array(
				'ThriftFolder.thrift_device_id'=>$thrift_device_id,
				'ThriftFolder.native_path_hash'=>$folder_hash,
			)
		);
		return $this->find('first', $options);
	}
	
	/**
	 * Get array of files which have already been uploaded under the given folder.
	 * uses simple string compare of nativePath 
	 * 
	 * NOTE: saving
	 * 
	 * @param ThriftDevice array, like $data['ThriftDevice']
	 * @param $nativePath string, native path string of the containing folder
	 * @return $data['Asset']
	 * 
	 */
	// public function getFiles($thrift_device_id, $nativePath) {
	public function getFiles($ThriftDevice, $nativePath) {
		// get all Assets by ProviderAccount
		$devicePrefix = $ThriftDevice['id'].ThriftFolder::$DEVICE_SEPARATOR;
		// escape \=>\\ for SQL, then use mysql_real_escape_string()
		$nativePath = str_replace('\\','\\\\',$nativePath);
		$nativePath = mysql_real_escape_string($nativePath);
		$asset_options = array(
			'permissionable'=>false,		// owner_id=AppController::$userid
			'conditions'=>array(
				'Asset.provider_account_id'=>$ThriftDevice['provider_account_id'],
				'Asset.owner_id'=>AppController::$userid,
				// filter in DB, or in PHP. see callback FolderContains::asset()
				"Asset.native_path LIKE '{$devicePrefix}{$nativePath}%'",
			),
			'fields'=>'Asset.native_path',
		);
		$data = ClassRegistry::init('Asset')->find('all', $asset_options);
		return Set::extract('/Asset/native_path',$data);
	}
	
	
}
?>