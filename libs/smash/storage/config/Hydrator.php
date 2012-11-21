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
	namespace Smash\Storage\Config;

	use	Smash\Library,
		Smash\Core,
		Smash\Error,
		Smash\Storage\Config,
		Smash\Storage\Filestream;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Storage\Config
	 * @uses         Smash\Library,  Smash\Core,  Smash\Error,  Smash\Storage\Config,  Smash\Storage\Filestream
	 * @extends      Smash\Storage\Config
	 * @interfaces   Traversable, Iterator, Countable, ArrayAccess
	 * @package      Hydrator
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Hydrator extends Config
	{
		private $options = array(
			'asConfig'    => true,
			'iniSections' => true,
			'phpType'     => 'vars',
			'rootNode'    => 'config',
			'indent'      => 2,
			'wordwrap'    => 40,
		);

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $options (optional)
		 */
		public function __construct($options = null)
		{
			if (!empty($options)) {
				if ($options instanceof Config) {
					$this->setOptions($options->asArray());
				} else if (is_array($options)) {
					$this->setOptions($options);
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $options (Array, required)
		 */
		public function setOptions(array $options)
		{
			foreach ($options as $key => $value) {
				$this->options[$key] = $value;
			}

			return $this;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getOptions()
		{
			return $this->options;
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $root (optional)
		 */
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $option (required)
		 */
		public function getOption($option)
		{
			if (array_key_exists($option, $this->options)) {
				return $this->options[$option];
			} else {
				throw Core::error('Option does not exist: %option', array('option' => $option));
			}
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $option (required)
		 * @param    $value (required)
		 */
		public function setOption($option, $value)
		{
			$this->options[$option] = $value;
			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 * @param    $index (required)
			 */
			return $this;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $docroot (optional)
		 * @param    $options (optional)
		 */
		public function asXml($docroot = null, $options = null)
		{
			if (!empty($docroot)) {
				$this->setOption('rootNode', $docroot);
			}

			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 * @param    $index (required)
			 * @param    $value (required)
			 * @param    $edit (optional)
			 */
			if ($this->count() > 0) {
				$config = $this->asArray();
			} else {
				throw Core::error('No stored configuration data was found');
			}

			$xml = Library::factory('serialize.xml', $this->getOptions());
			return $xml->serialize($config);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   private
		 * @param    $adapter (required)
		 * @param    $data (Array, required)
		 */
		private function prepareData($adapter, array $data)
		{
			switch ($adapter) {
				case 'yml' :
					if (function_exists('syck_dump')) {
						return syck_dump($data);
					} else {
						return \Yaml\Spyc::YAMLDump($data, $this->getOption('indent'), $this->getOption('wordwrap'));
					}
					break;
				case 'ini' :
				case 'xml' :
					$xml = Library::factory('serialize.'. $adapter, $this->getOptions());
					return $xml->serialize($data);
					break;
				case 'php' :
					$php = Library::factory('serialize.php');
					return $php->arrayToPHP($data, $this->getOption('phpType'), $this->getOption('rootNode'));
					break;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   private
		 * @param    $adapter (required)
		 * @param    $file (required)
		 */
		private function parseFile($adapter, $file)
		{
			switch ($adapter) {
				case 'yml' :
					$stream = Library::factory('storage.filestream');
					$data   = $stream->readAll($file);

					if (function_exists('syck_load')) {
						$dump = syck_load($data);
						$data = is_array($dump) ? $dump : array();
					} else {
						$data = \Yaml\Spyc::YamlLoad($data);
					}
					break;
				case 'ini' :
				case 'xml' :
					$xml  = Library::factory('serialize.'. $adapter, $this->getOptions());
					return $xml->unserialize($file);
					break;
				case 'php' :
					/**
					 * Description goes here ...
					 * 
					 * @access   public
					 * @param    $index (required)
					 */
					$php  = Library::factory('serialize.php');
					return $php->convertPHP($file, $this->getOptions());
					break;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   private
		 * @param    $config (Array, required)
		 * @param    $sections (optional)
		 */
		private function parseConfig(array $config, $sections = null)
		{
			$processed = array();

			if (empty($sections)) {
				/**
				 * Description goes here ...
				 * 
				 * @access   public
				 * @param    $index (required)
				 */
				$processed = $config;
			} else if (is_array($sections)) {
				foreach ($sections as $section) {
					if (array_key_exists($section, $config)) {
						$processed[$section] = $config[$section];
					} else {
						throw Core::error('config.section-not-exists', array('section' => $section));
					}
				}
			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 * @param    $index (required)
			 * @param    $value (required)
			 */
			} else {
				if (array_key_exists($sections, $config)) {
					$processed = $config[$sections];
				} else {
					throw Core::error('config.section-not-exists', array('section' => $section));
				}
			}

			if ($this->getOption('asConfig')) {
				parent::__construct($processed);
				return $this;
			} else {
				return $processed;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $file (required)
		 * @param    $options (optional)
		 * @param    $sections (optional)
		 */
		public function read($file, $options = null, $sections = null)
		{
			$adapter = $this->getAdapter($file);

			if (!empty($options)) {
				$this->setOptions($options);
			}

			$data = $this->parseFile($adapter, $file);
			return $this->parseConfig($data, $sections);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $file (required)
		 * @param    $data (optional)
		 * @param    $options (optional)
		 */
		public function write($file, $data = null, $options = null)
		{
			$adapter = $this->getAdapter($file);

			if (!empty($options)) {
				/**
				 * Description goes here ...
				 * 
				 * @access   public
				 * @param    $index (required)
				 */
				$this->setOptions($options);
			}

			if (!empty($data)) {
				if (is_array($data)) {
					$config = $data;
				} else if ($data instanceof Config) {
					$config = $data->asArray();
				} else {
					throw Core::error('Invalid data specified for writing to config file %file', array('file' => $file, 'data' => $data));
				}
			} else {
				if ($this->count() > 0) {
					$config = $this->asArray();
				} else {
					throw Core::error('No stored configuration data was found');
				}
			}

			$data   = $this->prepareData($adapter, $config);
			$stream = Library::factory('storage.filestream', $file, Filestream::MODE_WRITE);

			return $stream->write($data);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   private
		 * @param    $adapter (required)
		 */
		private function getAdapter($adapter)
		{
			if (strpos($adapter, '.') === false) {
				throw Core::error('config.invalid-adapter', array('adapter' => $adapter));
			}

			$f       = explode('.', $adapter);
			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 * @param    $index (required)
			 */
			$adapter = strtolower(array_pop($f));

			switch ($adapter) {
				case 'yaml' :
					return 'yml';
					break;
				case 'php'  :
				case 'xml'  :
				case 'ini'  :
					return $adapter;
					break;
				/**
				 * Description goes here ...
				 * 
				 * @access   public
				 */
				default     :
					throw Core::error('config.invalid-adapter', array('adapter' => $adapter));
			}
		}
	}
?>