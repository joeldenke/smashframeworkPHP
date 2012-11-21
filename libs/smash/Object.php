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
	 * @namespace   Smash
	 * @package     Object
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Object
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   protected, static
		 * @param    $param (ReflectionParameter Object, required)
		 * @param    $value (required)
		 */
		static protected function isValidValue(\ReflectionParameter $param, $value)
		{
			if ($param->isArray()) {
				return is_array($value);
			} else if (!is_null($param->getClass())) {
				$class = $param->getClass();
				$name  = $class->getName();

				return ($value instanceof $name);
			} else if ($param->isPassedByReference()) {
				return false;
			} else if (!$param->allowsNull()) {
				return is_null($value) ? false : true;
			} else {
				return true;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $method (ReflectionMethod Object, required)
		 */
		static public function getParamTypes(\ReflectionMethod $method)
		{
			$types = array();

			foreach ($method->getParameters() as $key => $param) {
				if ($param->isArray()) {
					$types[] = '$'. $param->getName() .' (Array' . ($param->isOptional() ? '' : ', required') .')';
				} else if(!is_null($param->getClass())) {
					$class = $param->getClass();
					$types[] = '$'. $param->getName() .' ('. $class->getName() .' Object'. ($param->isOptional() ? '' : ', required') .')';
				} else if ($param->isPassedByReference()) {
					$types[] = '$'. $param->getName() .' (Reference' . ($param->isOptional() ? '' : ', required') .')';
				} else {
					$types[] = '$'. $param->getName() .' ('. ($param->isOptional() ? 'optional' : 'required') .')';
				}
			}

			return $types;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   protected, static
		 * @param    $method (ReflectionMethod Object, required)
		 * @param    $params (Array, required)
		 */
		static protected function isValidParams(\ReflectionMethod $method, array $params)
		{
			foreach ($method->getParameters() as $key => $param) {
				if (array_key_exists($param->getName(), $params)) { // Kolla associativa f�rst!
					if (!self::isValidValue($param, $params[$param->getName()])) {
						return false;
					}
				} else if (array_key_exists($key, $params)) {
					if (!self::isValidValue($param, $params[$key])) {
						return false;
					}
				} else {
					if ($param->isOptional()) {
						continue;
					} else {
						return false;
					}
				}
			}

			/*throw Smash::error(
				'Parameters are not valid input for __construct(%params) in class "%class"',
				null,
				$this,
				array('class' => $class, 'params' => self::getParamTypes($method), 'input' => $params)
			);*/

			return true;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $object (required)
		 * @param    $method (required)
		 * @param    $params (Array)
		 */
		static public function callMethod($object, $method, array $params = array())
		{
			if (is_string($method)) {
				if (is_object($object)) {
					$class = $object;
				} else if (is_string($object)) {
					$class = self::factory($object);
				}

				$reflect = new \ReflectionObject($object);

				if ($reflect->hasMethod($method)) {
					$call = $reflect->getMethod($method);

					if (self::isValidParams($call, $params)) {
						return call_user_func_array(array($object, $method), $params);
					} else {
						throw Core::error(
							'Parameters are not valid input for %method(%params) in class "%class"',
							array('class' => $class, 'method' => $method, 'params' => self::getParamTypes($method), 'input' => $params)
						);
					}
				} else {
					throw Core::error('Method "%method" does not exist in class "%class"', array('class' => $class, 'method' => $method));
				}
			} else {
				throw Core::error('class.param-wrong-type', array('number' => 1, 'type' => 'string'));
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $object (required)
		 */
		static public function asArray($object)
		{
			$array = array();

			if (is_object($object)) {
				if ($object instanceof \ReflectionClass) {
					$class = $object;
				} else {
					$class = new \ReflectionObject($object);
				}
			}

			foreach ($class->getProperties() as $prop) {
				if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
					if (!$prop->isPublic()) {
						$prop->setAccessible();
					}
				}

			   	if (!$prop->isPublic()) {
			   		continue;
			   	} else {
					if (is_object($prop->getValue($object))) {
						$array[$prop->getName()] = self::asArray($prop->getValue($object));
					} else {
						$array[$prop->getName()] = $prop->getValue($object);
					}
				}
			}

			return $array;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $data (Array, required)
		 * @param    $class (optional)
		 */
		static public function asObject(array $data, $class = 'stdClass')
		{
			if (is_string($class)) {
				$class = new \ReflectionClass($class);
			} else if (is_object($class)) {
				if (!$class instanceof \ReflectionClass) {
					$class = new \ReflectionObject($class);
				}
			} else {
				throw Core::error('Class need to be an instantiated object or a classname to be load', array('class' => $class));
			}

			if ($class->hasMethod('__set')) {
				throw Core::error('Cannot convert into object "%class" which has the magic method __set()', array('class' => $class->getName()));
			} else if ($class->hasMethod('__construct')) {
				$method   = $class->getConstructor();
				$required = $method->getNumberOfRequiredParameters();

				if ($required > 0) {
					throw Core::error('Cannot convert into object "%class" which has a constructor that requires parameters', array('class' => $class->getName()));
				} else {
					$obj = $class->newInstance();
				}
			} else {
				$obj = $class->newInstance();
			}

			foreach ($data as $key => $value) {
				if ($class->hasProperty($key)) {
					$prop = $class->getProperty($key);

					if (!$prop->isPublic()) {
						continue;
					} else {
						if (is_array($value)) {
							$obj->$key = self::toObject($value, $obj);
						} else {
							$obj->$key = $value;
						}
					}
				} else if (is_array($value)) {
					$obj->$key = self::toObject($value, $obj);
				} else {
					$obj->$key = $value;
				}
			}

			return $obj;
		}
	}
?>