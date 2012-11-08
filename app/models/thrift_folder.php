<?php
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
	
	private function hashPath($path) {
		return crc32($path);
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
		return  $ret ? $this->read(null, $this->id) : false;
	}	
	
	public function findByDeviceUUID($device_UUID, $is_watched = false) {
		$contain = array('ThriftDevice'=>array(
				'conditions'=>array('ThriftDevice.device_UUID'=>$device_UUID)
			));
		return $this->findByThriftDeviceId(null, $is_watched, $contain);	
	}
	
	public function findByThriftDeviceId($thrift_device_id, $is_watched = false, $contain = null) {
		$options = array();
		if ($contain) $options['contain'] = $contain;
		if ($thrift_device_id) $options['conditions']['ThriftFolder.thrift_device_id'] =  $thrift_device_id;
		if ($is_watched === false) {
			$options['conditions']['ThriftFolder.is_watched'] = 0;
			$options['conditions']['ThriftFolder.is_scanned'] = 0;
		} else if ($is_watched) {
			$options['conditions']['ThriftFolder.is_watched'] = 1;
		} else if ($is_watched===null) {
			// return all folders
		}
		$data = $this->find('all', $options);
// ThriftController::log("ThriftFolder::findByDeviceId OK", LOG_DEBUG);
// ThriftController::log($options, LOG_DEBUG);
		return $data;
	}
	
	public function updateFolder($thrift_device_id, $folderState){
		
	}
	/**
	 * @param $folderData, $data['ThriftFolder']['native_path'], must include $folderData['ThriftFolder']['native_path']
	 */
	public function updateFolderByNativePath($thrift_device_id, $folderData){
		if (empty($folderData['ThriftFolder']['native_path'])) throw new Exception("Error: updateFolderByNativePath() native_path is null");
		$options = array(
			'conditions'=>array(
				'ThriftFolder.thrift_device_id'=>$thrift_device_id,
				'ThriftFolder.native_path_hash'=>$this->hashPath($folderData['ThriftFolder']['native_path']),
				'ThriftFolder.native_path'=>$folderData['ThriftFolder']['native_path'],
			)
		);
		$found = $this->find('first', $options);
		if (!empty($found)) {
			$this->id = $found['ThriftFolder']['id'];
			$data['ThriftFolder'] = array_filter_keys($folderData['ThriftFolder'], array('count', 'is_scanned', 'is_watched' ));
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
	 * @param $providerAccount array, like $data['ProviderAccount']
	 * @param $nativePath string, native path string of the containing folder
	 * @return $data['Asset']
	 * 
	 * TODO: this needs $thrift_device_id!!!!!!!!!!!!!!!!!!
	 */
	// public function getFiles($thrift_device_id, $nativePath) {
	public function getFiles($providerAccount, $nativePath) {
		// get all Assets by ProviderAccount
		$asset_options = array(
			'permissionable'=>false,		// owner_id=AppController::$userid
			'conditions'=>array(
				'Asset.provider_account_id'=>$providerAccount['id'],
				'Asset.owner_id'=>AppController::$userid,
			),
			'fields'=>'Asset.native_path',
		);
		$data = ClassRegistry::init('Asset')->find('all', $asset_options);
		return Set::extract('/Asset/native_path',$data);
	}
	
	
}
?>