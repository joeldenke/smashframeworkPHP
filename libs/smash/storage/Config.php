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
	namespace Smash\Storage;
	
	use 	Smash\Core;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Storage
	 * @uses         Smash\Core
	 * @interfaces   ArrayAccess, Countable, Iterator, Traversable
	 * @package      Config
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Config implements \ArrayAccess, \Countable, \Iterator
	{
		const MODE_ALL     = 0;
		const MODE_NEW     = 1;
		const MODE_EDIT    = 2;
		const MODE_DELETE  = 4;

		private    $mode   = self::MODE_ALL;

		protected  $cursor = 0;
		protected  $count  = 0;
		protected  $offset = array();

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $config (required)
		 * @param    $mode (optional)
		 */
		public function __construct($config, $mode = null)
		{
			if (!empty($mode)) {
				$this->mode = $mode;
			}

			if ($config instanceof Config) {
				$config = $config->asArray();
			}

			if (is_array($config)) {
				foreach ($config as $key => $value) {
					if (is_array($value)) {
						$this->offset[$key] = new self($value, $this->mode);
					} else {
						$this->offset[$key] = $value;
					}
				}
			} else {
				throw Core::error('config.not-array-resource', array('config' => $config), 1004);
			}

			$this->count = count($this->offset);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $root (optional)
		 */
		public function getOffset($root = false)
		{
			if ($root) {
				return $this;
			} else {
				return $this->offset;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function offsetExists($index)
		{
			return array_key_exists($index, $this->offset);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function offsetGet($index)
		{
			if ($this->offsetExists($index)) {
				return $this->offset[$index];
			} else {
				throw Core::error('config.offset-not-exists', array('index' => $index), 1004);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 * @param    $value (required)
		 * @param    $edit (optional)
		 */
		public function offsetSet($index, $value, $edit = false)
		{
			switch ($this->mode) {
				case self::MODE_ALL :
					if ($edit === true) {
						if ($this->offsetExists($index)) {
							if (is_array($value)) {
								$this->offset[$index] = new self($value, $this->mode);
							} else {
								// $this->offset[$index] = $this->createSingle($value, $this->mode);
								$this->offset[$index] = $value;
							}
						} else {
							throw Core::error('config.offset-not-exists', array('index' => $index), 1004);
						}
					} else {
						if (is_array($value)) {
							$this->offset[$index] = new self($value, $this->mode);
						} else {
							//$this->offset[$index] = $this->createSingle($value, $this->mode);
							$this->offset[$index] = $value;
						}
					}
					break;
				case self::MODE_NEW :
					if (is_array($value)) {
						$this->offset[$index] = new self($value, $this->mode);
					} else {
						//$this->offset[$index] = $this->createSingle($value, $this->mode);
						$this->offset[$index] = $value;
					}
					break;
				case self::MODE_EDIT  :
					if ($this->offsetExists($index)) {
						if (is_array($value)) {
							$this->offset[$index] = new self($value, $this->mode);
						} else {
							$this->offset[$index] = $value;
							// $this->offset[$index] = $this->createSingle($value, $this->mode);
						}
					} else {
						throw Core::error('config.offset-not-exists', array('index' => $index), 1004);
					}
					break;
				default :
					throw Core::error('config.mode-not-allow-mod', null, 1004);
					break;
			}

			$this->count = count($this->offset);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function offsetUnset($index)
		{
			if ($this->mode & self::MODE_DELETE | self::MODE_ALL) {
				if ($this->offsetExists($index)) {
					unset($this->offset[$index]);
				}
			} else {
				throw Core::error('config.mode-not-allow-delete', null, 1004);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function offsetAsArray($index)
		{
			if ($this->isArray($index)) {
				return $this->offsetGet($index)->asArray();
			} else {
				return array($this->offsetGet($index));
			}
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 * @param    $value (required)
		 */
		public function changeValue($index, $value)
		{
			$instance = clone $this;
			
			if (!is_array($index)) {
				$index = array($index);
			}
			
			do {
				$part = array_shift($index);

				if (!is_string($part)) {
					throw Core::error('config.all-elements-not-string', array('part' => $part, 'elements' => $index), 1004);
				}

				if ($instance->offsetExists($part)) {
					if (count($index) > 0) {
						if (!$instance->isArray($part)) {
							return false;
						} else {
							$instance = $this->offsetGet($part);
						}
					} else {
						return $instance->offsetSet($part, $value);
					}
				} else {
					return false;
				}
			} while (count($index));
			
			return false;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function exists($index)
		{
			$instance = clone $this;

			// Deep inheritance dependecy check
			if (is_array($index)) {
				while (count($index)) {
					$part = array_shift($index);

					if (!is_string($part)) {
						throw Core::error('config.all-elements-not-string', array('part' => $part, 'elements' => $index), 1004);
					}

					if ($instance->offsetExists($part)) {
						if (count($index) > 0) {
							if (!$instance->isArray($part)) {
								return false;
							} else {
								$instance = $this->offsetGet($part);
							}
						} else {
							return true;
						}
					} else {
						return false;
					}
				}
			} else {
				return $instance->offsetExists($index);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function isArray($index)
		{
			$value = $this->offsetGet($index);

			if ($value instanceof Config) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function asArray()
		{
			$array = array();

			foreach ($this->getOffset() as $key => $value) {
				if ($value instanceof Config) {
					$array[$key] = $value->asArray();
				} else {
					$array[$key] = $value;
				}
			}

			return $array;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function __toString()
		{
			return serialize($this->asArray());
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function __get($index)
		{
			return $this->offsetGet($index);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 * @param    $value (required)
		 */
		public function __set($index, $value)
		{
			return $this->offsetSet($index, $value);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $name (required)
		 */
		public function __isset($name)
		{
			return isset($this->offset[$name]);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function count()
		{
			return $this->count;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function current()
		{
			return current($this->offset);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function key()
		{
			return key($this->offset);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function next()
		{
			next($this->offset);
			$this->cursor++;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function rewind()
		{
			reset($this->offset);
			$this->cursor = 0;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function valid()
		{
			return $this->cursor < $this->count;
		}
	}
?>