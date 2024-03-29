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
	 * @namespace    Smash\Serialize
	 * @uses         Smash\Core
	 * @extends      ArrayIterator
	 * @interfaces   Countable, Serializable, SeekableIterator, ArrayAccess, Traversable, Iterator
	 * @package      Lexer
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	abstract class Lexer extends \ArrayIterator
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $array (required)
		 */
		public function import($array)
		{
			// echo 'after: '. print_r($array);
			parent::__construct($array);
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $token (required)
		 */
		public function isNext($token)
		{
			return $token === $this->nextToken();
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $type (required)
		 */
		public function isCurrent($type)
		{
			$token = $this->current();
			return $type === $token->type;
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $tokens (required)
		 * @param    $token (optional)
		 */
		public function isAvailable($tokens, $token = false)
		{
			$key    = $this->key();
			$result = $this->skipUntil($tokens, $token);
			$this->seek($key);
			
			return $result === false ? false : true;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (optional)
		 */
		public function getToken($index = '')
		{			
			if ($this->valid()) {
				$token = $this->current();
				$this->next();

				if (property_exists($token, $index) === true) {
					return $token->$index;
				} else {
					return $token;
				}
			} else {
				throw Core::error('No offset available');
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function nextToken()
		{
			$key   = $this->getNextKey();

			if (empty($key)) {
				throw Core::error('There is no next token in offset!');
			} else {
				$token = $this->offsetGet($key);
				return $token->type;
			}
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $tokens (required)
		 */
		public function match($tokens)
		{
			$matched = array();
			
			if (!is_array($tokens)) {
				$tokens = array($tokens);
			}
			
			foreach ($tokens as $token) {
				$match = $this->getToken();

				if ($match->type !== $token) {
					throw Core::error('Token %token does not match current token %current', array('token' => $token, 'current' => $match->type));
				} else {
					$matched[] = $match->data;
				}
			}
			
			return $matched;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $tokens (required)
		 */
		public function loopFetch($tokens)
		{
			$key    = $this->key();
			$result = $this->skipUntil($tokens);
			$this->seek($key);

			return $result;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function loopFetchAll()
		{
			$tokens = array();

			while ($this->valid()) {
				$tokens[] = $this->getToken();
			}

			return $tokens;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $tokens (required)
		 */
		public function loopFetchUntil($tokens)
		{
			$parts = array();

			if (!is_array($tokens)) {
				$tokens = array($tokens);
			}

			while ($this->valid()) {
				$data = $this->current();

				if (!in_array($data->type, $tokens)) {
					$parts[] = $this->getToken('data');
				} else {
					return $parts;
				}
			}

			return $parts;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $tokens (required)
		 * @param    $token (optional)
		 */
		public function skipUntil($tokens, $token = false)
		{
			if (!is_array($tokens)) {
				$tokens = array($tokens);
			}

			do {
				if ($this->valid()) {
					$token = $this->getToken();
				} else {
					return false;
				}
			
			} while (!in_array($token->type, $tokens));

			return $token;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getNextKey()
		{
			$key = $this->key();
			parent::next();

			if ($this->valid()) {
				$return = $this->key();
			} else {
				$return = null;
			}

			$this->seek($key);

			return $return;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $token (required)
		 */
		public function getConstant($token)
		{
			$class     = get_class($this);
			$reflect   = new \ReflectionClass($class);
			$constants = $reflect->getConstants();

			foreach ($constants as $name => $value) {
				if ($value === $token) {
					return $class . '::' . $name;
				}
			}

			return $token;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   abstract, public
		 * @param    $source (required)
		 */
		abstract public function tokenize($source);
		/**
		 * Description goes here ...
		 * 
		 * @access   abstract, public
		 * @param    $token (required)
		 */
		abstract public function parseToken($token);
	}
?>