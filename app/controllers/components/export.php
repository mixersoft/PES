<?php
class ExportComponent extends Object
{
	var $name='Export';
	var $controller;
	var $components = array('Jhead');
	var $uses = array();
	var $EXPORT_BASE = "C:\Users\michael\Downloads";
	public $EXPORT_RELPATH = 'snappi-export';
	var $basepath;
	
	function __construct() {
		parent::__construct();
		App::import('component', 'Jhead');
		$this->Jhead = new JheadComponent();
	}

	function startup(& $controller)
	{
		$this->controller = $controller;
	}
	
	function setBasepath($basepath) { 
		$this->basepath = $basepath;
	}
	
	function __countFiles($dir)
	{	
		if (!is_dir($dir)) 	$dir = dirname($dir);
		$f = new Folder ($dir,false,false);
		$contents = $f->read(false, true,false);
		$count =0;
		
		return count($contents[1]);
	}

	function __get_NumberedFilename($destname, $tag=null, $index=null)
	{
		$number = $index===null ? '' : str_pad($index, 4, "0", STR_PAD_LEFT);
		switch ($tag)
		{
			case '':
			case null:
				$copyName = $destname;
				break;
			default:		
				$copyName = $destname.'-'.$tag;
				break;		
		}
		$copyName = $number===null ? $copyName.'.JPG' : $copyName.'-'.$number.'.JPG';
		return $copyName;
	}
	
	function __destFilepath($type, $dest_filename, $dest_foldername = null , $basepath = '')
	{
//		$PATTERN = "/\/P\d+\//";
		$basepath = $basepath ? $basepath : $this->EXPORT_BASE; 
		$EXPORT_FOLDER = $this->EXPORT_RELPATH;
		switch ($type)
		{
			case 'root':
				$DEST = "$EXPORT_FOLDER/root/"; break;
			case 'orig': 
			default:
				$DEST = "$EXPORT_FOLDER/originals/"; break;
		}
		if ($dest_foldername) {
			$DEST .= $dest_foldername.DS;	// export into subfolder $destname
		}
		if ($basepath) $filepath = cleanPath($basepath.DS.$DEST.$dest_filename);
		else $filepath = cleanPath($DEST.$dest_filename);
		return cleanPath($filepath);
	}
	
	/**
	 * get Src from input
	 * 	WARNING: cannot get orig_src from CastingCall
	 */
	function __getSrc($data, $i, $source='find') {
		if ($source=='CastingCall') {
			$assetIds = Set::extract('Auditions.Audition.{n}.id', $CastingCall );
			$src = ($data['Auditions']['Audition'][$i]['Photo']['Img']['Src']['rootSrc']);
		} else {
			$src = json_decode($data[$i]['Asset']['json_src'], true);
		}
		return $src;
	}
	
	/*
	 * copy original files to export destination
	 * then run jhead autorotate
	 * 	example:
	 * 		http://git:88/photos/export/1309848627/Peter%20and%20Allie
	 * 
	 * @param $data, array of json_src rows, $sortedSrc = array('orig', 'root', 'preview', 'thumb')
	 * @param $basepath string src basepath
	 * @param $options[type,destname,tag,replace]
	 */
	function export($data, $src_basepath='', $options=array())
	{
		// defaults
		$replace = true;
		set_time_limit(0);  // set infinite execution time
		
		// set defaults
		$default_options = array(
			'type'=>'orig',
			'destname'=>null,
			'tag'=>'',
			'replace'=>false,
		);
		$options = array_merge($default_options, $options);
		extract($options);
		// $type, $destname, $tag

		/*
		 * get Export files in $data
		 */
		$i = 0;
		foreach ($data as $src)
		{
			$i++;
			/*
			 * source file
			 */
			if (!empty($src_basepath)) $src['root'] = $src_basepath.DS.$src['root'];
			
			// check if scrubbed Fotofix file exists, if not, copy original
//			$filepath = cleanPath($src_basepath . DS . $relpath);
			$src_filepath = cleanPath($src[$type]);
			if (!file_exists($src_filepath))
			{
				$errors[] = "ERROR: photo/file does not exist. filepath=$src_filepath";
			}
			
			
			/*
			 * destination file
			 */
			// rename files, 
			if ($destname === null) {
				$dest_filename = basename($src[$type]);
			} else {
				$dest_filename = $this->__get_NumberedFilename($destname, $tag, $i);	
			}
			
			// add finished path
			$dest_filepath = $this->__destFilepath($type, $dest_filename, $destname);
			if (!(file_exists($dest_filepath) && $replace==false))
			{
				// create dir
				if (!file_exists(dirname($dest_filepath))) {
					$f = new Folder (dirname($dest_filepath), true, 2775);
				}		
//debug(dirname($dest_filepath));						
//debug("$src_filepath => $dest_filepath");				
				if ($result = copy($src_filepath,$dest_filepath)) {
					// copy to finishedPath, then autoRotate
					
				}
				else $errors[] = "ERROR: problem copying file to export dir, file=$src_filepath";
			}
			else
			{
				$errors[] = "WARNING: export file already exists, replace=false. destPath=$dest_filepath";			
			}
			
			
		}
		
		if (isset($dest_filepath)) $errors[] = $this->Jhead->autoRotate(dirname($dest_filepath));
		return  $errors;
	}
	
	

}
?>