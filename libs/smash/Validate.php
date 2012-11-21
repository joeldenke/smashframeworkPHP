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
	namespace Smash;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash
	 * @extends      ArrayIterator
	 * @interfaces   Countable, Serializable, SeekableIterator, ArrayAccess, Traversable, Iterator
	 * @package      Validate
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Validate extends \ArrayIterator
	{
		private $errors   = array();
		private $messages = array();

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $validators (optional)
		 */
		public function __construct($validators = null)
		{
			if (!empty($validators)) {
				$this->add($validators);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getErrors()
		{
			return $this->errors;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $validators (required)
		 * @param    $params (Array)
		 */
		public function add($validators, array $params = array())
		{
			if ($validators instanceof Validate\Abstraction) {
				$class = get_class($validators);

				if (!$this->offsetExists($class)) {
					$this->offsetSet($class, $validators);
				}
			} else if (is_string($validators)) {
				if (strpos($validators, '::') !== false) {
					list($class, $method) = explode('::', $validators, 2);
				} else {
					$class  = $validators;
					$method = 'validate';
				}

				if (is_readable(dirname(__FILE__) . Inflector::classyfile('-validate-'. $class))) {
					$object = new \ReflectionClass(__NAMESPACE__ . '\\Validate\\'. $class);

					if ($object->isSubClassOf(__NAMESPACE__ . '\\Validate\\Abstraction')) {
						$name = $object->getName();

						if (!$this->offsetExists($name)) {
							$this->offsetSet($name, $object->newInstance($method, $params));
						}
					} else {
						throw Core::error('validate.invalid-validator', array('validator' => $class, 'method' => $method), Error::CODE_MVC);
					}
				} else {
					throw Core::error('validate.invalid-validator', array('validator' => $class), Error::CODE_MVC);
				}
			} else if (is_array($validators)) {
				foreach ($validators as $validator => $options) {
					$this->add($validator, $options);
				}
			} else {
				throw Core::error('validate.invalid-validator-format', array('validator' => $validator), Error::CODE_MVC);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $value (required)
		 */
		public function validate($value)
		{
			foreach ($this as $class => $validator) {
				try {
					$validator->validate($value);
				} catch (Error $e) {
					$this->errors[$class]   = $e;
					$this->messages[$class] = $e->getMessage();

					if ($validator->breakChain()) {
						return false;
					}
				}
			}

			if (empty($this->errors)) {
				return true;
			} else {
				return false;
			}
		}
	}
?>