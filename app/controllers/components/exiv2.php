<?php 
class Exiv2Component extends Object
{
	var $name='Exiv2';
	var $controller;
	var $components = array('Exec');
	var $uses = array();
	
	static $Exec;
	static $PATH2EXE ;
	

	/*
	 * Constants
	 */
	
	
	/*
	 * exiv2 command strings
	 *    -Pkv => exif Key, value
	 *    -u -Pkv => don't print unknown tags
	 *    -pi  => iptc tags
	 * 
	 */

	function __construct() {
		parent::__construct();
		if (empty(Exiv2Component::$Exec)) 
		{
			App::import('Component','Exec');
			Exiv2Component::$Exec = & new ExecComponent();
		}
		Exiv2Component::$PATH2EXE = cleanPath(Configure::read('bin.exiv2'));
	}

	function startup(& $controller)
	{
		$this->controller = $controller;
	}
	
	
	function exec($actions, $filepath){
		if (is_array($actions)) $actions = implode(' ',$actions);
//$this->log("exiv2 {$actions} \"{$filepath}\"", LOG_DEBUG);
		$cmd = Exiv2Component::$PATH2EXE.DS."exiv2 {$actions} \"{$filepath}\""; 
//debug($cmd);		
       	exec($cmd, $output, $return);
       	if ($return) return compact('cmd','output');
       	else return;
	}
		
	function getCamera($path,$basepath=null)
    {
//    	$keys=array("Exif.Image.Orientation","Exif.Photo.DateTimeOriginal","Exif.Photo.Flash","Exif.Photo.PixelXDimension","Exif.Photo.PixelYDimension");
    	$sn=null;$model=null;
        if ($basepath!==null) $path = $basepath.DS.$path;
        $path = cleanPath($path);

        $cmd = Exiv2Component::$PATH2EXE.DS."exiv2 -Pkv \"{$path}\""; 
       	exec($cmd, $output, $return);
       	
       	$key_make = "Exif.Image.Make";
       	$key_model = "Exif.Image.Model";
//debug($output);       	exit;
		foreach ($output as $line)
		{						
			$split = preg_split('/\s+/',$line,2);
			if ($split[0]==$key_make)
			{
				$make = $split[1];
				switch($make){
	        		case "NIKON CORPORATION":
	        			# exiv2 -Pkv "filename"
	        			$key_sn="Exif.Nikon3.SerialNumber";
	        			break;
	        		case "Canon":
	        			$key_sn="Exif.Canon.SerialNumber";
	        			break;
	        		case "OLYMPUS":
	        			$key_sn="Exif.Olympus.SerialNumber";
	        			break;
	        		case "FUJIFILM":
	        			$key_sn="Exif.Fujifilm.SerialNumber";
	        			break;        			
	        		case "Pentax":
	//        			$key_sn="Exif.Pentax.CameraInfo";
						$key_sn=null;
	        			break;
	        		default:
	        			debug("ERROR: Exif Make=$make not found for path=$path");
	        			$key_sn = null;	
        		}
			}
			if ($split[0]==$key_model && count($split)>1) $model = $split[1];	
			if (!@empty($key_sn) && $key_sn && $split[0]==$key_sn && count($split)>1)
			{
				$sn=$split[1];
				break;
			}
		}
		if ($sn && $model) return "{$model} ({$sn})";
		else if ($sn && empty($model)) return $sn;
		else if (empty($sn) && $model) return $model;
		else {
			return null;
		}
	}
	
		
	function getColorProfile($path,$basepath=null)
    {
//    	$keys=array("Exif.Image.Orientation","Exif.Photo.DateTimeOriginal","Exif.Photo.Flash","Exif.Photo.PixelXDimension","Exif.Photo.PixelYDimension");
        if ($basepath!==null) $path = $basepath.DS.$path;
        $path = cleanPath($path);

        $cmd = Exiv2Component::$PATH2EXE.DS."exiv2 -Pkv \"{$path}\""; 
       	exec($cmd, $output, $return);
       	
		$match=array('exif_InterOperabilityIndex'=>"Exif.Iop.InteroperabilityIndex", 'exif_ColorSpace'=>"Exif.Photo.ColorSpace");
       	$icc = array('embedded_profile'=>null,'exif_ColorSpace'=>null,'exif_InterOperabilityIndex'=>null);
		foreach ($output as $line)
		{						
			$split = preg_split('/\s+/',$line,2);
			$key = $split[0];
			if ($k=array_search($key , $match)) {
				$icc[$k] =  @if_e($split[1],null);
			}
		}
		return $icc;
	}	
	
	
    /*
     * copied from BellaComponent
     */
	function getExifIptc($path)
	{
		$data = array();

		// get EXIF data
		$exif = @exif_read_data($path);
//debug($path);		
//debug($exif);		
		if(isset($exif['DateTimeOriginal']))
		{
			if(isset($exif['COMPUTED']['Width']))
			{
				$data['imageWidth'] = $exif['COMPUTED']['Width'];
			}
			if(isset($exif['COMPUTED']['Height']))
			{
				$data['imageHeight'] = $exif['COMPUTED']['Height'];
			}
			$fields = array('ExifImageWidth'
							, 'ExifImageLength'
							, 'Orientation'
							, 'DateTimeOriginal'
							, 'Flash'
							, 'ColorSpace'
							, 'InterOperabilityIndex'
							, 'InterOperabilityVersion'
							, );
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
				'SpecialInstructions' => '2#040', 'Keyword' => '2#025', 'Category' => '2#015', 'ByLine' => '2#080', 'ByLineTitle' => '2#085'
			);
			foreach($fields as $field => $index) {
				if(isset($app13[$index]))
				{
					$value = $app13[$index];
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
	
	
	
}
?>