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

	use Smash\Tracker\Surface;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash
	 * @uses        Smash\Tracker\Surface
	 * @package     Tracker
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Tracker
	{
		static private $trackers = array();

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function __construct()
		{
			$this->load(array($this, 'defaultTrack'));
			$this->setTracking();
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   final, public, static
		 * @param    $list (required)
		 * @param    $type (optional)
		 */
		final static public function import($list, $type = null)
		{
			$import = is_array($list) ? $list : array($list);

			switch ($type) {
				case 'smash'  :
					foreach ($import as $class) {
						$file = Core::$base . str_replace(array('\\', '/'), Core::DS, $class) .'.php';

						if ((@include_once $file) === false) {
							throw Core::error('smash.import-classes-not-loaded', array('class' => $class, 'list' => $import), Error::CODE_CORE);
						}
					}
					break;
				default      :
					foreach ($import as $class) {
						if (!self::isDeclared($class)) {
							$file = Library::classTofile($class);

							if ((@include $file) === false) {
								throw Core::error('smash.import-classes-not-loaded', array('class' => $class, 'list' => $import), Error::CODE_CORE);
							}
						}
					}
					break;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $callback (required)
		 * @param    $type (optional)
		 */
		static public function load($callback, $type = 'append')
		{
			if (!is_callable($callback) && is_array($callback)) {
				foreach ($callback as $tracker) {
					self::load($tracker, $type);
				}
			} else {
				if (is_object($callback)) {
					if ($callback instanceof Surface || is_callable(array($callback, 'track'))) {
						switch ($type) {
							case 'prepend':
								array_unshift(self::$trackers, $callback);
								break;
							case 'append' :
								array_push(self::$trackers, $callback);
								break;
						}
					}
				} else if (is_callable($callback)) {
					switch ($type) {
						case 'prepend':
							array_unshift(self::$trackers, $callback);
							break;
						case 'append' :
							array_push(self::$trackers, $callback);
							break;
					}
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   private, static
		 */
		static private function getTrackers()
		{
			return self::$trackers;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $class (required)
		 */
		static public function track($class)
		{
			$trackers = self::getTrackers();

			foreach ($trackers as $tracker) {
				if ($tracker instanceof Surface) {
					if ($tracker->track($class)) {
						return true;
					}
				} else if (is_object($tracker)) {
					if (is_callable(array($tracker, 'track'))) {
						if ($tracker->track($class)) {
							return true;
						}
					}
				} else if (is_callable($tracker)) {
					if (call_user_func($tracker, $class)) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $class (required)
		 */
		static public function defaultTrack($class)
		{
			$file = Library::classToFile($class);

			if ((@include $file) === false) {
				return false;
			} else {
				return true;
			}
		}


		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $callback (optional)
		 * @param    $enable (optional)
		 */
		static public function setTracking($callback = null, $enable = true)
		{
			if (is_null($callback)) {
				$callback = __CLASS__;
			}

			if (!is_string($callback) && !is_object($callback) && !is_array($callback)) {
				throw Cpre::error('variable.invalid.resource', array('invalid' => $callback, 'valid' => array('string', 'array', 'object')));
			}

			if (!is_array($callback)) {
				$callback = array($callback, 'track');
			}

			if (!is_callable($callback)) {
				throw Core::error('Class does not have an track() method', null, Error::CODE_CORE);
			} else {
				if ($enable == true) {
					return spl_autoload_register($callback);
				} else {
					return spl_autoload_unregister($callback);
				}
			}
		}
	}
?>