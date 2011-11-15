<?php

//debug ("WWW_ROOT=".WWW_ROOT.", ROOT=".ROOT.", APP_DIR=".APP_DIR.", CAKE_CORE_INCLUDE_PATH=".CAKE_CORE_INCLUDE_PATH);

function osName() {
	if (substr(php_uname(), 0, 7) == "Windows") {
		$os = 'win';
	} else {
		$os = '*nix';
	}
	return $os;
}


/**
 * 'is not empty' wrapper function
 *   '0' and 0 are considered not empty
 *   '  ' (whitespace) considered empty by default
 * 	array key not defined = empty
 *
 *  use @ operator if you are not sure the array key exists
 *
 * @param mixed $v
 * @param boolean $include_whitespace
 * @return boolean
 */
function isne($v, $include_whitespace = false) {
	if (is_string($v)) {
		if ($include_whitespace) {
			$v = trim($v); // whitespace == empty
		}
		return (isset($v) && strlen($v)); // var is set and not an empty string ''
	} else if (is_numeric($v)) {
		return (isset($v)); // var is set and not an empty string ''
	} else
	return (! empty($v));
}

function ise($v, $include_whitespace = false) {
	return !(@isne($v, $include_whitespace));
}

/*
 * if empty - default, with empty in 0, null, ''
 */
function ifed($v, $default=null){
	return (isset($v) && !empty($v)) ? $v : $default;
}

/**
 * 'if empty, return $default'
 *   '0' and 0 are =  NOT empty
 *   '  ' (whitespace) =  empty
 * 	array key not defined = empty
 *
 *  use @ operator if you are not sure the array key exists
 *
 * @param mixed $v
 * @param mixed $default
 * @return mixed - return $default if $v is empty
 */
function if_e($v, $default) {
	if (is_string($v)) {
		$v = trim($v); // whitespace == empty, return default
		$not_empty = (isset($v) && strlen($v)); // var is set and not an empty string ''
	} else if (is_numeric($v)) {
		$not_empty = (isset($v)); // var is set and not an empty string ''
	} else
	$not_empty = ! empty($v);
	return $not_empty ? $v : $default;
}



/**
 * Format Path string to match current or chosen OS format
 * set the directory separator of $path to the system value,
 * regardless of whether the path is valid or not
 *
 * @param string $path = path string
 * @param string $os = desired OS format
 */
function cleanPath($path, $os = NULL) {
	$os = $os ? $os : osName();
	switch ($os) {
		case "win":
		case "win32":
			$path = str_replace('/', '\\', $path);
			break;
		case '*nix':
		case "http":
		case 'unix':
			$path = str_replace('\\', '/', $path);
			break;
	}
	return $path;
}


function pack_json_keys(& $arr) {
	$keys = array_keys($arr);
	foreach ($keys as $key) {
		if (substr($key, 0, 5)==='json_'){
			$arr[$key] = json_encode($arr[$key]);
		}
	}
}

/**
 * Extracts AA by keys where keys specified as array VALUES (not array keys)
 * @param $arr assoc array
 * @param $keys	array of values as keys
 * @return assoc array
 */
function array_filter_keys($arr, $keys) {
	if (!is_array($keys)) $keys = array_map('trim', explode(',', $keys));
	return array_intersect_key($arr, array_flip($keys));
}

/*
 * helper functions for manipulating CakePHP Named Parameters
 */
function trimNamedParam($urlparts, $named) {
	$url = $urlparts['url'];
	unset($urlparts['url']);
	$lastchar = strrchr($url, "?");
	if ($lastchar == false)
	$url .= "?";
	$trimmedURL = preg_replace("/(^.*)\/$named:.*?([\/\?].*$)/", '$1$2', $url);
	if ($lastchar == false) {
		$trimmedURL = substr($trimmedURL, 0, strlen($trimmedURL) - 1);
	}
	if (!@ empty($urlparts)) {
		$qs = '?'.http_build_query($urlparts);
	} else
	$qs = '';
	return '/'.$trimmedURL.$qs;
}

/**
 *	sets NamedParam value without changing anything else
 *	 @params array $urlparts - set to $this->params['url']
 */
function setNamedParam($urlparts, $named, $value) {
	if (is_array($urlparts) && isset($urlparts['url'])) {	// comes from $this->params['url']
		$url = $urlparts['url'];
		unset($urlparts['url']);
		unset($urlparts['ext']);
	} else $url = $urlparts;
	$querystring = strrchr($url, "?");
	if (strpos($url, $named) === false) {
		if ($querystring) {
			// add named param BEFORE $querystring
			$trimmedURL = substr_replace($url, "/$named:$value", -strlen($querystring), 0);
		} else 	$trimmedURL = $url."/$named:$value";
	} else {
		$trimmedURL = preg_replace("/(^.*)\/$named:.*?([\/\?].*$)/", '$1'."/$named:$value".'$2', $url);
		if ($querystring == false) {
			$trimmedURL = substr($trimmedURL, 0, strlen($trimmedURL) - 1);
		}
	}
	// add qs from $this->params['url'] array form
	if (is_array($urlparts) && !empty($urlparts)) {
		$qs = '?'.http_build_query($urlparts);
	} else $qs = '';
	if (strpos($trimmedURL, '/')!==0) $trimmedURL = '/'.$trimmedURL;
	return $trimmedURL.$qs;
}

function makeJsonRequest($url) {
	$JSON_EXT = '/.json';
	if (strpos($url, $JSON_EXT) === false) {
		$querystring = strrchr($url, "?");
		if ($querystring) {
			// add named param BEFORE $querystring
			$url = substr_replace($url, $JSON_EXT, -strlen($querystring), 0);
		} else 	$url .= $JSON_EXT;
	}	
	return $url;
}



/*
 * helper functions for safe encoding of urls to be used as cgi params
 * prevents mangling on redirection
 */
function base64url_encode($input) {
	return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function base64url_decode($input) {
	return base64_decode(strtr($input, '-_', '+/'));
}

/*
 * helper functions for http headers
 */
function setExpiresHeader($expires) {
	header('Expires: '.gmdate('D, d M Y H:i:s', time() + $expires).'GMT');
}
function setLastModifiedHeader($timestamp) {
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', $timestamp).'GMT');
}


/*
 * CAKEPHP helper functions
 */

/*
 * session utility functions
 */
function loadComponent($component, &$controller = NULL, $args = '') {
	$classNameRoot = Inflector::camelize(basename($component));
	App::import('Component', $component);
	$class = $classNameRoot.'Component';
	$c = & new $class;
	if (method_exists($c, 'initialize')) $c->initialize($controller, $args);
	if (method_exists($c, 'startup')) $c->startup($controller, $args);
	return $c;
}

/**
 * 	BUG FIXED
 *	BelongsTo association doesn't seem to work alongside Permissionable
 *		fetch data manually, and merge
 *	@params object $Model - Model Class
 *	@params array $data - output array to merge belongsTo with
 */
//function XXXmergeBelongsTo($Model, &$data) {
//	$options = array('recursive'=>-1);
//	foreach ( $Model->belongsTo as $alias=>$attr) {
//		if ($attr['foreignKey']=='asset_hash') {
//			$options['condition'] = array("{$alias}.asset_hash"=>$data[$Model->name][$attr['foreignKey']]);
//		} else {
//			$options['condition'] = array("{$alias}.id"=>$data[$Model->name][$attr['foreignKey']]);
//		}
//		$belongsTo = $Model->{$alias}->find('first', $options );
//		$data[$alias] = $belongsTo[$alias];
//	}
//}

/*
 * other helper functions
 */


function ps_grep($process, $filter = NULL) {
	//		$startup = Configure::read('Config.startup');

	$output = array();
	$ret = exec("ps ax | grep $process", $output);
	$filter = if_e($filter, '.*');
	$workers = array();
	foreach ($output as $line) {
		if (preg_match("/$filter/", $line)) {
			$workers[] = $line;
		}
	}
	return $workers;
}

function reformat_qdata($data, $table = NULL) {
	$return = array();
	if (!is_array($data))
	return $return;
	foreach ($data as $row) {
		if (isset($row[$table]))
		$return[] = array($table=>array_merge($row[$table], $row[0]));
		else if ($table)
		$return[] = array($table=>$row[0]);
		else
		$return[] = $row[0];
	}
	return $return;
}

function extract_field($data, $table, $field) {
	$return = array();
	if (!is_array($data))
	return $return;
	foreach ($data as $row) {
		if (isset($row[$table][$field]))
		$return[] = $row[$table][$field];
	}
	return $return;
}


/**
 *
 * recursive glob
 * can be make this case insensitive?
 * should be put in php_lib if we keep using
 */
function rglob($pattern = '*', $flags = 0, $path = '') {
	$paths = glob($path.'*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
	$files = glob($path.$pattern, $flags);
	foreach ($paths as $path) {
		$files = array_merge($files, rglob($pattern, $flags, $path));
	}
	return $files;
}

// for SQL password
function salt_password($raw) {
	return sha1(Configure::read('Security.salt').$raw);
}


/**
 * Recursively delete a directory
 *
 * @param string $dir Directory name
 * @param boolean $deleteRootToo Delete specified top-level directory as well
 * return false if unable to delete folder, otherwise true. true if folder does not exist
 */
function unlinkRecursive($dir, $deleteRootToo = true) {
	$return = true;
	if (!$dh = @opendir($dir)) {
		return true;
	}
	while (false !== ($obj = readdir($dh))) {
		if ($obj == '.' || $obj == '..') {
			continue;
		}

		if (!@unlink($dir.'/'.$obj)) {
			$return = $return & unlinkRecursive($dir.'/'.$obj, true);
		}
	}

	closedir($dh);

	if ($deleteRootToo) {
		$return = $return & @rmdir($dir);
	}
	return $return;
}

function indexRename($fileOrFolder) {
	if (file_exists($fileOrFolder)) {
		$i = 0;
		$pathinfo = pathinfo($fileOrFolder);
		do {
			$renamed = $pathinfo['dirname'].DS.$pathinfo['filename'].'.'.$i++;
			if (isset($pathinfo['extension']))
			$renamed .= '.'.$pathinfo['extension'];
		} while (file_exists($renamed));
		return rename("$fileOrFolder", "$renamed");
	} else
	return true;
}

class Stagehand {
	public static $default_badges =  null;	// set to Configure::read('path.default_badges')
	public static $stage_baseurl = null;
	/*
	 * uses size prefixing via autorender
	 */
	public static function getImageSrcBySize($relpath, $prefix) {
		if (strlen($prefix)==2) $prefix .= '~';
		$regexp = '/^(sq|br|bp|cr|ax|bs|bx|ap|as|ar|tn|bm|am)~/';
		$path_parts = pathinfo($relpath);
		// replace existing prefix, if any, and then prepend
		if (preg_match($regexp, $path_parts['basename'])) {
			$asset_basename = preg_replace($regexp, $prefix, $path_parts['basename'], 1);
		} else {
			$asset_basename = $prefix.$path_parts['basename'];
		}
		return cleanPath($path_parts['dirname'].'/'.$asset_basename, 'http'); //only forward-slash
	}
	/**
	 * @params badgeType String [person, group, event, wedding];
	 */
	public static function getSrc($relpath, $prefix = null, $badgeType = null) {
		if ($badgeType && empty($relpath)) {
			return Stagehand::$default_badges[$badgeType];
		} else if ($prefix) {
			$relpath = Stagehand::getImageSrcBySize($relpath, $prefix);
		}
		return Stagehand::$stage_baseurl.$relpath;
	} 
}

	// deprecate: use Stagehand::getImageSrcBySize(), or Stagehand::getSrc()
	function getImageSrcBySize($relpath, $prefix) {
		if (strlen($prefix)==2) $prefix .= '~';
		$regexp = '/^(sq|br|bp|cr|ax|bs|bx|ap|as|ar|tn|bm|am)~/';
		$path_parts = pathinfo($relpath);
		// replace existing prefix, if any, and then prepend
		if (preg_match($regexp, $path_parts['basename'])) {
			$asset_basename = preg_replace($regexp, $prefix, $path_parts['basename'], 1);
		} else {
			$asset_basename = $prefix.$path_parts['basename'];
		}
		return cleanPath($path_parts['dirname'].'/'.$asset_basename, 'http'); //only forward-slash
	}


function trimImagePrefix($url) {
	$trimmedURL = preg_replace("/(^.*)..~(.*$)/", '$1$2', $url);
	return $trimmedURL;
}

/*
 * unique hash function for JPG photos
 * 
 * 
 * 
 * From SNAPPI AIR
 * Installed desktop's uuid, datetimeOriginal,  Make, Model,  ExposureTime or null,  shutterSpeedValue or null, ApertureValue or null
BUT if datetimeOriginal = null or exif is null, then
Installed desktop's uuid, file create time,  filename.extension(it's ALWAYS jpg), filesize (add this if extif == null)
concatenate those values, then do an md5 hash

FROM snappi_air:src/api/ImageScanner.as

		public function getAssetHash(f:File,json_exif:Object):String{
			//if datetimeOriginal = null or exif is null, then
			var provider_key:String = Application.application.configs.provider_key;
			var asset_hash:String = provider_key + f.name;
			if(StringUtil.trim(json_exif.DateTimeOriginal).length==0 || json_exif.xfaltuIsNull){
				//Installed desktop's uuid, file create time,  filename.extension(it's ALWAYS jpg), filesize (add this if extif == null)
				asset_hash += Misc.convertDateStr(f.creationDate) + f.extension;
				if(json_exif.xfaltuIsNull){
					asset_hash += f.size;
				} 
			}else{
				//Installed desktop's uuid, datetimeOriginal,  Make, Model,  ExposureTime or null,  shutterSpeedValue or null, ApertureValue or null
				asset_hash += json_exif.DateTimeOriginal +
							  (json_exif.Make || '') + 
							  (json_exif.Model || '') + 
							  (json_exif.ExposureTime || '')+ 
							  (json_exif.ShutterSpeedValue || '') + 
							  (json_exif.ApertureValue || '');
			}
			
			return applyMd5(asset_hash);
		}

 * 
 * 
 */

/**
 * create unique MD5 asset_hash:
 * 		filename
 * either: 
 * 		+ json_exif.DateTimeOriginal  (formatString = "YYYY-MM-DD JJ:NN:SS")
 * 			+ json_exif.Make
 * 			+ json_exif.Model
 * 			+ json_exif.ExposureTime
 * 			+ json_exif.ShutterSpeedValue
 * 			+ json_exif.ApertureValue
 * OR
 * 		+ f.creationDate (formatString = "YYYY-MM-DD JJ:NN:SS")
 * 			+ file size
 * 
 * @params $exif array - exif_read_data($filepath)
 * @params filepath - filepath on server
 * @params filename string - original filename (in case index added to filepath on duplicate)
 * 
 * NOTE: does this method have to be the same as the one used in AIR desktop loader????
 */ 
function getAssetHash($exif, $filepath,  $filename=null ){
	$string = $filename ? pathinfo($filename, PATHINFO_FILENAME ) : pathinfo($filepath, PATHINFO_FILENAME );
	if (is_array($exif)) {
		// make assetHash from exif data
		$keys = array('DateTimeOriginal', 'Make', 'Model', 'ExposureTime', 'ShutterSpeedValue', 'ApertureValue');
		foreach ($keys as $key) {
			$string .= !empty($exif[$key]) ? $exif[$key] : '';
		}
	} else {
		// TODO: assetHash(): this is the time on server, not initial upload. 
		$string .= date("Y-m-d H:i:s", filectime($filepath));	
		$string .= filesize($filepath);
	}
	return md5($string, false);
}

/**
 * translate named params to SQL paginate options
 * @deprecated: moved to AppModel::getSqlOrderFromOptions()
 */
function getSqlOrderFromPassedArgs($passedArgs, $model, $direction = 'asc') {
	$order = null;
	if (!empty($passedArgs['sort'])) {
		$sort = $passedArgs['sort'];
		if (!empty($passedArgs['direction'])) $direction = $passedArgs['direction'];
		if (strpos($sort,'.') > 0) { 
			if (strpos($sort,'0.') === 0) $sort = substr($sort,2);  // use '0.' prefix to sort on derived column
			$order=array("{$sort}" => "{$direction}");
		} else 
			$order=array("{$model->alias}.{$sort}" => "{$direction}");
	}
	return $order; 	
}


function mergeAsArray ($target, $value){
	if ($value && !is_array($value)) $value = array($value);
	if ($target && !is_array($target)) $target = array($target);
	if (empty($target)) return $value;
	if (empty($value)) return $target;
	return array_merge ($target, $value);
}

/**
 * convert GET params to POST params to test XHR in browser
 * @param $forceXHR - debug level
 * @param $showData - echo converted post params in debug 
 */
function setXHRDebug($controller, $forceXHR = 0, $showData = false){
	if (isset($controller->params['url']['forceXHR'])) {
		$controller->params['url']['forcexhr'] = $controller->params['url']['forceXHR'];
		unset($controller->params['url']['forceXHR']);
	}
	if (isset($controller->params['url']['forcexhr'])) {
		$forceXHR = $controller->params['url']['forcexhr'];
	}
	if ($forceXHR && isset($controller->params['url']['data'])) {
		$controller->data = $controller->params['url']['data'];	
		if ($showData) debug($controller->data);
	}
	if (isset($controller->params['url']['debug'])) {
		$debug = $controller->params['url']['debug'];
		Configure::write('debug', $debug );
	} else if (isset($controller->params['named']['debug'])) {
		$debug = $controller->params['named']['debug'];
		Configure::write('debug', $debug );
	} else if ($controller->RequestHandler->isAjax()){
		Configure::write('debug',$forceXHR);
	} 
	return $forceXHR;
}

/**
 * chunk $VALUES array by bytesize, not records, to limit total bytesize of insert statement
 * @param string $INSERT
 * @param array of strings $VALUES
 * @param $chunksize bytes
 * @return 2 dim array of $chunks
 */
function insertByChunks($INSERT, $VALUES, $chunksize=50000) {
	$rowsize = strlen($INSERT); 
	$chunks = array(); 
	$first = true;
	foreach ($VALUES as $row) {
		$rowsize += (strlen($row)+2);  // include comma separator
		if ($first) {
			$chunk = $row;	// start of chunk
			$first = false;
		} else if ($rowsize < $chunksize) {
			$chunk .= ', ' . $row;
			continue;
		} else {
			// export this chunk, then
			$chunks[] = $chunk;
			$rowsize = strlen($INSERT); 
			// start next chunk
			$chunk = $row;
		}
	}
	// export last chunk, then
	$chunks[] = $chunk;
	return $chunks;
}
?>
