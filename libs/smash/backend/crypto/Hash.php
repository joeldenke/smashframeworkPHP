<?php
/**
 * 
 * Generated by
 * Smash Framework Commentator
 * with PHP Version 5.3.4
 * 
 *  DESCRIPTION
 * Smash Framework is a Open Source PHP web framework to make it easier, efficient and more fun to create web applications.
 * 
 *  LICENSE
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author      Joel Denke <mail@happyness.se>
 * @category    Smash - Smash Makes A Sweet Harmony
 * @copyright   (C) 2011 Joel Denke
 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
 * @version     alpha 0.1
 */
	namespace Smash\Backend\Crypto;

	use   Smash\Core,
		Smash\Error;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Backend\Crypto
	 * @uses        Smash\Core,  Smash\Error
	 * @package     Hash
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Hash
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $salt (required)
		 * @param    $length (optional)
		 */
		static public function getSalt($salt, $length = null)
		{
			if (is_string($salt)) {
				$salt = 'Sm4shS4lt' . $salt;
				$size = strlen($salt);
			} else {
				throw Core::error('crypto.hash.invalid-salt', array('salt' => $salt), Error::CODE_CORE);
			}

			if (is_int($length)) {
				if ($length >= 0 && $length < $size) {
					$size = $length;
				}
			}

			return substr($salt, 0, $size);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $hash (required)
		 * @param    $string (required)
		 * @param    $salt (optional)
		 * @param    $saltLength (optional)
		 * @param    $length (optional)
		 */
		static public function generate($hash, $string, $salt = '', $saltLength = null, $length = false)
		{
			$hash = strtolower($hash);
			$salt = self::getSalt($salt, $saltLength);

			if (function_exists($hash)) {
				$result = $hash($string . $salt);
			} else if (extension_loaded('hash')) {
				if (in_array($hash, hash_algos())) {
					$result = hash($hash, $string . $salt);
				} else {
					throw Core::error('crypto.hash.invalid-hash-extension-algorithm', array('algorithm' => $hash), Error::CODE_CORE);
				}
			} else {
				throw Core::error('crypto.hash.invalid-hash-algorithm', array('algorithm' => $hash), Error::CODE_CORE);
			}
			
			if (is_int($length)) {
				return (strlen($result) > $length) ? substr($result, 0, $length - 1) : $result;
			} else {
				return $result;
			}
		}
	}