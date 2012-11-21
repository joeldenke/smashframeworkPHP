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
	namespace Smash\Mvc\Model\Result;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Mvc\Model\Result
	 * @interfaces   Iterator, Traversable
	 * @package      Iterator
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Iterator implements \Iterator
	{
		protected $driver;
	    protected $result;

	    protected $rows     = null;
	    protected $rowCount = 0;
	    protected $cursor   = 0;

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $result (required)
		 * @param    $driver (required)
		 */
		public function __construct($result, $driver)
		{
			$this->driver   = $driver;
			$this->result   = $result;
			$this->rows     = $driver->fetch($result);
	        $this->rowCount = $driver->numRows($result);
		}

	/**
	 * Description goes here ...
	 * 
	 * @access   public
	 */
	    public function current()
	    {
	        return $this->rows[$this->cursor];
	    }

	/**
	 * Description goes here ...
	 * 
	 * @access   public
	 */
	    public function key()
	    {
	        return $this->cursor;
	    }

	/**
	 * Description goes here ...
	 * 
	 * @access   public
	 */
	    public function next()
	    {
	        if ($this->cursor < $this->rowCount - 1) {
	            $this->driver->seek($this->result, ++$this->cursor);
	        }
	    }

	/**
	 * Description goes here ...
	 * 
	 * @access   public
	 */
	    public function rewind()
	    {
	        if ($this->cursor > 0) {
	        	$this->driver->seek($this->result, --$this->cursor);
	        }
	    }

	/**
	 * Description goes here ...
	 * 
	 * @access   public
	 */
	    public function valid()
	    {
	        return ($this->cursor >= 0 && $this->cursor < $this->rowCount - 1);
	    }
	}
?>