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
	namespace Smash\Storage\Archive;

	use  Smash\Core,
		Smash\Inflector,
		Smash\Library;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Storage\Archive
	 * @uses         Smash\Core,  Smash\Inflector,  Smash\Library
	 * @interfaces   Smash\Storage\Archive\Surface
	 * @package      Phar
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Phar implements Surface
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $archive (required)
		 * @param    $mode (optional)
		 */
		public function __construct($archive, $mode = false)
		{
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $destination (required)
		 * @param    $entries (Array, required)
		 */
		public function extractTo($destination, array $entries)
		{
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $entry (required)
		 * @param    $content (required)
		 */
		public function addSimple($entry, $content)
		{
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $base (required)
		 * @param    $folder (required)
		 * @param    $parent (optional)
		 */
		public function addFolder($base, $folder, $parent = null)
		{
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $method (required)
		 * @param    $params (Array, required)
		 */
		public function __call($method, array $params)
		{
		}
	}
?>