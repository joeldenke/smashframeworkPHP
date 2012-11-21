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
	namespace Smash\Serialize;
	use Smash\Core;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Serialize
	 * @uses         Smash\Core
	 * @interfaces   Smash\Serialize\Surface
	 * @package      Url
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Url implements Surface
	{
		private $components = array(
  			'scheme'   => 'http',
			'host'     => null,
			'port'     => null,
			'user'     => null,
			'pass'     => null,
			'path'     => null,
			'query'    => null,
			'fragment' => null
		);

		/*
		public function component($component = '')
		{
			echo 'component:'. $this->component . '<br>'. "\n";

			switch ($component) {
				case PHP_URL_SCHEME   : return 'scheme';    break;
				case PHP_URL_HOST     : return 'host';      break;
				case PHP_URL_PORT     : return 'port';      break;
				case PHP_URL_USER     : return 'user';      break;
				case PHP_URL_PASS     : return 'pass';      break;
				case PHP_URL_PATH     : return 'path';      break;
				case PHP_URL_QUERY    : return 'query';     break;
				case PHP_URL_FRAGMENT : return 'fragment';  break;
				default               : return null;
			}
		}
		*/

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $url (required)
		 * @param    $return (optional)
		 * @param    $component (optional)
		 */
		public function unserialize($url, $return = false, $component = null)
		{
			$components = parse_url($url);

			if (is_array($components)) {
				$this->components = array_merge($this->components, $components);
			} else {
				throw Core::error('An error occured while parsing url "%url"', array('url' => $url));
			}

			if ($return === true) {
				if (array_key_exists($component, $this->components)) {
					return $this->components[$component];
				} else {
					return $this->components;
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $component (required)
		 * @param    $value (required)
		 */
		public function setComponent($component, $value)
		{
			if (array_key_exists($component, $this->components)) {
				$this->components[$component] = $value;
			} else {
				throw Core::error('Component "%component" is not available', array('component' => $component));
			}

			return $this;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $component (optional)
		 */
		public function getComponent($component = null)
		{
			if (empty($component)) {
				return $this->components;
			} else if (array_key_exists($component, $this->components)) {
				return $this->components[$component];
			} else {
				throw Core::error('Component "%component" is not available', array('component' => $component));
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $parts (optional)
		 */
		public function serialize($parts = null)
		{
			// Assume user already have parsed url in current instance
			if (empty($parts)) {
				$parts = $this->components;
			}

			if (!is_array($parts)) {
				$url = false;
			} else {
				$url  = isset($parts['scheme']) ? $parts['scheme'].':'.((strtolower($parts['scheme']) == 'mailto') ? '' : '//') : '';
				$url .= isset($parts['user']) ? $parts['user'].(isset($parts['pass']) ? ':'.$parts['pass'] : '').'@' : '';
				$url .= isset($parts['host']) ? $parts['host'] : '';
				$url .= isset($parts['port']) ? ':'.$parts['port'] : '';

				if (isset($parts['path'])) {
					$url .= (substr($parts['path'], 0, 1) == '/') ? $parts['path'] : ('/' . $parts['path']);
				}

				$url .= isset($parts['query']) ? '?'.$parts['query'] : '';
				$url .= isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
			}

			return $url;
		}
	}
?>