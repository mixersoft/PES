<?php 

    function compareRoles($a, $b) {
    	// sort by desc order
        if ($a['Prominence'] == $b['Prominence']) {
            return 0;
        }
        return ($a['Prominence'] < $b['Prominence']) ? 1 : -1;
    }


/*
 * Convert PicasaCollage xml documents
 */
class PicasaConverterComponent extends Object {
    var $name = 'PicasaConverterComponent';
    function startup(&$controller) {
        $this->controller = $controller;
    }
    
    /*
     * Picasa arrangement format
     */
    //		[xmlAsArray] => Array
    //                (
    //                    [Collage] => Array
    //                        (
    //                            [version] => 2
    //                            [format] => 7:5
    //                            [orientation] => landscape
    //                            [theme] => picturegrid
    //                            [shadows] => 0
    //                            [captions] => 1
    //                            [AlbumTitle] => Array
    //                                (
    //                                )
    //
    //                            [Background] => Array
    //                                (
    //                                    [type] => solid
    //                                    [color] => FFFFFFFF
    //                                )
    //
    //                            [Spacing] => Array
    //                                (
    //                                    [value] => 0.078062
    //                                )
    //
    //                            [Node] => Array
    //                                (
    //                                    [0] => Array
    //                                        (
    //                                            [x] => 0.000000
    //                                            [y] => 0.571429
    //                                            [w] => 0.500000
    //                                            [h] => 0.428571
    //                                            [theta] => 0.000000
    //                                            [scale] => 497.000000
    //                                            [theme] => noborder
    //                                        )
    //
    //                                    [1] => Array
    //                                        (
    //                                            [x] => 0.500000
    //                                            [y] => 0.571429
    //                                            [w] => 0.500000
    //                                            [h] => 0.428571
    //                                            [theta] => 0.000000
    //                                            [scale] => 497.000000
    //                                            [theme] => noborder
    //                                        )
    //
    //                                    [2] => Array
    //                                        (
    //                                            [x] => 0.333333
    //                                            [y] => 0.000000
    //                                            [w] => 0.666667
    //                                            [h] => 0.571429
    //                                            [theta] => 0.000000
    //                                            [scale] => 668.000000
    //                                            [theme] => noborder
    //                                        )
    //
    //                                    [3] => Array
    //                                        (
    //                                            [x] => 0.000000
    //                                            [y] => 0.000000
    //                                            [w] => 0.333333
    //                                            [h] => 0.285714
    //                                            [theta] => 0.000000
    //                                            [scale] => 326.000000
    //                                            [theme] => noborder
    //                                        )
    //
    //                                    [4] => Array
    //                                        (
    //                                            [x] => 0.000000
    //                                            [y] => 0.285714
    //                                            [w] => 0.333333
    //                                            [h] => 0.285714
    //                                            [theta] => 0.000000
    //                                            [scale] => 326.000000
    //                                            [theme] => noborder
    //                                        )
    //                                )
    //                        )
    //                )
    
    function isValid($xmlAsArray) {
        if (@isset($xmlAsArray['Collage']) 
				&& $xmlAsArray['Collage']['version'] == 2 
				&& $xmlAsArray['Collage']['theme'] == "picturegrid") {
            return true;
        } else
            return false;
    }
    
    
    function convert($xmlAsArray) {
        /*
         * check format
         */
        if ($this->isValid($xmlAsArray) === false)
            return false;
            
        $xmlAsArray = $xmlAsArray['Collage'];
        $converted = array();
        $converted['Title'] = @if_e($xmlAsArray['AlbumTitle'][0], '');
        $converted['Background'] = @if_e($xmlAsArray['Background'], array());
        $converted['Spacing'] = @if_e($xmlAsArray['Spacing']['value'], 0);
        $format = explode(':', $xmlAsArray['format']);
        $converted['W'] = $format[0];
        $converted['H'] = $format[1];
		
        $roles = array();
		$portrait=$landscape=0;
        foreach ($xmlAsArray['Node'] as $role) {
        	$W = $role['w'] * $converted['W'];
			$H = $role['h'] * $converted['H'];
            $prominence = round( $W * $H * $role['scale'] * $role['scale']);
			
            $roles[] = array(
				'X'=>$role['x'], 'Y'=>$role['y'], 'W'=>$role['w'], 'H'=>$role['h']
				, 'Theta'=>$role['theta'], 'Scale'=>$role['scale']
				, 'ZIndex'=>@if_e($role['ZIndex'], 0), 'IsCast'=>@if_e($role['isCast'], 0)
				, 'Prominence'=>$prominence, 'CSS'=>null
				, 'landscape'=> ($W >= $H)
			);
			
			if ($W >= $H) $landscape++;
			else $portrait++;
        }
        // sort by Prominence
		usort($roles, "compareRoles");
		
        // add role index
        for ($i = 0; $i < count($roles); $i++) {
            $roles[$i]['Index'] = $i;
        }
//		debug ($roles);
        $converted['Roles'] = $roles;
		$converted['LandscapeCount'] = $landscape;
		$converted['PortraitCount'] = $portrait;
        return $converted;
    }
}
?>
