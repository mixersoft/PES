<?php

/**
 * Cluster class
 */
class ClusterCollage {

    protected $epsilon = 0.0000000000001;
    
    /**
     * Initial photos set (rating average resized)
     * @var array
     */
    protected $photos;
	
    /**
     * Original photos set
     * @var array
     */
    protected $originalPhotos;
    /**
     * Max crop, abs(width_1/height_1-width_0/height_0)
     * @var float
     */
    protected $cropVarianceMax  = 0.2;
    /**
     * Max result arrangement width
     * @var float
     */
    protected $maxWidth;
    /**
     * Max result arrangement height
     * @var float
     */
    protected $maxHeight;
    /**
     * Photos weights division coefficient (rule of thirds or something else)
     * @var float
     */
    protected $divisionCoefficient = 0.6666;
    
    /**
     * Allow resize arrangements that do not fit the ratios restrioctions
     * @var type 
     */
    protected $allowResize = true;
    
    /**
     * Ratios limits for the final arrangement in the form of "height:width"
     * @var array h - horizontal, v - vertical
     */
    protected $allowedRatios = array('h' => '9:16', 'v' => '16:9'); 
    
    /**
     * Costs of the expensive photos. Being file in partitionByTopRatedCutoff(...) func
     * @var array
     */
    protected $expensiveCosts = array();
    
    /**
     * Temp copy of $this->expensiveCosts could be modified during algorithm
     * @var type 
     */
    protected $expensiveCostsTemp = array();


    /**
     * Define the each initial clusters cells filling by top rated photos state
     * @var array Lenght = 2 or 3 (there are 2/3 cells initial clusters availible only)
     */
    protected $clusterFilledByTopRated = array();
    
    /**
     * Defines the initial cell algorithm being working in now
     * @var mixed null|int  null or 0-2
     */
    protected $currentInitialCell = null;
    
    /**
     * Number of the cells in the initial cluster (2 or 3 now availible only)
     * @var int 2-3
     */
    protected $initialClusterCellsNumber;
    
    /**
     * Array of pids of top rated photos
     * @var array
     */
    protected $topRated = array();
    
    /**
     * Used for calculating the whole arrangement quality coefficient
     * @var int
     */
    protected $resizeOperarionWeight = 80; // 0..100

    /**
     * Default constructor
     * 
     * @param array $photos
     * @param mixed $type null|int Cluster type. If null then assume it is initial cluster
     * @param float $maxHeight Result arrangement height
     * @param float $maxWidth Result arrangement width that should be shoosen by the predefined logic (chooseCluster function)
     */
    public function __construct($cropVarianceMax, $maxHeight = 600, $maxWidth = 800) {
        if ($maxHeight <= 0 || $maxWidth <= 0 || $cropVarianceMax <= 0 || $cropVarianceMax >= 1)
            throw new Exception('Bad parameters for ' . __CLASS__ . '::' . __FUNCTION__);
        $this->cropVarianceMax = $cropVarianceMax;
        $this->maxWidth = $maxHeight;
        $this->maxHeight = $maxHeight;
    }

    /**
     * Shuffle photos randomly within same rating groups.
     * @param array $photos
     */
    private function shuffleDIffRatingPhotos(&$photos) {
        if (0 == count($photos))
            return;
        $begin = $end = 0; $prevRating = $photos[0]['rating'];
        $shuffleRatingGroup = function (&$photos, $begin, $end) {
            $group = array_slice($photos, $begin, $end - $begin + 1);
            shuffle($group);
            array_splice($photos, $begin, $end - $begin + 1, $group);
        };
        for ($i  = 1; $i < count($photos); $i ++) {
            if ($i == count($photos) - 1) {
                if ($photos[$i]['rating'] >= $prevRating) 
                    $end = $i;
            } else if ($photos[$i]['rating'] < $prevRating) {
                if ($end != $begin)
                    $shuffleRatingGroup($photos, $begin, $end);
                $begin = $end = $i;
                $prevRating = $photos[$i]['rating'];
            } else {
                $end = $i;
            }
        }
        if ($end != $begin)
            $shuffleRatingGroup($photos, $begin, $end);
    }
    
    /**
     * Set photos to be used for arrangement, 
	 * 	this is the top-level recursion
     * 
     * @param array $photos Array of array('rating' => int = 0..5, 'width' => int > 0, 'height' => int > 0) Input photos should be arranged descending by ratings!
	 * @param $shuffledPhotos
     * @return bool
     */
    public function setPhotos(array $photos,  $method='topRatedCutoff' ) {
        $this->photos = array();
        $this->shuffleDIffRatingPhotos($photos);
        $shuffledPhotos = $photos;
        
        // Calculate ratings sum:

        $ratingsSum = 0;
        foreach ($photos as & $photo) {
        	if (empty($photo['rating'])) $photo['rating'] = 0.2;	// fixes divide by zero error
            $ratingsSum += $photo['rating'];
        }
// debug($photos);		
		if (true) {
			/*
			 * adjust resized photo to obey $minArea
			 * */
			$minArea = min(1/count($photos), 0.125);
			$this->photos = $this->resizePhotosWithMinArea($photos, $ratingsSum, $minArea);
// debug($this->photos);			
			// $this->partitionByTopRatedCutoff($this->photos, $topRated, $lowRated, 2, true);
		} else {
			/*
			 * ignore $minArea limitations
			 * */			
	        foreach ($photos as $i => $photo) {
	            $dimensions = $this->resizePhoto($photo['height'], $photo['width'], $photo['rating'], $ratingsSum);
	            $this->photos[] = array(
	                'pid' => $i,
	                'id' => $photo['id'],
	                'h' => $dimensions['h'],
	                'w' => $dimensions['w'],
	                'rating' => $photo['rating'] ? $photo['rating'] : 0.2,
	                'cost' => 0,
	                'top' => false
	            );
	        }
			// $this->partitionByTopRatedCutoff($this->photos, $topRated, $lowRated, 2, true);
		}
        switch ($method) {
            case 'topRatedCutoff': // use topRatedCutoff
            	// Divide photos to 'expensive' and 'cheap' using topRatedCutoff, 
            	// i.e. all photo['rating'] >= the topRatedCutoff are in the topRated group            
            	$this->partitionByTopRatedCutoff($this->photos, $topRated, $lowRated, 2, true);
				break;
            default:
            	$this->partitionBySortOrder($this->photos, $topRated, $lowRated, 2, true);
				break;
        }		
		return true;
    }
	
	/**
	 * 
	 * @param float $minArea minimum area of photo, regardless of rating
	 */
	protected function resizePhotosWithMinArea($photos, $ratingsSum, $minArea ) {
		$delta = $sum_min = 0; 
		$resized = $too_small = array();
		foreach ($photos as $i => & $photo) {
			$h = $photo['height'];
			$w = $photo['width'];
			$rating = $photo['rating'];
			if ($h <= 0 || $w <= 0 || $rating < 0 || $rating > $ratingsSum)
	            throw new Exception('Bad parameters for ' . __CLASS__ . '::' . __FUNCTION__);
	        $newAreaPhoto = $rating / $ratingsSum;
			$areaPhoto = $h * $w;
			$photo['newAreaPhoto'] = $newAreaPhoto;
			$photo['areaPhoto'] = $areaPhoto;
			
			if ($newAreaPhoto < $minArea ) {
				$delta += ($minArea - $newAreaPhoto);
				$sum_min += $minArea;
				$too_small[] = $i;	// index to too small item
				$coef = sqrt($areaPhoto / $minArea);
			} else {
				$coef = sqrt($areaPhoto / $newAreaPhoto);	
			}
			$resized[] = array(
                'pid' => $i,
                'id' => $photo['id'],
                'h' => $h / $coef,
                'w' => $w / $coef,
                'rating' => $photo['rating'],
                'cost' => 0,
                'top' => false
            );
		};
		if (count($too_small)) {
			// calculate adjustment to keep total area = 1.00
			$adjustment = ($ratingsSum - $sum_min)/($ratingsSum - $delta);
			foreach ($resized as $i => $photo) {
				if (in_array($i, $too_small)) continue; 	// these are too small and have already been adjusted
				$ph = & $photos[$i];
				$adjustedArea = ($ph['newAreaPhoto'] * $adjustment);
				$ph['newAreaPhoto'] = $adjustedArea;
				$coef = sqrt($ph['areaPhoto'] / $adjustedArea);	
				$resized[$i]['h'] = $ph['height']/$coef;
				$resized[$i]['w'] = $ph['width']/$coef;
			}
		}
		return $resized;
	}

    /**
     * Resizes the photo proportionally to the arrangement area = 1 considering the photo's rating
     * 
     * @param float $h
     * @param float $w
     * @param int $rating
     * @param int $ratingsSum Sum of all photos ratings
     * @return array Array('h' => float, 'w' => float)
     */
    protected function resizePhoto($h, $w, $rating, $ratingsSum) {
        if ($h <= 0 || $w <= 0 || $rating < 0 || $rating > $ratingsSum)
            throw new Exception('Bad parameters for ' . __CLASS__ . '::' . __FUNCTION__);
        $newAreaPhoto = $rating / $ratingsSum;
        $areaPhoto = $h * $w;
        $coef = sqrt($areaPhoto / $newAreaPhoto);
        return array(
            'h' => $h / $coef,
            'w' => $w / $coef
        );
    }
    
    /**
     * Get ratio coefficient by ratio string 
     * 
     * @param string $ratio, "height:width"
     * @return float 
     */
    protected function getRatioByString($ratio) {
        if (! preg_match("/^(\d+)\:(\d+)$/", $ratio, $matches) || 0 == $matches[2])
                throw new Exception('Given bad parameters at ' . __CLASS__ . '::' . __FUNCTION__);
        return $matches[1] / $matches[2];
    }
    
    /**
     * Resize arrangement calculated by $this->calculate(...)
     * 
     * @param array $arrangement
     * @return int Result code. 0 - there is no need in resizing, 1 - successfully resized, 2 - does not feet ratio restrictions and resize is not allowed,   3 - does not feet ratio restrictions and can not resize due to crop restrictions
     */
    protected function resizeArrangement(&$arrangement) {
        // Check ratio:
        
        $orientation = $this->defineOrientation($arrangement['h'], $arrangement['w']);
        $allowed = $this->getRatioByString($this->allowedRatios[$orientation]);
        $ratio = $arrangement['h'] / $arrangement['w'];
        if (('h' == $orientation && $ratio > $allowed) 
        || ('v' == $orientation && $ratio < $allowed))
            return 0;
        if (! $this->allowResize)
            return 2;
        
        // Check resize possibility:
        
        $c = $arrangement['h'] / ($arrangement['w'] * $allowed);
        $cropVariance = abs($c - 1);
        if ($cropVariance >= $this->cropVarianceMax)
            return 3;
        foreach($arrangement['roles'] as &$role) {
            if ('h' == $orientation) {
                $role['y0'] /= $c;
                $role['y1'] /= $c;
            } else {
                $role['x0'] *= $c;
                $role['x1'] *= $c;
            }
            $role['coefs']['crop'] = $cropVariance;
        }
        'h' == $orientation ? $arrangement['h'] /= $c : $arrangement['w'] *= $c;
        return 1;
    }

    /**
     * Calculate quality coefficient for the photos set
     * 
     * @param array $roles
     * @return int 
     */
    protected function calculateQuality(&$roles) {
        $q = 0;
        foreach ($roles as &$role) {
            $qR = $role['coefs']['resize'] <= 1 ? $role['coefs']['resize'] : 1 / $role['coefs']['resize'];
            $qC = 1 - $role['coefs']['crop'];
            $q += ($this->resizeOperarionWeight * $qR + (100 - $this->resizeOperarionWeight) * $qC) / 100;
        }
        return $q / count($roles);
    }
    
    /**
     * Move photo to role
     * 
     * @param array $role
     * @param int $index of $this->photos, same as $pid
     * @return bool false if not satisfies the cropVariance restriction
     */
    protected function movePhotoToRole(&$role, $index) {
        if (! isset($this->photos[$index]))
            return false;
        $photo = $this->photos[$index];
        
        // Calculate crop and resize coefficients:
        
        $W = $role['x1'] - $role['x0'];
        $H = $role['y1'] - $role['y0'];
        $C = $W / $H;
        $c = $photo['w'] / $photo['h'];
        $cR = $C > $c ? $W / $photo['w'] : $H / $photo['h']; // Resize coefficient
        $cC = $C > $c ? $H / $photo['h'] / $cR : $W / $photo['w'] / $cR; // Crop coefficient
        
        // Check crop variance:
        
        $cropVariance = $C > $c ? abs($cC - 1) : abs(1/$cC - 1);
        if ($cropVariance >= $this->cropVarianceMax)
            return false;
        
        // Move photo:
        
        $role['pid'] = $index;
		$role['photo_id'] = $photo['id'];
        $role['coefs'] = array(
            'resize' => $cR,
            'crop' => $cropVariance,
        );
        // TODO: Crop photo
        // TODO: Resize photo
        return true;
    }


    /**
     * Rearranges photos sequence (begin-end) with the same rating
     * 
     * @param array $roles
     * @param array $availibleRoles
     * @param int $begin
     * @param int $end
     * @return bool
     */
    protected function rearrangePhotos(&$roles, &$availibleRoles, $begin, $end) {
        // Define the roles candidates:

        $num = 0; $prevRole = null; $selectedRoles = array();
        $countVertical = 0; $countHorizontal = 0;
        foreach ($availibleRoles as $role) {
            if (null === $role)
                continue;
            if (null === $prevRole || $role['area'] < $prevRole['area'])
                $num ++;
            if ($num > $end - $begin + 1)
                break;
            $selectedRoles[] = $role;
            'v' == $role['orientation'] ? $countVertical ++ : $countHorizontal ++;
            $prevRole = $role;
        }

        // Check orientation:

        $countVerticalPhotos = 0; $countHorizontalPhotos = 0;
        for ($i = $begin; $i<= $end; $i ++) {
             $orientation = $this->defineOrientation($this->photos[$i]['h'], $this->photos[$i]['w']);
             'v' == $orientation ? $countVerticalPhotos ++ : $countHorizontalPhotos ++;
        }
        if ($countVerticalPhotos > $countVertical || $countHorizontalPhotos > $countHorizontal )
            return false;

        // Move photos to selected roles:

        for ($i = $begin; $i<= $end; $i ++) {

            // Get group of roles with the same areas:

            $prevRole = null; $group = array();
            $orientation = $this->defineOrientation($this->photos[$i]['h'], $this->photos[$i]['w']);
            foreach($selectedRoles as $key => $role) {
                if (null === $prevRole || $this->comp($role['area'], $prevRole['area']))
                {
                    if ($orientation == $role['orientation']) {
                        $group[] = array('role' => $role, 'key' => $key);
                        $prevRole = $role;
                    }
                } else {
                    break;
                }
            }

            do {
                // Randomly select one role from the group:
                
                if (count($group) > 0)
                    $r = $group[$idx = array_rand($group)];
                else
                    return false;
                $role = $r['role'];

                // Move current photo to this role:
                $roles[$role['rid']]['pid'] = $i;
                $res = $this->movePhotoToRole($roles[$role['rid']], $i);
                array_splice($group, $idx, 0);
            } while(! $res );
            
             // Cut this role from selectedRoles:
            array_splice($selectedRoles, $r['key'], 1);
            

            // Cut this. role from availible roles
            // We cannot splice the element from availibleRoles, because
            // the rid2 values will not coincide with real availibleRoles
            // indeces, so just set null value (should be skiped in loops):
            $availibleRoles[$role['rid2']] = null;
        }
        return true;
    }
    
    /**
     * Rearrange photos into roles accroding to photo's ratings/role's sizes
     * 
     * @param array $roles 
     */
    protected function rearrange(&$roles) {
        if (0 == count($roles))
            return;
        $begin = $end = 0;
        
        // Sort roles by rating:
        
        $sortedRoles = array();
        foreach ($roles as $key => $role) {
            $sortedRoles[] = $role;
            $sortedRoles[$key]['rid'] = $key;
            $areas[$key] = $sortedRoles[$key]['area'] = 
                ($role['x1'] - $role['x0']) * ($role['y1'] - $role['y0']);
        }
        array_multisort($areas, SORT_DESC, $sortedRoles);
        $availibleRoles = $sortedRoles;
        
        // Fill rid2 field:
        foreach ($availibleRoles as $key => &$role) {
            $role['rid2'] = $key;
        }
        
        $prevRating = $this->photos[0]['rating'];
        $count = 1;
        for ($i  = 1; $i < count($this->photos); $i ++) {
            if ($this->photos[$i]['rating'] < $prevRating) {
                if (! $this->rearrangePhotos($roles, $availibleRoles, $begin, $end))
                    return false;
                $begin = $end = $i;
                $prevRating = $this->photos[$i]['rating'];
            } else {
                $end = $i;
            }
        }
        return $this->rearrangePhotos($roles, $availibleRoles, $begin, $end);
    }

     /**
     * create arrangement for photos given in $this->photos
     * 
     * @return array Array of roles
     */
    public function getArrangement() {
         do {
            $this->calculate($this->photos, $arrangement, '', true);
            $res1 = $this->resizeArrangement($arrangement);
            $res2 = (2 != $res1 && 3 != $res1) ? $this->rearrange($arrangement['roles']) : false;
         } while (! $res2 || 2 == $res1 || 3 == $res1);
        
//         $res2 = $this->rearrange($arrangement['roles']);
         
        // Sort roles by photos ids:
        
        $maxPid = -1; $roles = $arrangement['roles'];
        for ($i = count($roles); $i >= 1; $i -- ) {
            for ($j = 1; $j < $i; $j ++) {
                if ($roles[$j - 1]['pid'] > $roles[$j]['pid']) {
                    $tempRole = $roles[$j - 1];
                    $roles[$j - 1] = $roles[$j];
                    $roles[$j] = $tempRole;
                }
            }
        }
        
        
        // Calculate quality coefficient:
        $quality = $this->calculateQuality($roles);
         
        if ($arrangement['h'] == 0 ||$arrangement['h'] == 0)
            throw new Exception('Created incorrect arrangement at ' . __CLASS__ . '::' . __FUNCTION__);
        $coef = $arrangement['h'] > $arrangement['w'] ? $this->maxHeight / $arrangement['h']
                : $this->maxWidth / $arrangement['w'];
        $newArrangement = array(
            'H' => $arrangement['h'] * $coef,
            'W' => $arrangement['w'] * $coef,
            'Roles' => array(),
            'way' => $arrangement['way'] . ', init: ' . $arrangement['init'],
            'quality' => round(10 * $quality, 1),
        );
        foreach ($roles as $role) {
            // Scaled roles:
            $newArrangement['Roles'][] = array(
                'H' => ($role['y1'] - $role['y0']) * $coef,
                'W' => ($role['x1'] - $role['x0']) * $coef,
                'X' => $role['x0'] * $coef,
                'Y' => ( $arrangement['h'] - $role['y1'] ) * $coef,
                'photo_id' => $role['photo_id'],
            );
        }
        return $newArrangement;
    }

    /**
     * Calculate Arrangement
     * 
     * @param array $photos
     * @param array Arrangement with the left-bottom corner at the (0;0). Result array of roles
     * @param string $orientation If empty string then choose random orientation (initial cluster)
     * @param string $divisionCheck Should division check be done?
     * @return bool
     */
    protected function calculate(&$photos, &$roles, $orientation, $divisionCheck = false) {
        $roles = array();
        // Check the photos number:

        $num = count($photos);
        // TODO: Choose cluster:

        if (0 == $num) {
            return false;
        } else if (1 == $num) {
            $photo = $photos[0];
            $photoOrientation = $this->defineOrientation($photo['h'], $photo['w']);
            if ($orientation && $orientation != $photoOrientation)
                return false;
            $hasTop = $photo['top'];
            $roles = array(
                'h' => $photo['h'],
                'w' => $photo['w'],
                'hasTop' => $hasTop,
                'roles' => array(
                    array(
                        'x0' => 0,
                        'y0' => 0,
                        'x1' => $photo['w'],
                        'y1' => $photo['h'],
                        'pid' => $photo['pid'],
                        'photo_id' => $photo['id'],
                        'coefs' => array('resize' => 1, 'crop' => 0),
                        'orientation' => $photoOrientation,
                    )
                ),
                'way' => $photoOrientation,
                'init' => $photoOrientation,
            );
            $clusterType = 'v' == $photoOrientation ? 0 : 1;
        } else {
            do {
                $i = 0;
                do {
                    if (!$orientation) {
                        // Initial cluster:
                        // TODO: Use exception cluster

                        $this->expensiveCostsTemp = $this->expensiveCosts;
                        $clusterType = $this->chooseClusterType('init');
                        $this->initialClusterCellsNumber = $cellsNumber = $this->defineInitialClusterCellsNumber($clusterType);
                        $this->currentInitialCell = null;
                        $this->topRated = array();
                    } else {
                        $clusterType = $this->chooseClusterType('simple', $orientation);
                        $cellsNumber = $this->defineInitialClusterCellsNumber($clusterType);
                    }
                    $res = false;
                    if (count($photos) >= $cellsNumber)
                        $res =  $this->mergeCells($photos, $roles, $clusterType, $divisionCheck);
                    // TODO: check cluster:
                    $i ++;
                } while (false === $res && $i < 1000); // While the appropriate claster will not be found
            } while (false); // While the correnct arrangement will not be found
        }
        $roles['init'] = $this->getClusterTypes($clusterType);
        $roles['init'] = $roles['init'][0];
    }

    /**
     * Define cluster's cells number
     * 
     * @param int $clusterType
     * @return int
     */
    protected function defineCellsNumber($clusterType) {
        $cluster = $this->getClusterTypes($clusterType); $cluster = $cluster[0];
        preg_match_all('/[hv]/', $cluster, $matches);
        $num = count($matches[0]);
        if (preg_match_all('/\d/', $cluster, $matches)) {
            foreach ($matches[0] as $foundedClusterType) {
                $num += $this->defineCellsNumber($foundedClusterType);
            }
        }
        return 2;
    }

    /**
     * Merge cluster cells
     * 
     * @param array $photos
     * @param array $roles Array('h' => float, 'w' => float, 'roles' => array) Resulting roles
     * @param int $type Cluster type
     * @param string $divisionCheck Should division check be done?
     * @return bool
     */
    protected function mergeCells($photos, &$roles, $type, $divisionCheck = false) {
        $roles = array();
        $cluster = $this->getClusterTypes($type); $cluster = $cluster[0];
//        $cluster = 'h-2';
//        $cluster = 'h-h';
        if (!preg_match('/^(?:h|v|\d{1,3})(?:-|\|)(?:h|v|\d{1,3})$/', $cluster))
            throw new Exception('Found Bad cluster "' . $cluster . '" at ' . __CLASS__ . '::' . __FUNCTION__);
        $length = strlen($cluster);
        $cells  = array(); $operator = '';
        
        // Calculate cells: (i.e. roles)
        for ($i = 0; $i < strlen($cluster); $i++) {
            $c = $cluster[$i];
            if ('h' == $c || 'v' == $c) {
                if (0 == $i) {
                    if (false === $this->dividePhotos($photos, $division, 'sortOrder', 2))
                        return false;
                    if (empty($division[0]['photos']) || empty($division[1]['photos']))
                        return false;
                    if ($divisionCheck) {
                         $cellsNumber = $this->defineInitialClusterCellsNumber($type);
                         $costsDistribution =  3 == $cellsNumber ? array(1, 2) : array(1, 1);
                         if (! $this->divisionCheck ($division, $costsDistribution, $results)){
debug(" ----------------->   fail on i==0 divisionCheck");
                         	return false;
                         }
                              
                         
                         // Set 'top' property for the photos:
                         
                         $results = $results[array_rand($results)];
                         $pids = $this->findPhotosByCost($division[0]['photos'], $results[0][0], array(), true);
                         $id = $pids[array_rand($pids)]['id'];
                         $division[0]['photos'][$id]['top'] = true;
                         $id = $this->findPhotoByPid($division[0]['photos'][$id]['pid']);
                         $this->topRated[] = $id;
                         if (2 == $cellsNumber) {
                             $pids = $this->findPhotosByCost($division[1]['photos'], $results[1][0], array(), true);
                             $id = $pids[array_rand($pids)]['id'];
                             $division[1]['photos'][$id]['top'] = true;
                             $id = $this->findPhotoByPid($division[1]['photos'][$id]['pid']);
                             $this->topRated[] = $id;
                         } else {
                             if (false !== ($idx = array_search($results[0][0], $this->expensiveCostsTemp)))
                                 array_splice($this->expensiveCostsTemp, $idx, 1);
                         }
                         if (null === $this->currentInitialCell)
                              $this->currentInitialCell = 0;
                    }
                    $photos = $division[1]['photos'];
                    if (false === $this->calculate($division[0]['photos'], $cell, $c)) {
debug(" ------->   fail on i==0 calculate");
                    	return false;
                    }
                } else {
                    if ($divisionCheck) {
                        if (0 === $this->currentInitialCell)
                            $this->currentInitialCell = 1;
                        else if (1 == $this->currentInitialCell)
                            $this->currentInitialCell = 2;
                    }
                    if (false === ($this->calculate($photos, $cell, $c))){
debug(" ----------------------->   fail on i > 0 calculate");                    	
                    	return false;
                    }
                }
                $cells[] = $cell;
            } else if ('-' == $c || '|' == $c) {
                $operator = $c;
            } else if (is_numeric($c)) {
                if ($divisionCheck && 0 === $this->currentInitialCell)
                    $this->currentInitialCell = 1;
                if (false === $this->mergeCells($photos, $cell, $c, $divisionCheck)) {
debug(" ----------------------->   fail on is_numeric()");                   	
                    return false;
				}
                $cells[] = $cell;
            } else {
                throw new Exception('Unexpected operaand "' . $c . '" during rule parsing at ' . __CLASS__ . '::' . __FUNCTION__);
            }
			
			if (1 || !isset($cell['h'])) {
debug(">>>>>>>>>>>>>>>> cluster={$cluster}, i={$i}, c={$c}");
debug($photos);
debug($cells[0]);	// same as arrangement
			}
        }
        
        /**
         * Merge cells:
         */
        
        // Top rated photo processing:
        if (0 == mt_rand(0, 1)) {
            $k = 0; $l = 1;
        } else {
            $k = 1; $l = 0;
        }
//        if ('|' == $operator) {
//            $coef = $cells[0]['h'] / $cells[1]['h'];
//        } else {
//            $coef = $cells[0]['w'] / $cells[1]['w'];
//        }
//        if ($coef < 1 && $cells[1]['hasTop'] && ! $cells[0]['hasTop']){
//            $k = 1; $l = 0;
//        }

        // Fit the right/bottom cell to the left/top:
        
        if ('|' == $operator) {
        	if (!isset($cells[$k]['h']) || !isset($cells[$l]['h'])  ) {
        		debug($cells);
				debug("k={$k}, l={$l}");
        		throw new Exception('Undefined index: h "' . $cluster . '" at ' . __CLASS__ . '::' . __FUNCTION__);
			}
            $coef = $cells[$k]['h'] / $cells[$l]['h'];
            foreach($cells[$l]['roles'] as &$role) {
                $role['x0'] = $coef * $role['x0'] + $cells[$k]['w'];
                $role['y0'] = $coef * $role['y0'];
                $role['x1'] = $coef * $role['x1'] + $cells[$k]['w'];
                $role['y1'] = $coef * $role['y1'];
                $role['coefs']['resize'] *= $coef;
            }
        } else { // '-'
        	if (!isset($cells[$k]['w']) || !isset($cells[$l]['w'])  ) {
debug($cells);
debug("k={$k}, l={$l}");
        		throw new Exception('Undefined index: w "' . $cluster . '" at ' . __CLASS__ . '::' . __FUNCTION__);
			}        
            $coef = $cells[$k]['w'] / $cells[$l]['w'];
            $xOffset = $cells[$k]['w'];
            foreach($cells[$l]['roles'] as &$role) {
                $role['x0'] = $coef * $role['x0'];
                $role['y0'] = $coef * $role['y0'];
                $role['x1'] = $coef * $role['x1'];
                $role['y1'] = $coef * $role['y1'];
                $role['coefs']['resize'] *= $coef;
            }
            foreach($cells[$k]['roles'] as &$role) {
                $role['y0'] += $coef * $cells[$l]['h'];
                $role['y1'] += $coef * $cells[$l]['h'];
            }
        }
        $cells[$l]['h'] *= $coef;
        $cells[$l]['w'] *= $coef;
        if ('|' == $operator) {
            $newH = $cells[$k]['h'];
            $newW = $cells[$k]['w'] + $cells[$l]['w'];
        } else {
            $newH = $cells[$k]['h'] + $cells[$l]['h'];
            $newW = $cells[$k]['w'];
        }
        
        // Top rated photo size check:
        
//        if ($cells[0]['hasTop'] xor $cells[1]['hasTop']) {
//            if ($cells[0]['hasTop'])
//                $m  = 1;
//            else
//                $m = 0;
//            $topPhoto = $this->photos[$this->topRated[$this->currentInitialCell]];
//            $topPhotoArea = $topPhoto['h'] * $topPhoto['w'];
//            foreach ($cells[$m]['roles'] as $photo) {
//                $area = ($photo['x1'] - $photo['x0']) * ($photo['y1'] - $photo['y0']);
//                if ($area > $topPhotoArea) {
//                    return false;
//                }
//            }
//                // TODO: check this role bigger of each photos in another cell
//        }
        
        // Merge cells:
        
        $hasTop = $cells[$k]['hasTop'] || $cells[$l]['hasTop'];
        $roles = array(
            'hasTop' => $hasTop,
            'h' => $newH,
            'w' => $newW,
            'roles' => array_merge($cells[$k]['roles'], $cells[$l]['roles']),
            'way' => (count($cells[$k]['roles']) > 1 ? '(' . $cells[$k]['way'] . ')' : $cells[$k]['way'])
                . $operator . (count($cells[$l]['roles']) > 1 ? '(' . $cells[$l]['way'] . ')' : $cells[$l]['way']),
        );
        
        return true;
    }

    /**
     * Define parts weights. Supports only for 2-3 parts
     * 
     * @param array $photos
     * @param int $parts Number of parts
     * @param array $partsWeights Result - array of weights
     */
    protected function definePartsWeights(&$photos, $parts, &$partsWeights, $type = null) {
        switch ($type) {
            case 'golden':
                $divisionCoefficient = 0.618;
                break;
            case 'thirds':
                $divisionCoefficient = 0.6666;
                break;
            case 'equal':
                 $divisionCoefficient = 0.5;
                break;
            default:
                break;
        }
        if ($parts < 2 || $parts > 3)
            throw new Exception('Given bad parameters at ' . __CLASS__ . '::' . __FUNCTION__);
        $weightsTotal = 0;
        foreach ($photos as $photo) {
            $weightsTotal += $photo['rating'];
        }
        $partsWeights = array();
        $partsWeights[0] = $weightsTotal * $divisionCoefficient;
        if (2 == $parts) {
            $partsWeights[1] = $weightsTotal - $partsWeights[0];
        } else {
            $partsWeights[1] = $divisionCoefficient * ($weightsTotal - $partsWeights[0]);
            $partsWeights[2] = $weightsTotal - $partsWeights[0] - $divisionCoefficient * ($weightsTotal - $partsWeights[0]);
        }
    }
    
    /**
     * Define distans to equal-weighted division for each part
     * 
     * @param type $partsWeights
     * @param int $steps
     * @param type $partsDistance 
     */
    protected function defineDistanceToEqualWeights(&$partsWeights, $steps, &$partsDistance) {
        $partsDistance = array();
        $parts = count($partsWeights);
        $aim = array_sum($partsWeights) / $parts;
        foreach($partsWeights as $weight) {
            $partsDistance[] = ($weight - $aim) / $steps;
        }
    }
    

    /**
     * Find the photo by pid
     * 
     * @param string $photo_id, UUID
     * @param array $photos If not given then $this->photos being used
     * @return array $photo
     */
     protected function getPhotoByPhotoId($photo_id, $photos = null) {
		if (empty($this->lookupPhotoById)) {
			if ($photos === null) $photos = $this->photos;
			foreach ($photos as & $photo) {
				$this->lookupPhotoById[$photo['id']] = $photo;
			}
		}
		return $this->lookupPhotoById[$photo_id];
	}	
	
    /**
     * Find the photo by pid
     * 
     * @param int $pid
     * @param array $photos If not given then $this->photos being used
     * @return int needle photo's id
     */
    protected function findPhotoByPid($pid, $photos = null) {
        if (! is_array($photos)) {
            $photos = $this->photos;
		}
		if (isset($photos[$pid]['pid']) && $photos[$pid]['pid'] == $pid) {
			// this seems to always be true. faster
			return $pid;
		}
		foreach ($photos as $key => &$photo) {
            if ($photo['pid'] == $pid){
				return $key;
            }
        }
        return -1;
    }
    
    /**
     * partition photos into TopRated and LowRated arrays based on topRatedCutoff value, cluster cell count
     * 
     * @param array $photos
     * @param array $topRated OUTPUT array , 
	 * 			array of photos with ratings >= the lowest DISTINCT rating value of the top N rated photos, where N=$parts
	 * 			example: [5,5,4,4,4,3,2,1], 
	 * 				for $parts=2 => topRated=[5,5], 
	 * 				for $parts=3 => topRated=[5,5,4,4,4]
     * @param array $lowRated OUTPUT array, array of photos not in $topRated
     * @param int $parts, number of cells in current cluster, 2 or 3
     * @param array $setPhotosCost If true set the "cost" for each expensive photo. 
	 * 		Used to arrange the top rated photos into the best cells
     */
    protected function partitionByTopRatedCutoff(&$photos, &$topRated, &$lowRated, $parts, $setPhotosCost = false) {
    	if ($setPhotosCost)
            $this->expensiveCosts = array();
        if (count($photos) < 2) {
            if (! $setPhotosCost)
                throw new Exception('Given bad parameters at ' . __CLASS__ . '::' . __FUNCTION__);
            else
                return;
        } else if ($setPhotosCost) {
            $parts = count($photos) == 2 ? 2 : 3;
        }
		if ($parts > count($photos))
			throw new Exception('Given bad parts value at ' . __CLASS__ . '::' . __FUNCTION__);
				
    	$topRated = $lowRated = $ratingValues = array();
		$currentCost = $currentTopRatedValue = 0;
				
		// SKIP partitionByTopRatedCutoff processing in recursive levels
		if (true && count($photos) < count($this->photos)) {
			$topRated = array_slice($photos, 0, $parts);
			$lowRated = array_slice($photos, $parts);
			if ($setPhotosCost) {
				foreach ($topRated as $i => $photo) {
					if ($photo['rating'] != $currentTopRatedValue) $currentCost++;	// incr currentCost when ratingValue changes
					$currentTopRatedValue = $photo['rating'];
		            if (($id = $this->findPhotoByPid($photo['pid'])) >= 0) {
		            	// NOTE: $photos not necessarily the same as $this->photos, update source
		                $this->photos[$id]['cost'] = $currentCost;	// cost is higher when rating is lower
		                $this->expensiveCosts[] = $currentCost;
		            }
		        }				
			}			
			return;
		}

		
		// get cutoff value for topRated/lowRated partitioning
		foreach ($photos as $photo) {
			$ratingValues[]=$photo['rating'];
		}
		arsort($ratingValues); 	// sort DESC
		$topRatedCutoff = $ratingValues[$parts-1]; 
		
		// partition photos based on $topRatedCutoff
		// WARNING: assumes $photos is sorted by $photos['rating'] DESC
		foreach ($photos as $i => $photo) {
			if ($photo['rating'] < $topRatedCutoff) {
				$lowRated[] = $photo;
			} else {
				if ($photo['rating'] != $currentTopRatedValue) $currentCost++;	// incr currentCost when ratingValue changes
				$topRated[] = $photo;	
				$currentTopRatedValue = $photo['rating'];
		        if ($setPhotosCost) {
		            if (($id = $this->findPhotoByPid($photo['pid'])) >= 0) {
		            	// NOTE: $photos not necessarily the same as $this->photos, update source
		                $this->photos[$id]['cost'] = $currentCost;	// cost is higher when rating is lower
		                $this->expensiveCosts[] = $currentCost;
		            }
		        }				
			};
		}
	}
    /**
     * partition photos into TopRated and LowRated arrays based on sort order
	 * 	assumes $photos is sorted $photo[rating] DESC
     * 
     * @param array $photos
     * @param array $topRated OUTPUT array, N top rated photos, where N = $parts 
     * @param array $lowRated OUTPUT array, array of photos not in $topRated
     * @param int $parts, number of cells in current cluster, 2 or 3
     * @param array $setPhotosCost If true set the "cost" for each expensive photo. 
	 * 		Used to arrange the top rated photos into the best cells
     */
    protected function partitionBySortOrder(&$photos, &$topRated, &$lowRated, $parts, $setPhotosCost = false) {
			$topRated = array_slice($photos, 0, $parts);
			$lowRated = array_slice($photos, $parts);
			$currentTopRatedValue = -1;
			$currentCost = 0;
			if ($setPhotosCost) {
				foreach ($topRated as $i => $photo) {
					if ($photo['rating'] != $currentTopRatedValue) $currentCost++;	// incr currentCost when ratingValue changes
					$currentTopRatedValue = $photo['rating'];
		            if (($id = $this->findPhotoByPid($photo['pid'])) >= 0) {
		            	// NOTE: $photos not necessarily the same as $this->photos, update source
		                $this->photos[$id]['cost'] = $currentCost;	// cost is higher when rating is lower
		                $this->expensiveCosts[] = $currentCost;
		            }
		        }				
			}			
			return;    	
	}
	
	/**
	 * @deprecated use partitionByTopRatedCutoff instead
	 */
    protected function separateExpensive(&$photos, &$expensive, &$cheap, $parts, $setPhotosCost = false) {
        if ($setPhotosCost)
            $this->expensiveCosts = array();
        if (count($photos) < 2) {
            if (! $setPhotosCost)
                throw new Exception('Given bad parameters at ' . __CLASS__ . '::' . __FUNCTION__);
            else
                return;
        } else if ($setPhotosCost) {
            $parts = count($photos) == 2 ? 2 : 3;
        }
        $previousRating = $ratingsSum = $photos[0]['rating'];
        $expensive = array();
        $cheap = $photos;
        $expensive[] = array_shift($cheap);
        $currentCost = 1;
        if ($setPhotosCost) {
            if (($id = $this->findPhotoByPid($expensive[count($expensive) - 1]['pid'])) >= 0) {
                $this->photos[$id]['cost'] = $currentCost;
                $this->expensiveCosts[] = $currentCost;
            }
        }
        $i = 0;
        foreach ($photos as $i => $photo) {
            if (0 == $i)
                continue;
            if ($photos[$i]['rating'] < $previousRating) {
                if (count($expensive) >= $parts) {
                    break;
                } else { // New rating group of expensiv photos
                    $previousRating = $photos[$i]['rating'];
                    $ratingsSum += $photos[$i]['rating'];
                    $expensive[] = array_shift($cheap);
                    $currentCost ++;
                    if ($setPhotosCost) {
                        if (($id = $this->findPhotoByPid($expensive[count($expensive) - 1]['pid'])) >= 0) {
                            $this->photos[$id]['cost'] = $currentCost;
                            $this->expensiveCosts[] = $currentCost;
                        }
                    }
                }
            } else {
                $ratingsSum += $photos[$i]['rating'];
                $expensive[] = array_shift($cheap);
                if ($setPhotosCost) {
                    if (($id = $this->findPhotoByPid($expensive[count($expensive) - 1]['pid'])) >= 0) {
                        $this->photos[$id]['cost'] = $currentCost;
                        $this->expensiveCosts[] = $currentCost;
                    }
                }
            }
            $i++;
        }
    }
    
    /**
     * partition photos into N parts, where N=$parts using requested partition algorithm, 
	 * return partition in $division
     * 
     * @param array $photos
     * @param array $division Resulting division
     * @param string $method partition algorithm 
     * @param int $parts Number of desired partitions, aka cluster cells. 2 or 3
     * @return bool
     */
    protected function dividePhotos(&$photos, &$division, $method = 'topRatedCutoff', $parts = 2) {
        if ($parts < 2 || $parts > 3)
            throw new Exception('Given bad parameters at ' . __CLASS__ . '::' . __FUNCTION__);
        if (count($photos) < $parts)
            return false;
        $division = array();
        for ($i = 0; $i < $parts; $i++) {
            $division[] = array(
                'weight' => 0,			// cumulative rating of photos in this partition
                'photos' => array(),
            );
        }
        
        switch ($method) {
            case 'topRatedCutoff': // use topRatedCutoff
            	// Divide photos to 'expensive' and 'cheap' using topRatedCutoff, 
            	// i.e. all photo['rating'] >= the topRatedCutoff are in the topRated group            
            	$this->partitionByTopRatedCutoff($photos, $topRated, $lowRated, $parts);
				break;
            default:
            	$this->partitionBySortOrder($photos, $topRated, $lowRated, $parts);
				break;
        }
        // Divide all photos:

        $i = 0;
        $rest = false;
        
         // Define parts weights:
        $this->definePartsWeights($photos, $parts, &$partsWeights, 'golden');
        $rand = mt_rand(0, 1);
//                $rand = 0;
           
        $steps = 3; $step = 0;
        $this->defineDistanceToEqualWeights($partsWeights, $steps, $partsDistances);
        while (count($topRated) > 0 || count($lowRated) > 0) {
            $i %= $parts;
//                    if ($rest) {
//                        if (count($topRated) > 0) {
//                            $division[$i]['weight'] += $topRated[0]['rating'];
//                            $division[$i]['photos'][] = array_shift($topRated);
//                        } else if (count($lowRated) > 0) {
//                            $division[$i]['weight'] += $lowRated[0]['rating'];
//                            $division[$i]['photos'][] = array_shift($lowRated);
//                        }
//                    } else {
                if (count($topRated) > 0 && $division[$i]['weight'] +
                $topRated[0]['rating'] <= $partsWeights[$i] + abs($partsDistances[$i])) {
                    $division[$i]['weight'] += $topRated[0]['rating'];
                    $division[$i]['photos'][] = array_shift($topRated);
                } else if (count($lowRated) > 0 && $division[$i]['weight'] +
                $lowRated[0]['rating'] <= $partsWeights[$i]) {
                    $division[$i]['weight'] += $lowRated[0]['rating'];
                    $division[$i]['photos'][] = array_shift($lowRated);
                } else if ($i == $parts - 1){
                    foreach($partsWeights as $idx => &$weight) {
                        $weight = $rand ? $weight - $partsDistances[$idx] : $weight + $partsDistances[$idx];
                    }
                    $rest = true;
                }
//                    }
            $i++;
        }
        return true;
    }
    
    /**
     * Find photo by cost
     * 
     * @param array $photos
     * @param int $cost
     * @param array $except Array of pids
     * @param bool $all Find all matching photos
     * @return mixed int|array pid(s), -1 or empty array() if not found
     */
    protected function findPhotosByCost(&$photos, $cost, $except = array(), $all = false) {
        $res = $all ? array() : -1;
        foreach($photos as $key => $photo) {
            if ($photo['cost'] == $cost && !in_array($photo['pid'], $except))
                if ($all)
                    $res[] = array('id' => $key, 'pid' => $photo['pid']);
                else
                    return  $photo['pid'];
        }
        return $res;
    }
    
    /**
     * Check if the best photos have been distributed evently during division
     * 
     * @param array $division
     * @param array $costsDistribution For example Array(1, 2)
     * @param array $results Availible top photos distributions
     * @return bool true if results are not empty
     */
    protected function divisionCheck(&$division, $costsDistr, &$results) {
        $results = array();
        if (count($costsDistr) != 2 || $costsDistr != array(1,1) && $costsDistr != array(1,2))
            throw new Exception('Bad parameters for ' . __CLASS__ . '::' . __FUNCTION__);
        $sum = array_sum($costsDistr);
        $costs = array_slice($this->expensiveCostsTemp, 0, $sum);
        $found = array(0 => array(), 1 => array());
        $except  = array(0 => array(), 1 => array());
        
        // Define which costs are in the parts:
        foreach($costs as $cost) {
           if (($pid = $this->findPhotosByCost($division[0]['photos'], $cost, $except[0])) >= 0) {
               $except[0][] = $pid;
               $found[0][] = $cost;
           }
           if (($pid = $this->findPhotosByCost($division[1]['photos'], $cost, $except[1])) >= 0) {
               $except[1][] = $pid;
               $found[1][] = $cost;
           }
        }
        
        // Find all different possible 2-part divisions:
        
        $divisions = array();
        for($i = 0; $i < count($costs); $i ++ ) {
            $tempDivision = array(0 => array(), 1 => array());
            $tempDivision[0][] = $costs[$i];
            for($j = 0; $j < count($costs); $j ++) {
                if ($j == $i)
                    continue;
                $tempDivision[1][] = $costs[$j];
            }
            sort($tempDivision[1]);
            $check = true;
            foreach($divisions as $d) {
                if ($d == $tempDivision) {
                    $check = false;
                    break;
                }
            }
            if ($check)
                $divisions[] = $tempDivision;
        }

        // Division check:
        
        foreach($divisions as $d) {
            $tempFound = $found;
            if (in_array($d[0][0], $tempFound[0])) {
                 if (isset($d[1][0]) && false !== ($pos = array_search($d[1][0], $tempFound[1]))) {
                    array_splice($tempFound[1], $pos, 1);
                    if (2 == count($d[1]) && ! in_array($d[1][1], $tempFound[1])) {
                        continue;
                    }
                    $results[] = $d;
                 }
            }
            
        }
        return count($results) > 0;
    }

    /**
     * Returns availible cluster types
     * v means vertical, h - horizontal, '-' vertical merging, '|' horizontal merging, 
     * digit means another cluster number.
     * Each rule can consist not more than two operands of.
     * TODO: Each operands could be switched along the '-' adn '|' axis within an expression,
     * produsing new combinations
     * 
     * @param mixed $clusterType int|null If null then get all availible clusters
     * @return mixed array of arrays|Array(string => expression, string(h|v|x - undefined) => cluster's orientation)|false
     */
    public static function getClusterTypes($clusterType = null) {
        // Change this values very carefully. It can cause an incorrect system working:
        $clusters = array(
            // Base types (0-1):

            0 => array('v', 'v'),
            1 => array('h', 'h'),
            // Simple types (2-10):
            // Horizontal:

            2 => array('v|v', 'h'),
            3 => array('h|h', 'h'),
            4 => array('h|v', 'h'),
            5 => array('v|h', 'h'),
            // Vertical:

            6 => array('h-h', 'v'),
            7 => array('v-v', 'v'),
            8 => array('v-h', 'v'),
            9 => array('h-v', 'v'),
            // Complex types that can be used only as initial(the previous types ids could be used in expression)
            // Horizontal(11-50):
            11 => array('v|6', 'h'),
//            12 => array('3|v', 'h'),
            // Vertical(51-99):
            51 => array('h-2', 'v'),
//            52 => array('2-h', 'v'),
            // Exceptions (100-):

            100 => array('!grid!', 'x'),
            101 => array('!window!', 'x'),
        );
        if (null === $clusterType) {
            return $clusters;
        } else if (array_key_exists($clusterType, $clusters)) {
            return $clusters[$clusterType];
        } else {
            throw new Exception('Trying to access to not existing "'. $clusterType .'" cluster in ' . __CLASS__ . '::' . __FUNCTION__);
        }
    }

    /**
     * Choose cluster type
     * 
     * @param string $param 'init' - type for initial collage, 'simple' or not specified - only simple types, 'exception' - only exceptions
     * @param string $orientation ''|h|v
     * @return int
     */
    public static function chooseClusterType($param = '', $orientation = '') {
        switch ($param) {
            case 'init':
                switch ($orientation) {
                    case 'h':
                        $types = array(2, 2, 4, 5, 11);
                        break;
                    case 'v':
                        $types = array(6, 6, 8, 9, 51);
                        break;
                    default:
                        $types = array(2, 2, 4, 5, 6, 6, 8, 9, 11, 11, 51, 51);
//                        $types = array(9);
                        break;
                }
                break;
            case 'exception':
                $types = range(100, 101);
                break;
            case 'simple':
            default:
                switch ($orientation) {
                    case 'h':
                        $types = array(2, 2, 3, 4, 5);
                        break;
                    case 'v':
                        $types = array(6, 6, 7, 8, 9);
                        break;
                    default:
                        $types = array(2, 2, 3, 4, 5, 6, 6, 7, 8, 9);
                        break;
                }
                break;
        }
        $idx = mt_rand(0, count($types) - 1);
        return $types[$idx];
    }

    /**
     * Define photo's/role's orientation
     * Accepts two forms of input data:
     * 1. x0, y0, x1, y1 
     * 2. H, W
     * 
     * @param float $p1 x0 if 3,4 parameters given or height
     * @param float $p2 y0 if 3,4 parameters given or width
     * @param float $x1 x1
     * @param float $y1 y1
     * @return string h|v|x - square
     */
    protected function defineOrientation($p1, $p2, $x1 = null, $y1 = null) {
        $h = null === $x1 ? $p1 : $y1 - $p2;
        $w = null === $x1 ? $p2 : $x1 - $p1;
        return $h > $w ? 'v' : ($h < $w ? 'h' : 'x');
    }
    
    /**
     * Define the initial cluster cells number (for 2-3 cells clusters working now only)
     * 
     * @param int $clusterType
     * @return int
     */
    protected function 
    defineInitialClusterCellsNumber($clusterType) {
        $cluster = $this->getClusterTypes($clusterType);
        return preg_match('/\d/', $cluster[0]) ? 3 : 2;
    }
    
     /**
     * Check the float values for equality
     * 
     * @param float $a
     * @param float $b
     * @return bool 
     */
    protected function comp($a, $b) {
        return ($b >= $a - $this->epsilon && $b <= $a + $this->epsilon);
    }

}