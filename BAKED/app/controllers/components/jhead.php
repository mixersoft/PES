<?php 
class JheadComponent extends Object
{
	var $name='Jhead';
	var $controller;
//	var $components = array('Exec');
	var $uses = array();
	
	static $Exec;
	static $PATH2EXE ;
	static $AUTOROTATE = true;
	static $QUALITY = 85;
	static $SIZE_PREVIEW = 640;
	static $ON_DUPLICATE = 'compare';  // [ compare | replace | skip ]
	static $OPTIONS;
	static $OS ;
	

	/*
	 * Constants
	 */

	function __construct() {
		parent::__construct();
		if (empty(JheadComponent::$Exec)) 
		{
			App::import('Component','Exec');
			JheadComponent::$Exec = & new ExecComponent();
			$jhead_bin = cleanPath(Configure::read('bin.jhead'));
			JheadComponent::$PATH2EXE = $jhead_bin;
			JheadComponent::$OPTIONS = array(
				'cwd'=>$jhead_bin
//				, 'env'=>array( 'PATH'=>$jhead_bin )
			);
			
			JheadComponent::$OS = JheadComponent::$Exec->getOS();
		}
	}

	function startup(& $controller)
	{
		$this->controller = $controller;
	}
	
	function setOrigFileTimes($folder)
	{
		$jhead = " jhead -ft ";
		if (JheadComponent::$OS=='win32') {
			$cmd = "{$jhead} \"{$folder}\**\*.jpg\"" ;
		} 
		else // unix
		{
			$cmd = "find \"$folder\" -iname *.jpg -exec {$jhead} {} \;" ;
		}
//		debug($cmd);
		$options = JheadComponent::$OPTIONS;
		$options['title'] = 'setOrigFileTimes';
		$errors = JheadComponent::$Exec->exec($cmd, $options);
		return $errors;
	}
	

	function autoRotate($fileOrFolder = NULL, $spool=false)
	{
		$jhead = " jhead -ft -autorot ";
		$options = JheadComponent::$OPTIONS;
		
		if (is_file($fileOrFolder)) {
			$cmd = "{$jhead} \"{$fileOrFolder}\"" ;
		}
		else {
			if (JheadComponent::$OS=='win32') {
				$options['title'] = 'autoRotate';
				$cmd = "{$jhead} \"{$fileOrFolder}\**\*.JPG\"" ;
			} 
			else // unix
			{
				$cmd = "find \"{$fileOrFolder}\" -iname *.jpg -exec {$jhead} {} \;" ;
			}
		}
		
//		debug($cmd);
		if ($spool) return $cmd;
		$errors = JheadComponent::$Exec->exec($cmd, $options);
		if ($errors) return $errors;
	}
	
	function noRotate($fileOrFolder, $spool=false)
	{
		$jhead = " jhead -norot ";
		$options = JheadComponent::$OPTIONS;
		
		if (is_file($fileOrFolder)) {
			$cmd = "{$jhead} \"{$fileOrFolder}\"" ;
		}
		else {
			if (JheadComponent::$OS=='win32') {
				$options['title'] = 'noRotate';
				$cmd = "{$jhead} \"{$fileOrFolder}\**\*.JPG\"" ;
			} 
			else // unix
			{
				$cmd = "find \"{$fileOrFolder}\" -iname *.jpg -exec {$jhead} {} \;" ;
			}
		}
		if ($spool) return $cmd;
		
		$options = JheadComponent::$OPTIONS;
		$errors = JheadComponent::$Exec->exec($cmd, $options);
		if ($errors) return $errors;
	}


	/**
	 * use jpegtran to autorotate to vertical from an EXTERNAL exif_Orientation tag value
	 *
	 * @param $exif_Orientation [ 3 | 6 | 8 ]
	 * @param string $src 
	 * @param string $dest
	 * @return unknown
	 */
	function exifRotate($exif_Orientation, $src, $dest=null, $autoRotate=true, $spool=false)
	{
		if ($dest===null) {
			$dest = $src;
			// if we copy in place, assume $exif_Orientation is the final orientation
			$autoRotate = false;
		}
		$options = JheadComponent::$OPTIONS;
		$rotate = array(8=>270, 6=>90, 3=>180);
		
		$cmd = "jpegtran -rotate {$rotate[$exif_Orientation]} -copy all ";
		if (JheadComponent::$OS=="win32") {
			$cmd .=  "\"{$src}\"  \"{$dest}\"";
		} else
		{
			if (!file_exists($src)) {
				$orig = str_replace('/.thumbs/bp~','/',$src);
				copy($orig, $src);
			}
			if ($src==$dest) {
					$cmd .= "\"{$src}\" > \"{$src}.tmp\" ; mv -f \"{$src}.tmp\" \"{$dest}\"";
			} else 	$cmd .= "\"{$src}\" > \"{$dest}\"";
		}
		
		/*
		 * use shell and spool commands
		 */
//		$commands = array(); $innerSpool = true;
//		$commands[] = $cmd;
//		if ($autoRotate) $commands[] = $this->autoRotate($dest, $innerSpool);
//		else  $commands[] = $this->noRotate($dest, $innerSpool);
//		if ($spool) return $commands;
//		else  $errors[] = JheadComponent::$Exec->shell($commands, $options);
		
		/*
		 * don't use shell.
		 */
		$ret = JheadComponent::$Exec->exec($cmd, $options);
		if ($ret) $errors[] = $ret;
	
		// --copy all does not touch the exif_orient tag, 
		// since Fotofix rotate values are all based on autoRotated/exif_orient=1 photos, 
		// we must make sure the original is also auto-rotated 
		if ($autoRotate) $ret = $this->autoRotate($dest);
		else  $ret = $this->noRotate($dest);
		if ($ret) $errors[] = $ret;
		if (!empty($errors)) return $errors;
		
	}
	
	function transferExifByFolder($relpath, $srcBasepath, $destBasepath)
	{
		if (strpos($destBasepath,'WEDDINGS')!==FALSE) {
			return "WARNING: Jhead::transferExifByFolder() tried to transfer exif to originals in WEDDINGS folder, dest=$destBasepath ";
		}
		if (strpos($destBasepath,'PREVIEW')===FALSE) {
			return "WARNING: Jhead::transferExifByFolder() can only transfer exif to photos in the PREVIEW folder, dest=$destBasepath ";
		}
		
		if (is_file($relpath)) $relpath = dirname($relpath);
		
		$cmd = "jhead  -te \"{$srcBasepath}/&i\" \"{$relpath}\"/**/*.JPG" ;
		$options = JheadComponent::$OPTIONS;
		$options['title'] = 'transferExifByFolder';
		$options['cwd'] = $destBasepath;
		$errors = JheadComponent::$Exec->exec($cmd, $options);
		return $errors;
	}
	
	function transferExif($src, $dest, $spool=false)
	{
			$cmd = "jhead  -te \"{$src}\" \"{$dest}\"" ;
//			if ($createNewExif) $cmd .= " -mkexif ";		// replace exif section, or create if missing
			if ($spool) return $cmd;
			
			$options = JheadComponent::$OPTIONS;
			$errors = JheadComponent::$Exec->exec($cmd, $options);
			if ($errors) return $errors;
	}

	function setExifTime($filepath, $datetime, $createNewExif=false, $spool=false)
	{
		if ($datetime) {
			// format datetime for jhead
			$datetime = str_replace('-',':',$datetime);
			$datetime = str_replace(' ','-',$datetime);
			$cmd = "jhead ";
			if ($createNewExif) $cmd .= " -mkexif ";		// replace exif section, or create if missing
			$cmd .= " -ts{$datetime} -ft \"$filepath\"" ;
			
			if ($spool) return $cmd;
			
			$options = JheadComponent::$OPTIONS;
			$errors = JheadComponent::$Exec->exec($cmd, $options);
			return $errors;
		}
	}

	
	/**
	 * Enter description here...
	 *
	 * @param string $filepath
	 * @param string $offsetEnd used to calculate offset time. reformats "YYYY-MM-DD hh:mm:ss" => "YYYY:MM:DD/hh:mm:ss"
	 * @param string $offsetBegin used to calculate offset time. reformats "YYYY-MM-DD hh:mm:ss" => "YYYY:MM:DD/hh:mm:ss"
	 * @param string $baseDateTime set initial time before adding offset, reformats "YYYY-MM-DD hh:mm:ss" => "YYYY:MM:DD/hh:mm:ss"
	 * @return array $errors
	 */
	function adjustExifTime($filepath, $offsetEnd, $offsetBegin, $baseDateTime=null, $createNewExif=false)
	{
		//format dates for jhead -da
		$offsetBegin = str_replace(' ','/',$offsetBegin);
		$offsetBegin = str_replace('-',':',$offsetBegin);
		$offsetEnd = str_replace(' ','/',$offsetEnd);
		$offsetEnd = str_replace('-',':',$offsetEnd);
		
		// format datetime for jhead
		if ($offsetEnd == $offsetBegin ) $da = '';
		else $da = " -da{$offsetEnd}-{$offsetBegin} ";
		
		$cmd = "jhead ";
		if ($createNewExif) $cmd .= " -mkexif ";			// mk new exif if missing
		$cmd .= " {$da} -ft \"$filepath\"";
		
//debug($cmd);
		$options = JheadComponent::$OPTIONS;
		$errors = JheadComponent::$Exec->exec($cmd, $options);
		return $errors;
	}	
	
	
}
?>