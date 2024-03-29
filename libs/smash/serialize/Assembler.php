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
	 * @namespace   Smash\Serialize
	 * @uses        Smash\Core
	 * @package     Assembler
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Assembler
	{
		private $invalidDelims = array('_', '-', '\\', '/', '#');
		private $delimiter     = '/';
		private $defaultModel  = null;
		private $models        = null;
		private $build         = array();

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $model (required)
		 * @param    $delimiter (optional)
		 */
		public function __construct($model, $delimiter = null)
		{
			if (!empty($delimiter)) {
				$this->setDelimiter($delimiter);
			}

			$this->defaultModel = $this->parse($model);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $delimiter (required)
		 */
		public function setDelimiter($delimiter)
		{
			$this->delimiter = $delimiter;
			return $this;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $model (required)
		 */
		public function parse($model)
		{
			if (is_string($model)) {
				$model = str_replace($this->invalidDelims, $this->delimiter, $model);
			} else {
				throw Core::error('Model must be a string', array('model' => $model));
			}

			return $model;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $model (required)
		 */
		public function change($model)
		{
			$this->model = $this->parse($model);
			return $this;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $source (Array, required)
		 */
		public function assemble(array $source)
		{
			foreach ($source as $part => $value) {
				if (is_string($part) && (is_string($value) || is_null($value))) {
					$this->build[':'. ltrim($part, ':')] = $value;
				} else {
					throw Core::error('Invalid data input: %data', array('data' => $value), 1004);
				}
			}

			if (empty($this->model)) {
				$model = $this->defaultModel;
			} else {
				$model = $this->model;
			}

			$this->reset();

			return str_replace(array_keys($this->build), array_values($this->build), $model);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function reset()
		{
			$this->model = $this->defaultModel;
		}
	}
?>