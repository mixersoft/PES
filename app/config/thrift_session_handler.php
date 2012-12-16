<?php 
// app/config/thrift_session.php
//

AppController::log('::::::::::::::   USING CUSTOM THRIFT SESSION  ::::::::', LOG_DEBUG);
debug('::::::::::::::   USING CUSTOM THRIFT SESSION  ::::::::');
//Get rid of the referrer check even when Security.level is medium 
ini_set('session.referer_check', ''); 


/*
 * for thrift API, we are not using cookies
 * manually start session once we know TaskID
 */
 //Makes sure PHPSESSID doesn't tag along in all your urls 
ini_set('session.use_trans_sid', 0); 
// Cookie is now destroyed when browser is closed, doesn't
// persist for days as it does by default for security
// low and medium
ini_set('session.cookie_lifetime', 0);

//This sets the cookie domain to ".yourdomain.com" thereby making session persists across all sub-domains 
ini_set('session.cookie_domain', env('HTTP_BASE')); 

ini_set('session.cookie_path', '/thrift/'); 

//Comment out/remove this line if you want to keep using the default session cookie name 'PHPSESSID' 
//Useful when you want to share session vars with another non-cake app.
ini_set('session.name', Configure::read('Session.cookie')); 

ini_set('session.save_path', TMP . 'sessions');
?>