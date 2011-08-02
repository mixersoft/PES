<?php
/**
 * Collage Calculation Alrorythm
 */

class Collage
{
    protected $_epsilon = 0.0000000000001;
    /**
     * Height of the result arrangement
     * @var int 
     */
    protected $_height;
    
    /**
     * Width of the result arrangement
     * @var int 
     */
    protected $_width;
    
    /**
     * Rectangle width
     * @var float
     */
    protected $_xMax;
    
    /**
     * Rectangle height
     * @var float
     */
    protected $_yMax;
    
    /**
     * x step size for the algorythm of determining of the best role
     * @var float 
     */
    protected $_xStep = 0.01;
    
    /**
     * y step size for the algorythm of determining of the best role
     * @var float 
     */
    protected $_yStep = 0.01;
    
    /**
     * @var float 0 <= $_cropVariance <= 1
     */
    protected $_cropVarianceMax;
    
    /**
     * Normalized photos
     * @var array Array of array('rating' => int, 'w' => int > 0, 'h' => int > 0)
     */
    protected $_photos = array();
    
    /**
     * Roles
     * @var array Array of coordinates of left bottom and right top corners of roles. Array of array('x0' => float,  'y0' => float, 'x1' => float, 'y1' => float)
     */
    protected $_roles = array();
    


    /**
     * Default constructor
     * 
     * @param float $h Height of result arrangement.
     * @param float $w Width of result arrangement.
     * @param float $cropVarianceMax.
     * @param mixed $photos null|array
     */
    public function __construct($h, $w, $cropVarianceMax, $photos = null) {
        if ($h <= 0 || $w <= 0 || $cropVarianceMax <= 0 || $cropVarianceMax >= 1)
            throw new Exception('Bad parameters for ' . __CLASS__ . '::'  . __FUNCTION__);
        $this->_height = $h;
        $this->_width = $w;
        $this->_cropVarianceMax = $cropVarianceMax;
        $ratio = $h / $w;
        $this->_xMax = $h > $w ? $ratio : 1;
        $this->_yMax = $h > $w ? 1 : $ratio;
        if (is_array($photos))
            $this->setPhotos ($photos);
        
    }
    
    /**
     * Set photos
     * 
     * @param array $photos Array of array('rating' => int = 0..5, 'width' => int > 0, 'height' => int > 0)
     * @return bool
     */
    public function setPhotos(array $photos) {
        $this->_photos = array();
        
        // Calculate ratings sum:
        
        $ratingsSum = 0;
        foreach($photos as $photo) {
            $ratingsSum += $photo['rating'];
        }
        
        foreach($photos as $photo) {
            $dimensions = $this->_resizePhoto($photo['height'], $photo['width'], 
                    $photo['rating'], $ratingsSum);
            $this->_photos[] = array(
                'h' => $dimensions['h'],
                'w' => $dimensions['w'],
                'rating' => $photo['rating']
            );
        }
        return true;
    }
    
    
    /**
     * Calculate arrangement
     * 
     * @return array Array ('H' => arrangement height, 'W' => arrangement width, 
     * array('H' => role height, 'W' => role width, 'X' => role's top left corner 
     * x coordinate, 'Y' => role's top left corner y coordinate) - array of roles)
     */
    public function getArrangement() {
        $this->_calculateArrangement();
        $roles = array();
        $coef = $this->_height > $this->_width ? $this->_height : $this->_width; 
        foreach($this->_roles as $role) {
            // Scaled roles:
            $roles[] = array(
                'H' => ($role['y1'] - $role['y0']) * $coef,
                'W' => ($role['x1'] - $role['x0']) * $coef,
                'X' => $role['x0'] * $coef,
                'Y' => ( $this->_yMax - $role['y1'] ) * $coef
            );
        }
        return array(
            'H' => $this->_height,
            'W' => $this->_width,
            'Roles' => $roles
        );
    }
    
    /**
     * Calculate roles by photos
     */
    protected function _calculateArrangement() {
        $this->_roles = array();
        
        // Set roles:
        foreach ($this->_photos as $photo) {
            $this->_roles[] = $this->_getBestRole($photo);
        }
        
        // Stretch roles:
        $j = 0;
        do {
            $j ++;
            $check = false;
            for ($i = count($this->_roles) - 1; $i >= 0; $i--) {
                $check |= $this->_stretchRole($i);
            }
        } while ($check && $j <= 10);
        
        // "Close" the holes:
        
//        for ($i = count($this->_roles) - 1; $i >= 0; $i--) {
//            $check |= $this->_closeTheHoles($i);
//        }
        
    }
    
    /**
     * Resizes the photo proportionally to the arrangement size considering the photo's rating
     * @param float $h
     * @param float $w
     * @param int $rating
     * @param int $ratingsSum Sum of all photos ratings
     * @return array Array('h' => float, 'w' => float)
     */
    protected function _resizePhoto($h, $w, $rating, $ratingsSum) {
        if ($h <= 0 || $w <= 0 || $rating < 0 || $rating > $ratingsSum)
            throw new Exception('Bad parameters for ' . __CLASS__ . '::'  . __FUNCTION__);
        $percent = $rating / $ratingsSum;
        $areaPhoto = $h * $w;
        $areaArrangement = $this->_xMax * $this->_yMax;
        $newAreaPhoto = $percent * $areaArrangement;
        $coef = sqrt($areaPhoto / $newAreaPhoto);
        return array(
            'h' => $h / $coef,
            'w' => $w / $coef
        );
    }
    
    /**
     * Get the best role for the photo
     * 
     * @param array $photo Array of array('rating' => int, 'w' => int > 0, 'h' => int > 0)
     * @return array Array( 'x0', 'y0', 'x1', 'y1' )
     */
    protected function _getBestRole($photo) {  
        $bestRole = array(
            'x0' => 0,
            'y0' => 0,
            'x1' => $photo['w'],
            'y1' => $photo['h'],
        );
        $maxError = $minError = $this->_xMax * $this->_yMax;
        
        // Walk thru the all arrangement points with specified steps:
        
        for ($y = $this->_yMax; $y >= 0; $y-=$this->_yStep) {
            for ($x = 0; $x <= $this->_xMax; $x+=$this->_xStep) {
                if ( $this->_comp($y, 0.13) && /*$x<= 0.45 && */ $x >= 0.52) {
                    $stop = 1;
                }
                $res = $this->_getError($x, $y, $photo['h'], $photo['w']);
                if (false === $res)
                    continue;
                if ($res['error'] < $minError) {
                    $bestRole['x0'] = $res['coordinates']['x0'];
                    $bestRole['y0'] = $res['coordinates']['y0'];
                    $bestRole['x1'] = $res['coordinates']['x1'];
                    $bestRole['y1'] = $res['coordinates']['y1'];
                    $temp = array(
                        'x' => $x,
                        'y' => $y
                    );
                    $minError = $res['error'];
                }
            }
        }
        if ($minError == $maxError) {
            return array(
                'x0' => -1,
                'y0' => -1,
                'x1' => -1,
                'y1' => -1,
            );
        } 
        return array(
            'x0' => $bestRole['x0'],
            'y0' => $bestRole['y0'],
            'x1' => $bestRole['x1'],
            'y1' => $bestRole['y1'],
        );
    }
    
    /**
     * Calculate the measure of how the specified rectangle is bad
     * 
     * @param float $x X coordinate of center
     * @param float $y Y coordinate of center
     * @param float $h
     * @param float $w
     * @return mixed false - when this position does not satisfy by some reasons|
     * Array('error' => float, 'c' => float, 'coordinates' => array of coordinates) 
     * where c is the stretch coefficient
     */
    protected function _getError($x, $y, $h, $w) {
        $photoRatio = $w / $h;
        $coordinates = $this->_getCoordinatesByCenter($x, $y, $h, $w);
        
        $error = 0;
        
        // Calculate the error for intersection with arrangement:
        
        $crop = $this->_getOuterCrop($coordinates['x0'], $coordinates['y0'], 
                $coordinates['x1'], $coordinates['y1'], 0, 0, $this->_xMax, $this->_yMax, $photoRatio);
        if (false === $crop)
            return false;
        $error = $crop['area'];
        $coordinates = $crop['coordinates'];
        
        // Calculate the error for intersection with each existing roles:
        foreach($this->_roles as $role) {
            $crop = $this->_getInnerCrop($coordinates['x0'], $coordinates['y0'], 
                    $coordinates['x1'], $coordinates['y1'], $role['x0'], $role['y0'],
                    $role['x1'], $role['y1'], $photoRatio);
            if (false === $crop)
                return false;
            $error += $crop['area'];
            $coordinates = $crop['coordinates'];
        }
        return array(
            'error' => $error, 
            'c' => 1,
            'coordinates' => $coordinates
        );
    }
    
    /**
     * Get coordinates of square by coordinates of its center and dimensions
     * 
     * @param type $x X coordinate of center
     * @param type $y Y coordinate of center
     * @param type $h
     * @param type $w
     * @return type 
     */
    protected function _getCoordinatesByCenter($x, $y, $h, $w) {
        return array(
            'x0' => $x - $w/2,
            'x1' => $x + $w/2,
            'y0' => $y - $h/2,
            'y1' => $y + $h/2
        );
    }
    
    /**
     * Get crop of the photo and arrangement
     * 
     * @param float $x0 photo's x0
     * @param float $y0 photo's y0
     * @param float $x1 photo's x1
     * @param float $y1 photo's y1
     * @param float $X0
     * @param float $X1
     * @param float $Y0
     * @param float $Y1
     * @param float $photoRatio
     * @return array Array('area' - crop area, 'x0', 'y0', 'x1', 'y1' - cropped photo)
     */
    protected function _getOuterCrop($x0, $y0, $x1, $y1, $X0, $Y0, $X1, $Y1, $photoRatio) {
        $xIn0 = $x0 < $X0 ? $X0 : $x0;
        $xIn1 = $x1 > $X1 ? $X1 : $x1;
        $yIn0 = $y0 < $Y0 ? $Y0 : $y0;
        $yIn1 = $y1 > $Y1 ? $Y1 : $y1;
        $coordinates = array(
                'x0' => $xIn0,
                'y0' => $yIn0,
                'x1' => $xIn1,
                'y1' => $yIn1
            );
        
        // Check cropVariance:
        
        $roleRatio = ($coordinates['x1']-$coordinates['x0']) 
                / ($coordinates['y1']-$coordinates['y0']);
        $cropVariance = abs($photoRatio / $roleRatio - 1);
        if ( $cropVariance >= $this->_cropVarianceMax )
            return false;
        
        return array(
            'area' => round(($x1 - $x0)*($y1 - $y0) - ($xIn1 - $xIn0) * ($yIn1 - $yIn0), 9),
            'coordinates' => $coordinates
        );
    }
    
    /**
     * Get crop of the photo and role
     * 
     * @param float $x0 photo's x0
     * @param float $y0 photo's y0
     * @param float $x1 photo's x1
     * @param float $y1 photo's y1
     * @param float $X0
     * @param float $X1
     * @param float $Y0
     * @param float $Y1
     * @param float $photoRatio
     * @return mixed false|Array('area' - crop area, 'x0', 'y0', 'x1', 'y1' - cropped photo)
     */
    protected function _getInnerCrop($x0, $y0, $x1, $y1, $X0, $Y0, $X1, $Y1, $photoRatio) {
        if ($x0 >= $X0 && $y0 >= $Y0 && $x1 <= $X1 && $y1 <= $Y1) // Photo is fully nested in tole
            return false;
        if (($Y0 <= $y0 && $y0 <= $Y1) || ($Y0 <= $y1 && $y1 <= $Y1)) {
            $cropX0 = ($x0 < $X0 && $X0 < $x1) ? round(($x1 - $X0) * ($y1 - $y0), 9) : 0;
            $cropX1 = ($x0 < $X1 && $X1 < $x1) ? round(($X1 - $x0) * ($y1 - $y0),9) : 0;
        } else {
            $cropX0 = 0;
            $cropX1 = 0;
        }
        if (($X0 <= $x0 && $x0 <= $X1) || ($X0 <= $x1 && $x1 <= $X1)) {
            $cropY0 = ($y0 < $Y0 && $Y0 < $y1) ? round(($x1 - $x0) * ($y1 - $Y0),9) : 0;
            $cropY1 = ($y0 < $Y1 && $Y1 < $y1) ? round(($x1 - $x0) * ($Y1 - $y0),9) : 0;
        } else {
            $cropY0 = 0;
            $cropY1 = 0;
        }
        $crops = array($cropX0, $cropX1, $cropY0, $cropY1);
        $minCrop = max($crops);
        foreach($crops as $crop) {
            if ($crop > 0 && $crop < $minCrop)
                $minCrop = $crop;
        }
        if ( 0 == $minCrop)
            $coordinates = array(
                'x0' => $x0,
                'y0' => $y0,
                'x1' => $x1,
                'y1' => $y1
            );
        else if ($cropX0 == $minCrop)
            $coordinates = array(
                'x0' => $x0,
                'y0' => $y0,
                'x1' => $X0,
                'y1' => $y1
            );
        else if ($cropX1 == $minCrop)
            $coordinates = array(
                'x0' => $X1,
                'y0' => $y0,
                'x1' => $x1,
                'y1' => $y1
            );
        else if ($cropY0 == $minCrop)
            $coordinates = array(
                'x0' => $x0,
                'y0' => $y0,
                'x1' => $x1,
                'y1' => $Y0
            );
        else if ($cropY1 == $minCrop)
            $coordinates = array(
                'x0' => $x0,
                'y0' => $Y1,
                'x1' => $x1,
                'y1' => $y1
            );
        
        // Check cropVariance:
        
       $roleRatio = ($coordinates['x1']-$coordinates['x0']) 
                / ($coordinates['y1']-$coordinates['y0']);
        $cropVariance = abs($photoRatio / $roleRatio - 1);
        if ( $cropVariance >= $this->_cropVarianceMax )
            return false;
        
        return array(
            'area' => $minCrop,
            'coordinates' => $coordinates
        );
    }
    
    /**
     * Stretch role:
     * 
     * @param int $roleId
     * @return bool
     */
   protected function _stretchRole($roleId) {
       // Define neighbors:
       
       $role = $this->_roles[$roleId];
       
       // Set the arrangement's borders as initial neighbors
       $xN0 = 0;
       $xN1 = $this->_xMax;
       $yN0 = 0;
       $yN1 = $this->_yMax;
       
       foreach ($this->_roles as $key => $checkRole) {
           if ($key == $roleId)
               continue;
           
           if ((($checkRole['y0'] <= $role['y0'] && $role['y0'] <= $checkRole['y1'])
                   || ($checkRole['y0'] <= $role['y1'] && $role['y1'] <= $checkRole['y1'])
                   || ( $checkRole['y0'] >= $role['y0'] && $role['y1'] >= $checkRole['y1']))
                    && !($role['y1'] == $checkRole['y0'] || $role['y0'] == $checkRole['y1'])) {
               $xN0 = $checkRole['x1'] <= $role['x0'] && $checkRole['x1'] > $xN0 ? $checkRole['x1'] : $xN0;
               $xN1 = $checkRole['x0'] >= $role['x1'] && $checkRole['x0'] < $xN1 ? $checkRole['x0'] : $xN1;
           }
           if ((($checkRole['x0'] <= $role['x0'] && $role['x0'] <= $checkRole['x1'])
                   || ($checkRole['x0'] <= $role['x1'] && $role['x1'] <= $checkRole['x1'])
                   || ($checkRole['x0'] >= $role['x0'] && $role['x1'] >= $checkRole['x1']))
                   && ! ($role['x1'] == $checkRole['x0'] || $role['x0'] == $checkRole['x1'])) {
               $yN0 = $checkRole['y1'] <= $role['y0'] && $checkRole['y1'] > $yN0 ? $checkRole['y1'] : $yN0;
               $yN1 = $checkRole['y0'] >= $role['y1'] && $checkRole['y0'] < $yN1 ? $checkRole['y0'] : $yN1;
           }
       }
       
       // Arrange the neighbors:
       
       $neighbors = array();
       $this->_comp($yN1, $this->_yMax) ? array_unshift($neighbors, 'yN1') : array_push($neighbors, 'yN1');
       $this->_comp($xN0, 0) ? array_unshift($neighbors, 'xN0') : array_push($neighbors, 'xN0');
       $this->_comp($yN0, 0) ? array_unshift($neighbors, 'yN0') : array_push($neighbors, 'yN0');
       $this->_comp($xN1, $this->_xMax) ? array_unshift($neighbors, 'xN1') : array_push($neighbors, 'xN1');
       
       // Stretch role to the neighbors as long as possible (taking in account the cropVarianceMax coefficient):
       
       $newRole = $role;
       $check = false;
       $c = 10;
       foreach ($neighbors as $neighbor) {
           switch ($neighbor) {
               case 'xN0':
                   $c = ($newRole['y1'] - $newRole['y0']) * ($this->_photos[$roleId]['w'] / $this->_photos[$roleId]['h'])
                       / (1 - $this->_cropVarianceMax);
                   $xSL0 = $newRole['x1'] - $c;
                   $xN0 = $xN0 > $xSL0 ? $xN0 : $xSL0;
                   if ($newRole['x0'] - $xN0 > $this->_epsilon * $c)
                       $check = true;
                   $newRole['x0'] = $xN0;
                   break;
               case 'xN1':
                   $c = ($newRole['y1'] - $newRole['y0']) * ($this->_photos[$roleId]['w'] / $this->_photos[$roleId]['h'])
                       / (1 - $this->_cropVarianceMax);
                   $xSR1 = $newRole['x0'] + $c;
                   $xN1 = $xN1 < $xSR1 ? $xN1 : $xSR1;
                   if ($xN1 - $newRole['x1'] > $this->_epsilon * $c)
                       $check = true;
                   $newRole['x1'] = $xN1;
                   break;
               case 'yN0':
                   $c = ($newRole['x1'] - $newRole['x0']) * (1 + $this->_cropVarianceMax) / 
                       ($this->_photos[$roleId]['w'] / $this->_photos[$roleId]['h']);
                   $ySB0 = $newRole['y1'] - $c;
                   $yN0 = $yN0 > $ySB0 ? $yN0 : $ySB0;
                   if ($newRole['y0'] - $yN0 > $this->_epsilon * $c)
                       $check = true;
                   $newRole['y0'] = $yN0;
                   break;
               case 'yN1':
                   $c = ($newRole['x1'] - $newRole['x0']) * (1 + $this->_cropVarianceMax) / 
                       ($this->_photos[$roleId]['w'] / $this->_photos[$roleId]['h']);
                   $yST1 = $newRole['y0'] + $c;
                   $yN1 = $yN1 < $yST1 ? $yN1 : $yST1;
                   if ($yN1 - $newRole['y1'] > $this->_epsilon * $c)
                       $check = true;
                   $newRole['y1'] = $yN1;
                   break;
               default:
                   break;
           }
       }
       $this->_roles[$roleId] = $newRole;
       return $check;
   }
   
   /**
    * TODO: Closes the rest of the holes that could not be closed just by stretching the roles:
    * 
    * @param type $roleId 
    */
   protected function _closeTheHoles($roleId) {
   }


   /**
     * Check the float values for equality
     * 
     * @param float $a
     * @param float $b
     * @return bool 
     */
    protected function _comp($a, $b) {
        return ($b >= $a - $this->_epsilon && $b <= $a + $this->_epsilon);
    }
    
}