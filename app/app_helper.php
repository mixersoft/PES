<?php
/**
 * Short description for file.
 *
 * This file is application-wide helper file. You can put all
 * application-wide helper-related methods here.
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
 * @subpackage    cake.cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Helper', 'Helper', false);

/**
 * This is a placeholder class.
 * Create the same file in app/app_helper.php
 *
 * Add your application-wide methods in the class below, your helpers
 * will inherit them.
 *
 * @package       cake
 * @subpackage    cake.cake
 */
class AppHelper extends Helper {
//	function beforeRender() {
//		if ($this instanceof PaginatorHelper) {
//			// set options for Paginator->numbers();
//		}
//	}
}


class LayoutHelper extends AppHelper { 
     
    var $__blockName = null; 
     
    /** 
     * Start a block of output to display in layout 
     * 
     * @param  string $name Will be prepended to form {$name}_for_layout variable 
     */ 
    function blockStart($name) { 

        if(empty($name)) 
            trigger_error('LayoutHelper::blockStart - name is a required parameter'); 
             
        if(!is_null($this->__blockName) && $this->__blockName !== $name) 
            trigger_error('LayoutHelper::blockStart - Blocks cannot overlap'); 

        $this->__blockName = $name; 
        ob_start(); 
        return null; 
    } 
     
    /** 
     * Ends a block of output to display in layout 
     */ 
    function blockEnd() { 
        $buffer = @ob_get_contents(); 
        @ob_end_clean(); 

        $out = $buffer;  
             
        $view =& ClassRegistry::getObject('view'); 
		if (isset($view->viewVars[$this->__blockName.'_for_layout'])) {
			$view->viewVars[$this->__blockName.'_for_layout'] .= $out;
		} else 
        	$view->viewVars[$this->__blockName.'_for_layout'] = $out; 
         
        $this->__blockName = null; 
    } 
     
    /** 
     * Output a variable only if it exists. If it does not exist you may optionally pass 
     * in a second parameter to use as a default value. 
     *  
     * @param mixed $variable Data to ourput 
     * @param mixed $defaul Value to output if first paramter does not exist 
     */ 
    function output(&$var, $default=null) { 
        if(!isset($var) or $var==null) { 
            if(!is_null($default))  
                echo $default; 
        } else 
            echo $var;     
    } 
     
} 

?>