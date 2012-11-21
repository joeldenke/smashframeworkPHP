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
	namespace Smash\Validate;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Validate
	 * @extends     Smash\Validate\Abstraction
	 * @package     Alphanum
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Alphanum extends Abstraction
	{
		const NOT_ALPHA          = 10;
		const NOT_BETWEEN        = 20;
		const NOT_BETWEEN_STRICT = 30;
		const NOT_MATCH                = 40;

		protected $errors = array(
			self::NOT_ALPHA => 'validate.alphanum.not-alpha',
			self::NOT_BETWEEN => 'validate.alphanum.not-between',
			self::NOT_BETWEEN_STRICT => 'validate.alphanum.not-strict-beween',
			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 * @param    $method (required)
			 * @param    $params (Array, required)
			 */
			self::NOT_MATCH                => 'validate.alphanum.not-match'
		);

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $value1 (required)
		 * @param    $value2 (required)
		 * @param    $caseSensitive (optional)
		 */
		public function match($value1, $value2, $caseSensitive = true)
		{
			if ($caseSensitive) {
				if ($value1 !== $value2) {
					throw $this->createError(self::NOT_MATCH, array('data1' => $value1, 'data2' => $value2));
				}
			} else {
				if (strcasecmp($value1, $value2) !== 0) {
					throw $this->createError(self::NOT_MATCH, array('data1' => $value1, 'data2' => $value2));
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $mixed (optional)
		 * @param    $allowBlank (optional)
		 */
		public function alpha($mixed = false, $allowBlank = false)
		{
			$pattern = ($mixed === true ? "[a-zA-Z0-9������+]+" : "[a-zA-Z������]+\s");

			if (!$allowBlank && !empty($this->value)) {
				if (!preg_match('#'. $pattern .'#', $this->value)) {
					throw $this->createError(self::NOT_ALPHA);
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $min (required)
		 * @param    $max (required)
		 * @param    $inclusive (optional)
		 */
		public function between($min, $max, $inclusive = false)
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		{
			if (is_string($this->value)) {
				$this->value = strlen($this->value);
			}

			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 */
			if ($inclusive) {
				if ($min > $this->value || $max < $this->value) {
					throw $this->createError(self::NOT_BETWEEN, array('min' => $min, 'max' => $max));
				}
			} else {
				/**
				 * Description goes here ...
				 * 
				 * @access   public
				 * @param    $value (required)
				 */
				if ($min >= $this->value || $max <= $this->value) {
					throw $this->createError(self::NOT_BETWEEN_STRICT, array('min' => $min, 'max' => $max));
				}
			}
		}
	}
/**
 * Description goes here ...
 * 
 * @access   public
 * @param    $error (required)
 * @param    $data (Array)
 */
?>