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
	namespace Smash\Mvc\Model;

	use	Smash\Core,
		Smash\Error,
		Smash\Inflector,
		Smash\Archive,
		Smash\Mvc\Model,
		Smash\Mvc\Model\Driver\Surface as iFace;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Mvc\Model
	 * @uses        Smash\Core,  Smash\Error,  Smash\Inflector,  Smash\Archive,  Smash\Mvc\Model,  Smash\Mvc\Model\Driver\Surface as iFace
	 * @package     Backup
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Backup
	{
		private $driver;
		private $base;
		private $options = array(
			'archive' => Archive::ARCHIVE_NONE,
			'path'     => ':base-:prefix:date.:suffix',
			'date'     => 'Y-m-d',
			'prefix'   => 'backup_',
			'suffix'   => 'sql'
		);

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $adapter (required)
		 * @param    $base (required)
		 * @param    $options (Array)
		 */
		public function __construct($adapter, $base, array $options = array())
		{
			$this->setDriver($adapter);

			if (is_dir($base) && is_readable($base)) {
				$this->base = Core::cleanPath($base);
			}

			if (!empty($options)) {
				foreach ($options as $key => $option) {
					if (array_key_exists($key, $this->options)) {
						$this->options[$key] = $option;
					}
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $adapter (required)
		 */
		public function setDriver($adapter)
		{
			if ($adapter instanceof Model) {
				if ($adapter->hasDriver()) {
					$this->driver = $adapter->getDriver();
				} else {
					throw Core::error('mvc.model.valid-driver-required', array('adapter' => $adapter));
				}
			} else if ($adapter instanceof iFace) {
				$this->driver = $adapter;
			} else {
				throw Core::error('mvc.model.invalid-driver-adapter', array('adapter' => $adapter));
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $method (required)
		 */
		public function run($method)
		{
			$archive = $this->getArchive($this->options['archive']);
			$data     = array(
				'base' => $this->base,
				'date' => date($this->options['date']),
				'prefix' => $this->options['prefix'],
				'suffix' => $archive->getSuffix(),
			);
			$assembler = Library::factory('serialize.assembler', $this->options['path']);
			$path           = $assembler->assemble($data);

			$archive->init($path, $this->options['overwrite']);
			$backup = $this->getBackup();
			$archive->addEntries($backup);

			$this->explore($path, $method);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getBackup()
		{
			return $this->driver->generateBackup();
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $path (required)
		 * @param    $method (required)
		 */
		public function explore($path, $method)
		{
			$file = Library::factory('storage.filestream', $path);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $archive (required)
		 */
		public function getArchive($archive)
		{
			return Library::factory('storage.archive', $archive);
		}

/*
// mysql & minor details..
$tmpDir = "/tmp/";
$user = "root";
$password = "pass";
$dbName = "db";
$prefix = "db_";

// email settings...
$to = "someone@gmail.com";
$from = "another@gmail.com";
$subject = "db - backup";
$sqlFile = $tmpDir.$prefix.date('Y_m_d').".sql";
$attachment = $tmpDir.$prefix.date('Y_m_d').".tgz";

$creatBackup = "mysqldump -u ".$user." --password=".$password." ".$dbName." > ".$sqlFile;
$createZip = "tar cvzf $attachment $sqlFile";
exec($creatBackup);
exec($createZip);*/
	}
?>