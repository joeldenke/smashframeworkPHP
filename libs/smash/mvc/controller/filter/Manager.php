<?php/**
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
	namespace Smash\Mvc\Controller\Filter;		use	Smash\Core;		/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Mvc\Controller\Filter
	 * @uses         Smash\Core
	 * @extends      ArrayIterator
	 * @interfaces   Countable, Serializable, SeekableIterator, ArrayAccess, Traversable, Iterator
	 * @package      Manager
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Manager extends \ArrayIterator	{		protected $front;		protected $coreChain;		protected $cursor = 0;		// Inspiration: http://www.sitepoint.com/forums/showthread.php?t=184548&page=4		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $filters (Array, required)
		 */
		public function __construct(array $filters)		{			$this->front = $front;			$filters     = $this->getValidFilters($filters);						parent::__construct($filters);		}				/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $filter (required)
		 * @param    $index (optional)
		 */
		public function addFilter($filter, $index = null)		{			if ($filter instanceof Intercept) {				if (empty($index)) {					$index = $this->count();				}				if (!$this->offsetExists($index)) {					$this->offsetSet($index, $filter);				}								$this->ksort();			}						return $this;		}				/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function removeFilter($index)		{			if ($this->offsetExists($index)) {				$this->offsetUnset($index);			}						return $this;		}				/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $filters (Array, required)
		 * @param    $recursive (optional)
		 * @param    $deep (optional)
		 */
		public function getValidFilters(array $filters, $recursive = false, $deep = 1)		{			$filters = array_map('strtolower', $filters);			$front   = $this->getFront();			$path    = $front->getPath('filters');			$valid   = array();						if (empty($path)) {				$path = dirname(__FILE__);			}						foreach ($filters as $filter) {				if (is_string($filter)) {					/*					$file  = Smash_Os::cleanPath($path) . Smash_Inflector::classyfile($filter);					$class = Smash_Inflector::classify('smash-mvc-controller-filter-'. $filter);											if (Smash_Os::exists($file)) {						if (!Smash_Tracker::isDefined($class)) {							Smash_Tracker::includeFile($file);						}												$instance = Smash_Object::factory($class);												if ($instance instanceof Intercept) {							array_push($valid, $instance);						}					}*/				}			}						return $valid;		}		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $filters (Array, required)
		 */
		public function setFilters(array $filters)		{			foreach ($filters as $filter) {				$this->addFilter($filter);			}		}				/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getFront()		{			return $this->front;		}				/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $coreChain (required)
		 */
		public function process($coreChain)		{			$this->coreChain = $coreChain;			$this->rewind();						return $this->processNext();		}				/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function processNext()		{			if ($this->valid()) {				$filter = $this->current();				$this->next();								return $filter->process($this);			} else {				return $this->coreChain->process();			}		}	}?>