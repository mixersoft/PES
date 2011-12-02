<?php
/**
 * Short description for file.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

Router::parseExtensions('json','xml');

Router::connect('/combo', array('controller'=>'combo', 'action'=>'js'));
Router::connect('/tags/:action/*', array('plugin'=>'tags', 'controller'=>'tags'));
Router::connect('/comments/:action/*', array('plugin'=>'comments', 'controller'=>'comments'));
Router::connect('/users/:action/*', array('plugin'=>'', 'controller'=>'users'));
//Router::connect('/photos', array('controller'=>'assets'));  // for photos/all only
//Router::connect('/photos/:action/*', array('controller'=>'assets'));


// my routing
//Router::connect('/my', array('controller'=>'users'));
//Router::connect('/my/:action', array('controller'=>'users'));
//Router::connect('/my/:action/*', array('controller'=>'users'));

//Router::connect('/my/:action/:id', array('controller'=>'users'), array('pass'=>array('id')));

Router::connect('/', array('controller' => 'pages', 'action' => 'index'));
	
//Router::connect('/', array('controller'=>'welcome', 'action'=>'home'));

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.ctp)...
 */
	Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
?>