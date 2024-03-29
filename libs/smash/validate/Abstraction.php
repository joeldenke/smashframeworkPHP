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
	use Smash\Core;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Validate
	 * @uses        Smash\Core
	 * @package     Abstraction
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	abstract class Abstraction
	{
		public $breakChainOnError;

		private $method;
		private $params;
		private $error = false;

		protected $value;

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $method (required)
		 * @param    $params (Array, required)
		 */
		public function __construct($method, array $params)
		{			
			foreach ($params as $key => $param) {
				if (is_string($key)) {
					switch ($key) {
						case 'breakChain' :
							if (is_bool($param)) {
								$this->breakChainOnError = $param;
							}
							unset($params[$key]);
							break;
						case 'error' :
							if (is_string($param)) {
								$this->error = $param;
							}
							
							unset($params[$key]);
							break;
					}
				}
			}

			if (method_exists($this, $method)) {
				$this->method = $method;
				$this->params = $params;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function breakChain()
		{
			return $this->breakChainOnError;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getParams()
		{
			return $this->params;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $value (required)
		 */
		public function validate($value)
		{
			$this->value = $value;
			return call_user_func_array(array($this, $this->method), $this->params);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $error (required)
		 * @param    $data (Array)
		 */
		public function createError($error, array $data = array())
		{
			$data = array_merge(array('value' => $this->value), $data);

			if (is_string($this->error)) {
				return Core::error($this->error);
			} else if (isset($this->errors[$error])) {
				return Core::error($this->errors[$error], $data, $error);
			} else {
				return Core::error('validate.unknown-error', $data, $error);
			}
		}
	}
?>