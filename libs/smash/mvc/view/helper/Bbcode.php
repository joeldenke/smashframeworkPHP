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
	namespace Smash\Mvc\View\Helper;
	use  Smash\Core,
		Smash\Mvc\View;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Mvc\View\Helper
	 * @uses         Smash\Core,  Smash\Mvc\View
	 * @interfaces   Smash\Mvc\View\Helper\Surface
	 * @package      Bbcode
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Bbcode implements Surface
	{
		protected $view;

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $view (Smash\Mvc\View Object, required)
		 */
		public function setView(View $view)
		{
			$this->view = $view;
		}

		/*
		public function __construct()
		{
			hmm ...
		}
		*/

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $label (required)
		 * @param    $value (required)
		 * @param    $attributes (Array)
		 */
		public function make($label, $value, array $attributes = array())
		{
			$value = $this->view->escape($value);

			switch ($label) {
				case 'quote' :
					$output = $this->quote($value, $attributes);
					break;
				case 'bold' :
					$output = $this->bold($value);
					break;
				default :
					throw Core::error('Unsupported BBCode usage');
					break;
			}

			return $output;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $quote (required)
		 * @param    $attributes (Array, required)
		 */
		public function quote($quote, array $attributes)
		{
			$values = array();

			foreach ($attributes as $attribute => $value) {
				$values[] = $this->view->escape($attribute). '="'. $this->view->escape($value) .'"';
			}

			return '[quote '. implode(' ', $values) .']'. $quote .'[/quote]';
		}
	}
?>