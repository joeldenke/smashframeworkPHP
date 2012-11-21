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
	
	use	Smash\Storage\Config,
		Smash\Core,
		Smash\Error,
		Smash\Library,
		Smash\Object;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Serialize
	 * @uses        Smash\Storage\Config,  Smash\Core,  Smash\Error,  Smash\Library,  Smash\Object
	 * @package     Ini
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Ini
	{
		private $options = array(
			'iniSections' => true
		);
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $config (optional)
		 */
		public function __construct($config = null)
		{
			if ($config instanceof Config) {
				$this->options = $config->asArray();
			} else if (is_array($config)) {
				$this->options = $config;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $data (Array, required)
		 */
		public function serialize(array $data)
		{
			$res = array();
			
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$res[] = "[$key]";
					
					foreach($value as $skey => $sval) {
						$res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
					}
				} else {
					$res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
				}
			}
			
			return implode(Core::CRLF, $res);
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $file (required)
		 */
		public function unserialize($file)
		{
			return parse_ini_file($file, $this->options['iniSections']);
		}
	}
?>