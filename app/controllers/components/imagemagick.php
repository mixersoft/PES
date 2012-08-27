<?php 
class ImagemagickComponent extends Object
{
	var $name='Imagemagick';
	var $controller;
	var $components = array('Exec');
	var $uses = array();
	var $Exiv2;
	
	static $Exec;
	static $PATH2EXE ;
	static $AUTOROTATE = true;
	static $QUALITY = 85;
	static $SIZE_PREVIEW = 640;
	static $ON_DUPLICATE = 'compare';  // [ compare | replace | skip ]
	

	/*
	 * Constants
	 */

	function __construct() {
		parent::__construct();
		if (empty(ImagemagickComponent::$Exec)) 
		{
			App::import('Component','Exec');
			ImagemagickComponent::$Exec = & new ExecComponent();
		}
		ImagemagickComponent::$PATH2EXE = cleanPath(Configure::read('bin.imagemagick'));
	}

	function startup(& $controller)
	{
		$this->controller = $controller;
	}

	function autoRotate($folder = NULL, $offset=0)
	{
		if ($offset) {
			
		} 
		else 
		{
			$cmd = "jhead -ft -autorot \"{$folder}\**\*.jpg\"" ;
		}
		debug($cmd);
		$options = array(
			'cwd'=>cleanPath(Configure::read('bin.jhead')), 
			'env'=>array(
					'PATH'=>Configure::read('bin.jhead')
			),
			'title'=>'autoRotate'
		);
		$errors = ImagemagickComponent::$Exec->exec($cmd, $options);
		return $errors;
	}
	
	
	/**
	 * downsize an image if necessary. preserves EXIF and IPTC meta-data
	 * @param $src - filepath
	 * @param $dest - filepath
	 * @param $options autorotate, size, JPG quality
	 * @return String error message or 0 if no errors 
	 */
	function downsize($src, $dest, $options=null)
	{
		// extract options
		$rotate = ImagemagickComponent::$AUTOROTATE;
		$quality = ImagemagickComponent::$QUALITY;
		$size = ImagemagickComponent::$SIZE_PREVIEW;
		$duplicate = ImagemagickComponent::$ON_DUPLICATE;
		if ($options) extract($options, EXTR_OVERWRITE);
		
		// test $src, create dir for $dest recursively
		if (!is_file($src)) return "Source file not found, src=$src";
		
		if (is_file($dest)) 
		{
			switch ($duplicate) {
				case 'compare':
					$exif_dest = @exif_read_data($dest);
					$dest_date = @if_e($exif_dest['DateTimeOriginal'], 2);

					$exif_src = exif_read_data($src);
					$src_date = @if_e($exif_src['DateTimeOriginal'], 1);
//		debug("$src_date == $dest_date");
					if ($src_date == $dest_date) return "DateTimeOriginal matches, assume same image. skipping";
					break;
				case 'skip':
					return "Warning: dest file exists, skipping.";
					break;					
				case 'replace':
					break;

			}
		}
		
		if (!is_dir($folderpath = dirname($dest))) {
			$f = new Folder($folderpath, true, 0775);
			if ($f->errors()) return "Unable to create destination folder, dest=$folderpath";
		}
		
		// process convert options
		if ($rotate) $autorot=' -auto-orient ';
		else $autorot='';
		
		// exec options
		$options = array(
			'cwd'=>ImagemagickComponent::$PATH2EXE, 
		);		
		
		
		$cmd = "convert -verbose \"{$src}\" -resize {$size}x{$size}\">\" {$autorot} -quality {$quality} \"{$dest}\" ";
		$errors = ImagemagickComponent::$Exec->exec($cmd, $options);
//		debug($cmd);	
		return $errors;
	}
	
	
	function iccProfileConversion($src, $dest, $target='sRGB', $create_profile=true, $options=array())
	{
		// extract options
		$duplicate = ImagemagickComponent::$ON_DUPLICATE;
		$default_options = array(
			'cwd'=>ImagemagickComponent::$PATH2EXE, 
		);		
		$options = array_merge($default_options, $options);
		
		// test $src, create dir for $dest recursively
		if (!is_file($src)) return "Source file not found, src=$src";
		
		if (is_file($dest)) 
		{
//			switch ($duplicate) {
//				case 'compare':
//					break;
//				case 'skip':
//					return "Warning: dest file exists, skipping.";
//					break;					
//				case 'replace':
//					break;
//
//			}
		}
		
		if ($src==$dest){
			$backup = dirname($src).DS.'icc'.DS.basename($src);
			if (!is_file($backup)) 
			{
				if (!is_dir($folderpath = dirname($backup))) {
					$f = new Folder($folderpath, true, 0775);
					if ($f->errors()) return "Unable to create destination folder, dest=$folderpath";
				}				$result = copy($src, $backup );
				if (!$result) return "Warning: saving backup copy failed for src=$src";
			}
		}
		
		if (!is_dir($folderpath = dirname($dest))) {
			$f = new Folder($folderpath, true, 0775);
			if ($f->errors()) return "Unable to create destination folder, dest=$folderpath";
		}
		
	
		// exec options	
		$icc_path = Configure::read('Config.icc');
		$sRGB_path = cleanPath("{$icc_path}/sRGB.icm");
		$adobe_path = cleanPath("{$icc_path}/AdobeRGB1998.icc");
		
		if ($target=='sRGB' && $create_profile) $cmd = "convert -verbose \"{$src}\" -profile \"{$adobe_path}\" -profile \"{$sRGB_path}\" \"{$dest}\" ";
		else if ($target=='sRGB' && !$create_profile) $cmd = "convert -verbose \"{$src}\" -profile \"{$sRGB_path}\" \"{$dest}\" ";
		else if ($target=='adobe1998' && $create_profile) $cmd = "convert -verbose \"{$src}\" -profile \"{$sRGB_path}\"  -profile \"{$adobe_path}\" \"{$dest}\" ";
		else if ($target=='adobe1998' && !$create_profile) $cmd = "convert -verbose \"{$src}\" -profile \"{$adobe_path}\" \"{$dest}\" ";
		else return "ERROR: ICC Color Profile unknown. profile=$target";
		
		if ($error = ImagemagickComponent::$Exec->exec($cmd, $options)){
			return $error;
		} 
		
			if (@empty($this->Exiv2)) {
				App::import('Component','Exiv2');
				$this->Exiv2 = & new Exiv2Component();
			}
			/*
			 * update exif attrs describing color profile
			 */
			if ($target=='sRGB') 
			{
				$actions[]="-M\"set Exif.Photo.ColorSpace Short 1\"";
				$actions[]="-M\"set Exif.Iop.InteroperabilityIndex Ascii R98\"";
			} else if ($target=='adobe1998') 
			{
				$actions[]="-M\"set Exif.Photo.ColorSpace Short 65535\"";
				$actions[]="-M\"set Exif.Iop.InteroperabilityIndex Ascii R03\"";
				
			} 
			$error = $this->Exiv2->exec($actions, $dest);

			if ($error) return $error;
	}
	
	
	function getIccProfile($path,$basepath=null)
    {
    	$output=array();
    	$options = array(
			'cwd'=>ImagemagickComponent::$PATH2EXE, 
		);
        if ($basepath!==null) $path = $basepath.DS.$path;
        $path = cleanPath($path);

        $cmd = "identify -verbose \"{$path}\"";
//        $error = ImagemagickComponent::$Exec->exec($cmd, $options);
       	$error = exec($cmd, $output, $return);       	

       	$icc = array('embedded_profile'=>null,'exif_ColorSpace'=>null,'exif_InterOperabilityIndex'=>null);
       	$scan=null;
    	
		foreach ($output as $line)
		{						
			if (strpos($line,"exif:InteroperabilityIndex")) {
				$split = preg_split('/\s+/',$line,3);
				$icc['exif_InterOperabilityIndex'] = $split[2];
			}
			if (strpos($line,"exif:ColorSpace")) {
				$split = preg_split('/\s+/',$line,3);
				$icc['exif_ColorSpace'] = $split[2];
			}
			if ($scan=='next line') {
				$icc['embedded_profile'] = trim($line);
				$scan=null;
			}			
			if (strpos($line,"Profile-icc:")) {
				$scan='next line';
			}
		}
		return $icc;
	}
	
	function getColorProfile($path)
	{
//		$icc = $this->getIccProfile($path);

		/*
		 * use Exiv2 to read colorspace
		 */
		if (@empty($this->Exiv2)) {
			App::import('Component','Exiv2');
			$this->Exiv2 = & new Exiv2Component();
		}
		$icc = $this->Exiv2->getColorProfile($path);
		
//debug($path);		
//debug($icc);				
		if ($icc['embedded_profile']=='IEC 61966-2.1 Default RGB colour space - sRGB'){
			$icc['profile'] = 'sRGB';
		}
		else if ($icc['embedded_profile']=='Adobe RGB (1998)'){
			$icc['profile'] = 'adobe1998';
		}
		else if ($icc['exif_InterOperabilityIndex']=='R98') {
			$icc['profile'] = 'sRGB';
		} 
		else if ($icc['exif_InterOperabilityIndex']=='R03') 
		{
			$icc['profile'] = 'adobe1998';
		} 
		else if ($icc['exif_ColorSpace']==1) 
		{
			$icc['profile'] = 'sRGB';
		}
		else if ($icc['exif_ColorSpace']==65535) 
		{
			$icc['profile'] = 'adobe1998';
		}
		else $icc['profile'] = 'unknown';
		return $icc;
	}
	
	function setColorProfile($path,$target='sRGB')
	{
		if (!is_file($path))  return "ERROR: ImagemagickComponent->setColorSpace() file not found, path=$path";
		$icc = $this->getColorProfile($path);
		if ($icc['profile']==$target) return;
		if ($target=='sRGB')
		{
			if ($icc['profile'] == 'adobe1998' && empty($icc['embedded_profile'])) {				
				$error = $this->iccProfileConversion($path, $path, 'sRGB', true); // add adobe1998 profile, then convert
			} else if ($icc['profile'] == 'adobe1998' && !empty($icc['embedded_profile'])) {				
				$error = $this->iccProfileConversion($path, $path, 'sRGB', false);
			} else {
				$error = "WARNING: can't convert ICC Profile to sRGB because current profile is unknown";
			}
			
		} 
		else if ($target=='adobe1998')
		{
			if ($icc['profile'] == 'sRGB' && empty($icc['embedded_profile'])) {
				$error = $this->iccProfileConversion($path, $path, 'adobe1998', true);
			} else if ($icc['profile'] == 'sRGB' && !empty($icc['embedded_profile'])) {
				$error = $this->iccProfileConversion($path, $path, 'adobe1998', false);
			} else {
				$error = "WARNING: can't convert ICC Profile to adobe1998 because current profile is unknown";
			}
		} 
		if ($error) return $error;
		return;
	}
	
}
?>