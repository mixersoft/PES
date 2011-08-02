<?php
/**
 * LT Localized Validation class. Handles localized validation for the Lithuanian language
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org
 * @package       localized
 * @subpackage    localized.libs
 * @since         localized 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * LtValidation
 *
 * @package       localized
 * @subpackage    localized.libs
 */
class LtValidation {

/**
 * Checks phone numbers for the Lithuania
 *
 * @param string $check The value to check.
 * @access public
 * @return boolean
 */
	function phone($check) {
		$pattern = "/^(([\+]?370)|(8))[\s-]?\(?[0-9]{2,3}\)?[\s-]?([0-9]{2}[\s-]?){2}?[0-9]{1,2}$/";
		return preg_match($pattern, $check);
	}

/**
 * Checks zipcodes for the Lithuania
 *
 * @param string $check The value to check.
 * @access public
 * @return boolean
 */
	function postal($check) {
		$pattern = '/^(lt)?[\s-]?[\d]{5}$/i';
		return preg_match($pattern, $check);
	}

/**
 * Checks social security numbers for the Lithuania
 *
 * @param string $check The value to check.
 * @access public
 * @return boolean
 */
	function ssn($check) {
		$pattern = '/^([a-z]{2})[\s-]?[\d]{7}$/i';
		return preg_match($pattern, $check);
	}
}
?>