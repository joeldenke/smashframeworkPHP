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

	use   Smash\Library,
		Smash\Core,
		Smash\Error,
		Smash\Validate,
		Smash\Mvc\View;

	// @TODO : Fix so it saves all data into ONE single array and then output it with XML serializer
	// That would solve the indent problem and also make the generator run XML::serialize() less times and get perfomance
	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Mvc\View\Helper
	 * @uses         Smash\Library,  Smash\Core,  Smash\Error,  Smash\Validate,  Smash\Mvc\View
	 * @extends      ArrayIterator
	 * @interfaces   Countable, Serializable, SeekableIterator, ArrayAccess, Traversable, Iterator
	 * @package      Form
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Form extends \ArrayIterator
	{
		const MODE_PREPEND = 'mode_prepend';
		const MODE_APPEND  = 'mode_append';
		const MODE_WRAP    = 'mode_wrap';

		private $decorator;
		private $view;

		private $attributes   = array();
		private $elements   = array();
		private $containers = array();
		private $defaults    = array();
		private $values       = array();
		private $errors        = array();
		private $processErrors = array();
		
		public $options = array(
			'breakChainOnError' => false,
			'html'              => true,
			'indentation'       => "\t",
		);

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $attributes (Array)
		 */
		public function __construct(array $attributes = array())
		{
			$this->decorator = Library::factory('mvc.view.helper.form.decorator', $this);
			$this->decorator->decorate('form', $attributes, array('mode' => Form::MODE_WRAP));
			$this->setAttributes($attributes);
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
			if (isset($this->options[$option])) {
				$this->options[$option] = $value;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $attributes (Array, required)
		 */
		public function setAttributes(array $attributes)
		{
			$this->attributes = $attributes;
		}

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

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $name (required)
		 * @param    $order (required)
		 */
		public function setOrder($name, $order)
		{
			if (is_int($order) && $order > 0) {
				$orders = $this->getArrayCopy();
				$elements = array();
				$start    = false;

				foreach ($orders as $key => $value) {
					if ($order == $value) {
						$start = true;
						$elements[$name] = $value;
					}
					if ($start) {
						$elements[$key] = $value + 1;
					}
				}

				if (empty($elements)) {
					$this->offsetSet($name, $order);
				} else {
					foreach ($elements as $k2 => $v2) {
						$this->offsetSet($k2, $v2);
					}
				}
			} else {
				$order = $this->count() + 1;
				$this->offsetSet($name, $order);
			}

			$this->asort();
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $element (required)
		 * @param    $name (required)
		 * @param    $attributes (Array)
		 * @param    $options (Array)
		 */
		public function addElement($element, $name, array $attributes = array(), array $options = array())
		{
			$element               = Library::factory('mvc.view.helper.form.element', $this, $element, $name, $attributes, $options);
			$this->elements[$name] = $element;
			$this->setOrder($name, $element->getOrder());

			return $element;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $name (required)
		 * @param    $fields (Array, required)
		 * @param    $order (optional)
		 */
		public function addContainer($name, array $fields, $order = 0)
		{
			$elements = array();

			foreach ($fields as $field) {
				if (isset($this->elements[$field])) {
					$elements[$field] = $this->elements[$field];
					$this->offsetUnset($field);
				}
			}

			$container               = Library::factory('mvc.view.helper.form.container', $this, $name, $elements, $order);
			$this->containers[$name] = $container;
			$this->setOrder($name, $container->getOrder());

			return $container;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $field (required)
		 * @param    $name (required)
		 * @param    $params (Array)
		 */
		public function addRule($field, $name, array $params = array())
		{
			if (isset($this->elements[$field])) {
				$this->elements[$field]->addRule($name, $params);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $defaults (Array, required)
		 */
		public function addDefaults(array $defaults)
		{
			foreach ($defaults as $key => $default) {
				if (isset($this->elements[$key])) {
					$this->elements[$key]->changeValue($default);

					if (!array_key_exists($key, $this->defaults)) {
						$this->defaults[$key] = $default;
					}
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $locale (required)
		 */
		public function addTranslator($locale)
		{

		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getDecorator()
		{
			return $this->decorator;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $name (required)
		 * @param    $attributes (Array)
		 * @param    $options (optional)
		 */
		public function changeDecoration($name, array $attributes = array(), $options = null)
		{
			if ($this->decoration->offsetExists($tag)) {
				$decoration = $this->decorator->offsetGet($name);
				$decoration->change($attributes, $options);
			} else {
				return false;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $decorations (required)
		 */
		public function decorate($decorations)
		{
			if (!is_array($decorations)) {
				$decorations = array($decorations);
			}

			foreach ($decorations as $key => $decoration) {
				if (is_string($decoration)) {
					$this->decorator->decorate($decoration);
				} else if (is_array($decoration)) {
					$attributes = isset($decoration['attributes']) ? $decoration['attributes'] : array();
					$options    = isset($decoration['options']) ? $decoration['options'] : array();
					$this->decorator->decorate($key, $attributes, $options);
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $name (required)
		 * @param    $tag (required)
		 * @param    $attributes (Array)
		 * @param    $options (optional)
		 */
		public function decorateSimple($name, $tag, array $attributes = array(), $options = null)
		{
			try {
				$this->get($name)->decorate($tag, $attributes, $options);
			} catch (Error $e) {
				return false;
			}
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $decorations (required)
		 */
		public function decorateAll($decorations)
		{
			if (!is_array($decorations)) {
				$decorations = array($decorations);
			}

			foreach ($this as $name => $order) {
				$element = $this->get($name);
				
				foreach ($decorations as $key => $decoration) {
					if (is_string($decoration)) {
						$element->decorate($decoration);
					} else if (is_array($decoration) && is_string($key)) {
						$attributes = isset($decoration['attributes']) ? $decoration['attributes'] : array();
						$options    = isset($decoration['options']) ? $decoration['options'] : array();
						$element->decorate($key, $attributes, $options);
					}
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $decorations (required)
		 */
		public function decorateElements($decorations)
		{
			if (!is_array($decorations)) {
				$decorations = array($decorations);
			}

			foreach ($this->elements as $name => $element) {
				foreach ($decorations as $key => $decoration) {
					if (is_string($decoration)) {
						$element->decorate($decoration);
					} else if (is_array($decoration) && is_string($key)) {
						$attributes = isset($decoration['attributes']) ? $decoration['attributes'] : array();
						$options    = isset($decoration['options']) ? $decoration['options'] : array();
						$element->decorate($key, $attributes, $options);
					}
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $input (Array, required)
		 */
		public function isValid(array $input)
		{
			$elements = array();

			foreach ($this->elements as $name => $element) {
				$result = $element->validate($input);

				if ($result instanceof Validate) {
					$this->errors[$name] = $result->getErrors();

					if ($this->options['breakChainOnError']) {
						return false;
					}
				} else {
					if (isset($input[$name])) {
						$this->values[$name] = $input[$name];
					}
				}
			}

			if (empty($this->errors)) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $type (required)
		 */
		public function hasErrors($type)
		{
			$errors = $this->getErrors($type);
			return empty($errors) ? false : true;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $error (Smash\Error Object, required)
		 */
		public function processError(Error $error)
		{
			$this->processErrors[] = $error;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function attachErrors()
		{
			foreach ($this->errors as $name => $error) {
				$element = array_pop($error);
				$this->elements[$name]->changeValue($element->getMessage());
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $type (optional)
		 */
		public function getErrors($type = null)
		{
			switch ($type) {
				case 'process' :
					return $this->processErrors;
					break;
				case 'elements' :
					return $this->errors;
					break;
				default :
					return array_merge($this->errors, $this->processErrors);
					break;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $type (optional)
		 */
		public function displayErrors($type = null)
		{
			$decorator = Library::factory('mvc.view.helper.form.decorator', $this);
			$decorator->decorate('errors');
			$errors = $decorator->shift('errors');

			return $errors->processAll($this, $type);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getValidValues()
		{
			return $this->values;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function populate()
		{
			foreach ($this->values as $name => $value) {
				$this->elements[$name]->changeValue($value);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 */
		public function get($index)
		{
			if (isset($this->elements[$index])) {
				return $this->elements[$index];
			} else if (isset($this->containers[$index])) {
				return $this->containers[$index];
			} else {
				throw Core::error('mvc.view.helper.form.index-not-available', array('index' => $index));
			}
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $indent (required)
		 * @param    $output (required)
		 */
		public function indent($indent, $output)
		{
			$indentation = str_repeat($this->options['indentation'], $indent);
			$segments    = preg_split("/(\r\n|\n|\r)/", $output, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			$elements    = array();
			
			foreach ($segments as $segment) {
				if ($segment === "\n" || $segment === "\r\n" || $segment === "\r") {
					$elements[] = $segment;
				} else {
					$elements[] = $indentation . $segment;
				}
			}
			
			return implode($elements);
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $indent (optional)
		 */
		public function display($indent = 0)
		{
			$this->decorator->decorate('elements');
			
			//xdebug_start_trace('/home/oxymoron/xdebug/test');
			// var_dump(xdebug_get_tracefile_name());
			
			$xml = $this->decorator->process();
			
			// xdebug_stop_trace();

			return $this->indent($indent, $xml->outputMemory());
		}
	}
?>