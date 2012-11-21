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
	namespace Smash\Mvc\View\Helper\Form\Decorator;
	
	use   	Smash\Core,
		Smash\Mvc\View\Helper\Form,
		Smash\Mvc\View\Helper\Form\Element;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Mvc\View\Helper\Form\Decorator
	 * @uses        Smash\Core,  Smash\Mvc\View\Helper\Form,  Smash\Mvc\View\Helper\Form\Element
	 * @extends     Smash\Mvc\View\Helper\Form\Decorator\Abstraction
	 * @package     Errors
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Errors extends Abstraction
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function __construct()
		{
			$options = array(
				'list'    => 'ul',
				'subList' => 'ol',
				'node'    => 'li',
				'label'    => 'h2',
				'listAttributes' =>array('class' => 'errors')
			);

			foreach ($options as $option => $value) {
				$this->options[$option] = $value;
			}
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $decorator (Smash\Mvc\View\Helper\Form\Decorator Object, required)
		 * @param    $element (required)
		 * @param    $attributes (Array, required)
		 * @param    $options (required)
		 * @param    $tag (optional)
		 */
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $xml (XMLWriter Object, required)
		 * @param    $decorator (required)
		 */
		public function processNext(\XMLWriter $xml, $decorator)
		{			
			$form   = $decorator->form;
			$name   = $decorator->name;
			$errors = $form->getErrors('elements');
			
			if (isset($errors[$name])) {
				$xml->startElement($this->options['subList']);
				$decorator->writeAttributes($xml, $this->options['listAttributes']);

				foreach ($errors[$name] as $error) {
					$xml->writeElement($this->options['node'], $decorator->escape($error->getMessage()));
				}
				
				$xml->endElement();
			}
			
			$decorator->processNext($xml);
		}
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $property (required)
		 */
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $xml (required)
		 * @param    $decorator (required)
		 * @param    $first (optional)
		 */
		public function processAll($xml, $decorator, $first = true)
		{
			if ($first) {
				$xml->startElement($this->options['list']);
				$decorator->writeAttributes($xml, $this->options['listAttributes']);
			} else {
				$xml->startElement($this->options['subList']);
			}

			$first = false;
			
			foreach ($errors as $name => $error) {
				if (is_array($error)) {
					$xml->startElement($this->options['node']);
					/**
					 * Description goes here ...
					 * 
					 * @access   public
					 * @param    $property (required)
					 * @param    $value (required)
					 */
					$xml->writeElement($this->options['label'], $decorator->escape($name));
					$this->processAll($xml, $decorator, $error, $first);
					$xml->endElement();
				} else {
					$xml->writeElement($this->options['node'], $decorator->escape($error->getMessage()));
				}
			}
			
			$xml->endElement();
		}
	}
?>