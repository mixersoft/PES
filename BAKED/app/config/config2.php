<?php
$config['Config.os']=osName();

$config['AAA']=array('allow_guest_login'=>false);

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


/*
 * OS dependent config here
 */
switch ($config['Config.os']) {
	case 'win':
	case 'win32':
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
		$wwwroot = 'W:/www-git';
		$config['path'] = array(
			'APIKEYS' => 'U:\Users\michael\PRIVATE\APIKEYS.php',
			'wwwroot'=> $wwwroot,
			'local' => array( // deprecate, use 
				'original'=>array('basepath'=>$wwwroot.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
				'preview'=>array('basepath'=>$wwwroot.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
			),
			'stageroot'=>array('basepath'=>$wwwroot.'/svc/STAGING', 'httpAlias'=>'svc/STAGING'),
			
			'blank_user_photo'=> '/img/providers/snappi.png', 	// deprecate, use Stagehand::getSrc()
			'default_badges'=>array(
				'person'=>'/img/providers/snappi.png',
				'group'=>'/img/providers/snappi.png',
				'event'=>'/img/providers/snappi.png',
				'wedding'=>'/img/providers/snappi.png',
			),

//			'meanshift_tmp'=>'W:/usr/bin/meanshift/tmp',
			'pageGalleryPrefix'=>'/svc/pages',
			'pagemaker'=>array('catalog'=>$wwwroot.'\PAGEMAKER\arrangements'),
		);
		$config['path']['fileUploader'] = array(
				'vendorpath'=>$config['vendors']['fileUploader'],
				'basepath'=>"/{$config['vendors']['fileUploader']}",
				'folder_basepath'=>$wwwroot.DS.'svc'.DS.'upload'.DS,
			);
		$config['path']['airUploader'] = array(
				'folder_basepath'=>$wwwroot.DS.'svc'.DS.'upload'.DS,
			);			
		
		break;
	case '*nix':
	case 'unix':
		$wwwroot = '/www-dev';
		
switch (env('SERVER_NAME')) {
	case 'aws.snaphappi.com':
	case 'dev2.snaphappi.com':
	case 'gallery.snaphappi.com':
		$wwwroot = '/www-dev2';
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
			'local' => array( 
				'original'=>array('basepath'=>$wwwroot.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
				'preview'=>array('basepath'=>$wwwroot.'/svc/PREVIEWS', 'httpAlias'=>'svc/PREVIEWS'),
			),
			'stageroot'=>array('basepath'=>$wwwroot.'/svc/STAGING', 'httpAlias'=>'svc/STAGING'),
			'blank_user_photo'=> '/img/providers/snappi.png', 	// deprecate, use Stagehand::getSrc()
			'default_badges'=>array(
				'person'=>'/img/providers/snappi.png',
				'group'=>'/img/providers/snappi.png',
				'event'=>'/img/providers/snappi.png',
				'wedding'=>'/img/providers/snappi.png',
			),
			'pageGalleryPrefix'=>'/svc/pages',
			'pagemaker'=>array('catalog'=>$wwwroot.'/PAGEMAKER/arrangements'),
		);
		$config['path']['fileUploader'] = array(
				'vendorpath'=>$config['vendors']['fileUploader'],
				'basepath'=>"/{$config['vendors']['fileUploader']}",
				'folder_basepath'=>$wwwroot.DS.'svc'.DS.'upload'.DS,
			);	
		$config['path']['airUploader'] = array(
				'folder_basepath'=>$wwwroot.DS.'svc'.DS.'upload'.DS,
			);					
		break;		
}

switch (env('SERVER_NAME')) {
	case 'aws.snaphappi.com':
	case 'gallery.snaphappi.com':
	case 'dev2.snaphappi.com':
		Configure::write('debug', 0);
		break;
}


/*
 * config lookup array
 */
$lookup_roles['ADMIN']='role-----0123-4567-89ab--------admin';
$lookup_roles['MANAGER']='role-----0123-4567-89ab------manager';
$lookup_roles['EDITOR']='role-----0123-4567-89ab-------editor';
$lookup_roles['USER']='role-----0123-4567-89ab---------user';
$lookup_roles['GUEST']='role-----0123-4567-89ab--------guest';
$lookup_roles['VISITOR']='role-----0123-4567-89ab------visitor';
$config['lookup.roles'] = $lookup_roles;

/*
 * lookups
 */
// keyName from controller->name. use keyName as controller/context param for building urls
$config['lookup.keyName'] = array('Users'=>'person','Groups'=>'group','Assets'=>'photo','Tags'=>'tag');
// xfr (from XHR root) lookup, keys should match keyName lookup 
$config['lookup.xfr']['Me'] = array('Model'=>'User', 'ControllerAlias'=>'person');
$config['lookup.xfr']['Person'] = array('Model'=>'User', 'ControllerAlias'=>'person');
$config['lookup.xfr']['Group'] = array('Model'=>'Group', 'ControllerAlias'=>'groups');
$config['lookup.xfr']['Event'] = array('Model'=>'Group', 'ControllerAlias'=>'groups');
$config['lookup.xfr']['Wedding'] = array('Model'=>'Group', 'ControllerAlias'=>'groups');
$config['lookup.xfr']['Photo'] = array('Model'=>'Asset', 'ControllerAlias'=>'photos');
$config['lookup.xfr']['Snap'] = array('Model'=>'Asset', 'ControllerAlias'=>'photos');
$config['lookup.xfr']['Tag'] = array('Model'=>'Tag', 'ControllerAlias'=>'tags');

Configure::write('feeds.paginate.perpage', 20);
Configure::write('feeds.action', 'all');
?>