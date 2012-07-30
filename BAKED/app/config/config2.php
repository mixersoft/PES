<?php
$config['Config.os']=osName();

$config['AAA']=array(
	'allow_guest_login'=>false,
	'allow_magic_login'=>false,
	);

$config['AAA.Permissionable'] = array('root_user_id'=>'12345678-1111-0000-0000-123456789abc', 'root_group_id'=>'role-----0123-4567-89ab-cdef----root');

$config['Staging.slots'] = 8;

$config['email'] = array(
	'noreply' => 'noreply@' . array_shift(explode(':',env('HTTP_HOST'))),
	'auth'=>array(
		'port'=>'465', 
        'timeout'=>'30',
        'host' => 'ssl://smtp.gmail.com',
		'username'=>'customerservice@snaphappi.com',
		'password'=>'snapsh0t1',
	),
);

$config['register'] = array(
	'active'=>1,	// default User.active=1
	'email_verify' => 1,
	'success_redirect' => '/pages/downloads',	// default redirect on Sucess
	'auth_on_success' => 1,
);

$config['desktop.uploader'] = array(
	'version'=>'1.8.6'
);

/*
 * OS dependent config here
 */
switch ($config['Config.os']) {
	case 'win':
	case 'win32':
		$config['AAA']['allow_magic_login']=true;
		$config['bin'] = array(
			'imagemagick' => 'W:/usr/bin/ImageMagick',
			'jhead' => 'W:/usr/bin/jhead',
			'jpegtran' => 'W:/usr/bin/jhead',
			'rsync_home' => '\usr\bin\CWRSYNC',
			'java' => 'xxx',
			'cakeConsole' => 'xxx',
			'php' => 'W:/usr/local/php',
			'exiv2' => 'W:/usr/bin/exiv2',
			'shell' => 'C:/windows/system32',			// C DRIVE, <-- PLEASE CONFIRM LOCATION!!!!!!!
        	'meanshift'=> 'W:/usr/bin/meanshift/bin',
		);
		
		$config['vendors'] = array('fileUploader'=>'valums-file-uploader-461068d'); 
		$wwwroot = 'W:/www-git.3';
		$svc_root = 'W:/www-svc';
		$config['path'] = array(
			'APIKEYS' => 'U:\Users\michael\PRIVATE\APIKEYS.php',
			'wwwroot'=> $wwwroot,
			'svcroot'=>$svc_root,
			'local' => array( // deprecate, use 
				'original'=>array('basepath'=>$svc_root.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
				'preview'=>array('basepath'=>$svc_root.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
			),
			'stageroot'=>array('basepath'=>$svc_root.'/svc/STAGING', 'httpAlias'=>'svc/STAGING'),
			
			'blank_user_photo'=> '/static/img/css-gui/snappi.png', 	// deprecate, use Stagehand::getSrc()

//			'meanshift_tmp'=>'W:/usr/bin/meanshift/tmp',
			'pageGalleryPrefix'=>'/svc/pages',
			'pagemaker'=>array('catalog'=>$wwwroot.'\PAGEMAKER\static\arrangements'),
			'autorender'=> $svc_root.DS.'svc'.DS.'autorender'.DS.'Image.php',
		);
		$config['path']['fileUploader'] = array(
				'vendorpath'=>$config['vendors']['fileUploader'],
				'basepath'=>"/{$config['vendors']['fileUploader']}",
				'folder_basepath'=>$svc_root.DS.'svc'.DS.'upload'.DS,
			);
		$config['path']['airUploader'] = array(
				'folder_basepath'=>$svc_root.DS.'svc'.DS.'upload'.DS,
			);			
		$config['path']['storyMakerUploader'] = array(
				'folder_basepath'=>$svc_root.DS.'svc'.DS.'upload'.DS,
				'baseurl'=>'svc'.DS.'upload'.DS,
			);	
		$config['http_static'] = array('http://snappi', 'http://snappi1');
		break;
	case '*nix':
	case 'unix':
		
		switch (env('SERVER_NAME')) {
			case 'preview.snaphappi.com':
			case 'dev.snaphappi.com':
			case 'touch.snaphappi.com':
			// case 'aws.snaphappi.com':
				$wwwroot = '/www-dev';	
				$svc_root = $wwwroot;
				$config['http_static'] = array('http://snappi.snaphappi.com', 'http://snappi1.snaphappi.com'); 
				break;
			case 'dev2.snaphappi.com':
			case 'gallery.snaphappi.com':
				$wwwroot = '/www-dev2';
				$svc_root = $wwwroot;
				break;			
			default:
				$wwwroot = '/www-dev'; 
				$svc_root = $wwwroot;
				break;
		}
		
		$config['bin'] = array(
			'imagemagick' => '/usr/bin/convert',
			'jhead' => '/usr/bin/jhead',
			'jpegtran' => '/usr/bin/jhead',
			'rsync_home' => '/usr/bin/rsync',
			'java' => 'xxx',
			'cakeConsole' => 'xxx',
			'php' => '/usr/bin/php',
			'exiv2' => '/usr/bin/exiv2',
		);
		
		$config['vendors'] = array('fileUploader'=>'valums-file-uploader-461068d'); 

		$config['path'] = array(
			'APIKEYS' => '/home/michael/APIKEYS.php',
			'wwwroot'=> $wwwroot,
			'svcroot'=> $svc_root,
			'local' => array( 
				'original'=>array('basepath'=>$svc_root.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
				'preview'=>array('basepath'=>$svc_root.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
			),
			'stageroot'=>array('basepath'=>$svc_root.'/svc/STAGING', 'httpAlias'=>'svc/STAGING'),
			'blank_user_photo'=> '/static/img/css-gui/snappi.png', 	// deprecate, use Stagehand::getSrc()
			
			'pageGalleryPrefix'=>'/svc/pages',
			'pagemaker'=>array('catalog'=>$wwwroot.'/PAGEMAKER/static/arrangements'),
			'autorender'=> $svc_root.DS.'svc'.DS.'autorender'.DS.'Image.php',
		);
		$config['path']['fileUploader'] = array(
				'vendorpath'=>$config['vendors']['fileUploader'],
				'basepath'=>"/{$config['vendors']['fileUploader']}",
				'folder_basepath'=>$svc_root.DS.'svc'.DS.'upload'.DS,
			);	
		$config['path']['airUploader'] = array(
				'folder_basepath'=>$svc_root.DS.'svc'.DS.'upload'.DS,
			);	
		$config['path']['storyMakerUploader'] = array(
				'folder_basepath'=>$svc_root.DS.'svc'.DS.'upload'.DS,
				'baseurl'=>'svc'.DS.'upload'.DS,
			);		
		break;		
}

$config['path']['default_badges'] = array(
	'Asset'=>'/static/img/css-gui/snappi.png',
	'Story'=>'/static/img/css-gui/snappi.png',
	'Collection'=>'/static/img/css-gui/snappi.png',
	'Person'=>'/static/img/css-gui/snappi.png',
	'Circle'=>'/static/img/css-gui/snappi.png',
	'Group'=>'/static/img/css-gui/snappi.png',
	'Event'=>'/static/img/css-gui/snappi.png',
	'Wedding'=>'/static/img/css-gui/snappi.png',
	'Tag'=>'/static/img/css-gui/snappi.png',
);

switch (env('SERVER_NAME')) {
	case 'preview.snaphappi.com':
	case 'touch.snaphappi.com':
		Configure::write('debug', 0);
		$config['isDev'] = 0;
		break;
	case 'preview':
		$config['isDev'] = 1;
		Configure::write('debug', 0);
		break;	
	case 'dev.snaphappi.com':
	default:
		$config['isDev'] = 1;
		Configure::write('debug', 2);
	break;
}


/*
 * config lookup array
 */
$lookup_roles['USER']='role-----0123-4567-89ab---------user';
$lookup_roles['GUEST']='role-----0123-4567-89ab--------guest';
$lookup_roles['VISITOR']='role-----0123-4567-89ab------visitor';
 $lookup_roles['ROOT']='role-----0123-4567-89ab-cdef----root';
$lookup_roles['ADMIN']='role-----0123-4567-89ab--------admin';
$lookup_roles['MANAGER']='role-----0123-4567-89ab------manager';
$lookup_roles['EDITOR']='role-----0123-4567-89ab-------editor';
$config['lookup.roles'] = $lookup_roles;

/*
 * lookups
 */
// keyName from controller->name. use keyName as controller/context param for building urls
$config['lookup.keyName'] = array('Users'=>'person','Groups'=>'group','Assets'=>'photo','Collections'=>'story','Tags'=>'tag');
// xfr (from XHR root) lookup, keys should match keyName lookup 
// updated, using 'controller.alias' for key
$config['lookup.xfr']['my'] = array('Model'=>'User', 'ControllerLabel'=>'Me');
$config['lookup.xfr']['person'] = array('Model'=>'User', 'ControllerLabel'=>'Person');
$config['lookup.xfr']['circles'] = array('Model'=>'Group', 'ControllerLabel'=>'Group');
$config['lookup.xfr']['groups'] = array('Model'=>'Group', 'ControllerLabel'=>'Group');
$config['lookup.xfr']['events'] = array('Model'=>'Group', 'ControllerLabel'=>'Event');
$config['lookup.xfr']['weddings'] = array('Model'=>'Group', 'ControllerLabel'=>'Wedding');
$config['lookup.xfr']['photos'] = array('Model'=>'Asset', 'ControllerLabel'=>'Photo');
$config['lookup.xfr']['stories'] = array('Model'=>'Collection', 'ControllerLabel'=>'Story');
$config['lookup.xfr']['tags'] = array('Model'=>'Tag', 'ControllerLabel'=>'Tag');
// TODO: deprecate. using 'controller.keyName' as key
$config['lookup.xfr']['Me'] = array('Model'=>'User', 'ControllerAlias'=>'person');
$config['lookup.xfr']['Person'] = array('Model'=>'User', 'ControllerAlias'=>'person');
$config['lookup.xfr']['Group'] = array('Model'=>'Group', 'ControllerAlias'=>'groups');
$config['lookup.xfr']['Event'] = array('Model'=>'Group', 'ControllerAlias'=>'groups');
$config['lookup.xfr']['Wedding'] = array('Model'=>'Group', 'ControllerAlias'=>'groups');
$config['lookup.xfr']['Photo'] = array('Model'=>'Asset', 'ControllerAlias'=>'photos');
$config['lookup.xfr']['Snap'] = array('Model'=>'Asset', 'ControllerAlias'=>'photos');
$config['lookup.xfr']['Story'] = array('Model'=>'Collection', 'ControllerAlias'=>'stories');
$config['lookup.xfr']['Tag'] = array('Model'=>'Tag', 'ControllerAlias'=>'tags');


Configure::write('feeds.paginate.perpage', 20);
Configure::write('feeds.action', 'all');
?>