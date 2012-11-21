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
	namespace Smash\Serialize\Query;
	
	use	Smash\Core,
		Smash\Mvc\Model,
		Smash\Mvc\Model\Driver\Surface as Driver;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Serialize\Query
	 * @uses        Smash\Core,  Smash\Mvc\Model,  Smash\Mvc\Model\Driver\Surface as Driver
	 * @package     Ast
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	abstract class Ast
	{
		protected $driver;
		protected $lexer;
		protected $parsed = array();
		protected $filter    = array("\140", '"');
		protected $query;

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $lexer (Smash\Serialize\Query\Lexer Object, required)
		 * @param    $driver (Smash\Mvc\Model\Driver\Surface Object, required)
		 */
		public function __construct(Lexer $lexer, Driver $driver)
		{
			$this->driver = $driver;
			$this->lexer = $lexer;
			$this->parse();
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $special (required)
		 * @param    $value (required)
		 * @param    $change (required)
		 */
		public function specialChange($special, $value, $change)
		{
			switch ($special) {
				case 'prefix' :
					return $change . $value;
					break;
				default :
					return $value;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $changes (Array, required)
		 * @param    $parse (Array, required)
		 */
		public function manipulate(array $changes, array $parse)
		{
			$processed = array();

			foreach ($parse as $key => $value) {
				if (isset($changes[$key])) {
					if (is_string($changes[$key])) {
						if (strpos($changes[$key], '|') !== false) {
							list($special, $data) = explode('|', $changes[$key]);
							$change = $this->specialChange($special, $value, $data);
						}

						$processed[$key] = $change;
					} else if (is_array($changes[$key]) && is_array($value)) {
						$processed[$key] = $this->manipulate($changes[$key], $value);
					}
				} else {
					$processed[$key] = $value;
				}
			}

			return $processed;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $changes (Array, required)
		 */
		public function getParsed(array $changes)
		{
			if (!empty($changes)) {
				$this->parsed = $this->manipulate($changes, $this->parsed);
			}

			return $this->parsed;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $token (required)
		 * @param    $data (required)
		 */
		public function parseSpecial($token, $data)
		{
			switch ($token) {
				case Lexer::T_DATA :
					$data = ltrim(rtrim($data, ')'), '(');
					$data = trim($data);

					return array_map('trim', explode(',', $data));
				default :
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $value (required)
		 */
		public function filter($value)
		{
			return str_replace($this->filter, '', $value);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $error (required)
		 */
		public function syntaxError($error)
		{
			return Core::error($error);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $token (required)
		 */
		public function match($token)
		{
			$match = $this->lexer->getToken();

			if ($match->type !== $token) {
				$this->syntaxError('token %token doesnt match %pattern');
			} else {
				return $match->data;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $query (required)
		 */
		public function setQuery($query)
		{
			$this->query = $query;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getQuery()
		{
			return $this->query;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   abstract, public
		 */
		abstract public function parse();
		/**
		 * Description goes here ...
		 * 
		 * @access   abstract, public
		 */
		abstract public function compile();
	}
?>