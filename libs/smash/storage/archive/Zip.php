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
	 * @package      Zip
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Zip implements Surface
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
			if (!extension_loaded('zip')) {
				throw Core::error('general.extension-not-loaded', array('zip'));
			} else {
				$this->archive = new \ZipArchive;
			}
			
			$result = $mode === false ? $this->archive->open($archive) : $this->archive->open($archive, $mode);
			
			if ($result !== true) {
				throw Core::error('storage.archive.unable-to-open', null, $result);
			}
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
			for ($i = 0; $i < $this->numFiles; $i++) {	        	
	        	$this->archive->extractTo($destination, array($this->getNameIndex($i)));		    
		    }
		    
		    $this->close();
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
			if (!empty($content) && is_string($content)) {
				$this->archive->addFromString($entry, $content);
			} else {
				$this->archive->addFile($entry);
			}
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
			$fullPath = $base . $parent . $folder;
			$zipPath = $parent . $folder;

			$this->archive->addEmptyDir($zipPath);
			$dir = new \DirectoryIterator($fullPath);

			foreach ($dir as $file) {
				if (!$file->isDot()) {
					$filename = $file->getFilename();

					if (!in_array($filename, $this->ignoredNames)) {
						if($file->isDir()) {
							$this->addFolder($base, $filename, $zipPath . Core::DS);
						} else {
							$this->archive->addFile($fullPath . Core::DS . $filename, $zipPath . Core::DS . $filename);
						}
					}
				}
			}
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
			if (method_exists($this->archive, $method)) {
				call_user_func_array(array($this->archive, $method), $params);
			} else {
				throw Core::error('class.method-not-exist', array($method, $this->archive));
			}
		}
	}
?>