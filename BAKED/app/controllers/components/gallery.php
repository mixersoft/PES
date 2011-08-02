<?php
// Cress: Use this component for common photo work

class GalleryComponent extends Object
{
	var $controller;
	
	CONST CLIENT_ID = 2;
	
	// currently not used
	var $reservedTags = array('fk','PRE', 'CER','POR','REC','CPE','STE','private','spt','clock','BTS');
	
	function startup(& $controller)
	{
		$this->controller = & $controller;
	}

	function findPhotos($dir)
	{
		// NOTE: don't use realpath here, it doesn't work with symlinks
		$folder = new Folder($dir);
		$photos = $folder->findRecursive('(?![a-z]{2}~).*\.jpe?g');
		return $photos;
	}

	function getRelativePath($filepath, $role=NULL)
	{
		# basepath no longer dependent on user role
//		if ($role===NULL) $role = @$this->controller->cur['user']['role']; 

		$filepath = cleanPath($filepath, 'unix');	
		return str_replace($this->getBasepath().'/','',$filepath);
	}

	
	function getBasepath($role=NULL)
	{
		if (@empty($this->basepath)) $this->basepath = cleanPath(Configure::read('Local.original.fileroot'), 'unix');
		return $this->basepath;
	}
	

	function XXXgetAbsolutePath($relpath)
	{
		return Configure::read('Local.original.fileroot') . '/' . $relpath;
	}

	function getWorkorderPath($workorder_id)
	{
		App::import('model', 'Workorder');
		$Workorder = new Workorder();
		$Workorder->id = $workorder_id;
		
		$this->controller->loadComponent('Exec', $this->controller);
		$prefix = $this->getBasepath();
		return $prefix . '/' . $Workorder->field('title');		
	}

	/*
	 * This function will extract key information 
	 * for matching a filepath to a row in the Assets DB table 
	 *
	 * @param String $path
	 * @return aa
	 */
	function getKeyFromPath($path)
	{
		App::import('model', 'Tag');
		$Tag = new Tag();

		$key = array();
		
		if($relpath = $this->getRelativePath($path))
		{
			// try $path = relative path
			$data = $Tag->Asset->find('first', array(
				'conditions' => array('relpath' => $relpath)
				, 'fields' => 'id'
				, 'recursive' => -1
			));
			$key['relpath'] = $relpath;
		} 
		else 
		{
			// check to see if $path == relpath
			$relpath = $path;
			$data = $Tag->Asset->find('first', array(
				'conditions' => array('relpath' => $relpath)
				, 'fields' => 'id'
				, 'recursive' => -1
			));
			$key['relpath'] = $relpath;
		}

		$key['client_id'] =  GalleryComponent::CLIENT_ID; // unused at this moment
		
		 // use filename to key on db row for now, but later we will use order_id/asset_id where filename=asset_id
		 
		if($data)
		{			
			$key['asset_id'] = (int)$data['Asset']['id'];
		} else
		{
			$key['asset_id'] = null;
		}
		return $key;
	}

	function getExifIptc($path)
	{
		$data = array();

		// get EXIF data
		$exif = @exif_read_data($path);
//debug($path);		
//debug($exif);		
		if(!empty($exif))
		{
			if(isset($exif['COMPUTED']['Width']))
			{
				$data['imageWidth'] = $exif['COMPUTED']['Width'];
			}
			if(isset($exif['COMPUTED']['Height']))
			{
				$data['imageHeight'] = $exif['COMPUTED']['Height'];
			}
			$fields = array('ExifImageWidth', 'ExifImageLength', 'Orientation', 'DateTimeOriginal', 'Flash');
			foreach($fields as $field) {
				if(isset($exif[$field]))
				{
					$data["exif_{$field}"] = $exif[$field];
				}
			}
		}

		// get IPTC data
		getimagesize($path, $iptc);
		if(!empty($iptc['APP13']))
		{
			$app13 = iptcparse($iptc['APP13']);
			$fields = array(
				'SpecialInstructions' => '2#040'
				, 'Keyword' => '2#025'
				, 'Category' => '2#015'
				, 'ByLine' => '2#080'
				, 'ByLineTitle' => '2#085'
				, 'PhotoMechanicPrefs' => '2#221'
			);
			foreach($fields as $field => $index) {
				if(isset($app13[$index]))
				{
					$value = $app13[$index];
					if ($index==$fields['PhotoMechanicPrefs'])
					{
						if (empty($this->Iptc)) $this->Iptc = loadComponent('Iptc');
						$data["rating"]=$this->Iptc->getRatingFromPhotoMechanic($value);
						continue;
					}
					if(is_array($value))
					{
						$value = join(',', $value);
					}
					$data["iptc_{$field}"] = $value;
				}
			}			
		}

		return $data;
	}

	// if $workorder_id is a string, it is the workorder's title, the workorder will be created for assets
	// if $workorder_id is an ID, the new assets will be added to the workorder
	function importAssets($idOrBasepath, $expected_photos = 0)
	{
		App::import('model', 'Workorder');
		$Workorder = new Workorder();
		
		/*
		 * create workorder if BasePath
		 */
		if(is_numeric($idOrBasepath)==false)
		{
			$data = array(
				'client_id' =>  GalleryComponent::CLIENT_ID, 'title' => $idOrBasepath, 'folder_basepath' => $idOrBasepath, 'expected_photos' => $expected_photos
			);
			if(!$Workorder->save($data))
			{
				return null;
			}
			$idOrBasepath = $Workorder->id;
	
			if(!$Workorder->WorkorderStep->initSteps($idOrBasepath))
			{
				return null;
			}
		}
		
		/*
		 * fetch workorder data
		 */
		if(is_numeric($idOrBasepath))
		{
			$Workorder->id = $idOrBasepath;
			$data = $Workorder->find('first', array(
				'conditions' => array('id' => $idOrBasepath)
				, 'fields' => array('folder_basepath', 'expected_photos', 'actual_photos'
									, 'city', 'state', 'wedding_date','copyright'
									, 'P1_name', 'P2_name', 'P3_name'  
									)
				, 'recursive' => -1
			));
			extract($data['Workorder']);

			$dir = Configure::read('Photos.workorder.basepath') . "/{$folder_basepath}";
			$photos = $this->findPhotos($dir);
	//			debug($photos);
	
			if($actual_photos >= count($photos))
			{
				// all present assets have been imported (but maybe $actual < $expected)
				return array('total'=>$actual_photos, 'ok'=>0, 'ready'=>true);	
			}
		}

		$total = count($photos);
		$ok = 0;
		$ready = false;
		/*
		 * import Assets
		 */
		$wo_id = $idOrBasepath;
		foreach($photos as $photo)
		{
			$photo = cleanPath($photo,'unix');

			$data = array(
				'id' => null,
				'workorder_id' => $wo_id,
				'client_id' => GalleryComponent::CLIENT_ID,
				'relpath' => $this->getRelativePath($photo)
			);
			$data = array_merge($data, $this->getExifIptc($photo));

			$ok += (int)$Workorder->Asset->save($data);
		}

		if($ok)
		{
			$Workorder->updateForAssets($wo_id);
		}

		$count = $Workorder->Asset->find('count', array(
			'conditions' => array('workorder_id' => $wo_id)
			, 'recursive' => -1
		));
		if($count == $total)
		{
			$Workorder->saveField('actual_photos', $count);
		}
		if($count == $expected_photos)
		{
			if($Workorder->saveField('status', 'ready'))
			{
				$ready = true;
			}
		}

		return compact('total', 'ok', 'ready');
	}
	
	/**
	 * Adjust exif_DateOriginalTaken times for 
	 * ALL PHOTOS FOR SAME PHOTOGRAPHER/WORKORDER, and optionally, camera
	 * using a single dateTime offset calculated by new-time - old-time
	 *
	 * @param aa $data
	 * @param String $filter, 	'photog' limits adjustment to photos by the same photog (default), 
	 * 							'camera' limits adjustment to photos from the same camera
	 * return array $errors
	 * 
	   Array(
		    [asset_id] => Array
		        (
		            [asset_id] => 15593
		            [new-time] => 2008-08-23 17:54:19
		            [old-time] => 2008-08-23 17:54:19
		        )
		)
	 */
	function syncExifTime($data, $filter='photog')
	{
		if (empty($data)) return;
		$errors = array();
		App::import('model', 'Asset');
		$Asset = new Asset();
		
		
		// find photographer from asset_id, check workorder_id
		$asset_ids = array_keys($data);
		$photos = $Asset->find('all', array(
			'conditions' => array('id' => $asset_ids)
			, 'fields' => array('id', 'workorder_id', 'iptc_ByLine', 'iptc_Category')
			, 'recursive' => -1
		));
//debug($photos); 		

		// list new/old time adjustments by Photographer name 
		$adjustments=array();
		foreach ($photos as $photo)
		{
//debug($photo); 			
			if (@empty($workorder_id)) $workorder_id = $photo['Asset']['workorder_id'];
			else if ($workorder_id!=$photo['Asset']['workorder_id']) {
				$errors[]="ERROR: first kiss photos are not from the same workorder. please flag.";
			}
			
			$photog = $photo['Asset']['iptc_ByLine'];
			if (isset($adjustments[$photog]) && $filter=='photog')
			{
				$errors[]="ERROR: first kiss photos are from same photographer, '$photog'. Please flag.";
			}
			else 
			{
				$id = (int)$photo['Asset']['id'];
				$data[$id]['workorder_id'] = $workorder_id;
				if ($filter=='camera') $data[$id]['camera'] = $photo['Asset']['iptc_Category'];
				$adjustments[$photog][$id] = $data[$id];
				
//				unset ($adjustments[$photog]['asset_id']);
			}
		}
		if (!@empty($errors)) return $errors;
		
//debug($adjustments); 

		/*
		 *  Save adjusted time to DB, Assets.sync_DateTimeOriginal
		 */
		$errors = $Asset->syncExifTime($adjustments, $filter);
		
//debug($errors);
//exit;		
		/*
		 * copy Assets.sync_DateTimeOriginal to files using JHead
		 */
		$photos = NULL;
		
		// get ALL photos by photographer/workorder with sync_DateTimeOriginal != NULL
		$photogs = array_keys($adjustments);
		$conditions = array('iptc_ByLine' => $photogs, 'workorder_id'=>$workorder_id, 'sync_DateTimeOriginal is NOT NULL');
		$photos = $Asset->find('all', array(
			'conditions' => $conditions
			, 'fields' => array('iptc_ByLine', 'iptc_Category', 'relpath', 'exif_DateTimeOriginal', 'sync_DateTimeOriginal')
			, 'recursive' => -1
		));
		
		
//		debug($photos); //exit;

		set_time_limit(0);
		// format as array of jhead commands, then execute
		App::import('Component', 'Jhead');
		$Jhead = & new JheadComponent();
		$basepath = Configure::read('Photos.workorder.basepath') . DS; 
		foreach ($photos as $photo)
		{
			if (!isset($photo['Asset']['relpath'])) debug($photo);
			$filepath = cleanPath($basepath.$photo['Asset']['relpath']);
			$file_meta = @$this->getExifIptc($filepath);
			$createNewExif = !isset($file_meta['exif_DateTimeOriginal']);
			$error = $Jhead->setExifTime($filepath, $photo['Asset']['sync_DateTimeOriginal'], $createNewExif);
//			$error = $this->__resetExifTime($filepath, $photo['exif_DateTimeOriginal'], $exifTime, & $Jhead);
			if ($error) $errors[] = $error;
		}
		if ($errors) return $errors;
	}
	
	function __resetExifTime($filepath, $dbTime, $exifTime, $Jhead=null)
	{
		$error = null;
		$createNewExif = ($exifTime==null);
		if ($dbTime != $exifTime)
		{
			if (empty($Jhead))
			{
				App::import('Component', 'Jhead');
				$Jhead = & new JheadComponent();
			}
			$error = $Jhead->setExifTime($filepath, $dbTime, $createNewExif);			
			// doublecheck result
			$file_meta = $this->getExifIptc($filepath);
			$dbTime = str_replace('-',':',$dbTime);
			if ($dbTime != $file_meta['exif_DateTimeOriginal']) {
				$error = "ERROR: problem resetting Exif time to DB stored value. filepath=$filepath";
			}
		} 
		return $error;
	}
	
	/*
	 * for use with /tags/scan output
	 * 
	 * when CPE is available, remove any chapter asset id that is not also in CPE
	 */
	function filterScannedAssetGroupsForStarred(& $asset_group,& $count)
	{
		if (isset($asset_group['CPE'])) {
			$starred_CPE = $asset_group['CPE'];
			$chapters = array('PRE','CER','POR','REC');
			
			foreach ($chapters as $chapter)
			{
				if (@empty($asset_group[$chapter])) continue;
				$filtered=array();				
				foreach ($asset_group[$chapter] as $aid)
				{
					if (array_search($aid, $starred_CPE)!==FALSE) $filtered[] = $aid;
					else $count--;
				}
				$asset_group[$chapter] = $filtered;
			}
		}
	}
	
}
?>