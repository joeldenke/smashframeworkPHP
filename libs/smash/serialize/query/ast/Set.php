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
	namespace Smash\Serialize\Query\Ast;

	use   Smash\Serialize\Query\Lexer,
		Smash\Serialize\Query\Ast;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Serialize\Query\Ast
	 * @uses        Smash\Serialize\Query\Lexer,  Smash\Serialize\Query\Ast
	 * @extends     Smash\Serialize\Query\Ast
	 * @package     Set
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Set extends Ast
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function parse()
		{
			$this->parsed['ast']     = $this->match(Lexer::T_SET);
			$this->parsed['options'] = array();

			if ($this->lexer->isAvailable(Lexer::T_COMMA)) {
				do {
					/**
					 * Description goes here ...
					 * 
					 * @access   public
					 * @param    $lexer (Smash\Serialize\Query\Lexer Object, required)
					 * @param    $driver (Smash\Mvc\Model\Driver\Surface Object, required)
					 */
					$parts = $this->lexer->loopFetchUntil(Lexer::T_COMMA);
					$this->parseOption($parts);
				} while (!empty($parts) && $this->match(Lexer::T_COMMA) !== false);
			}

			return $this->parsed;
		}
/**
 * Description goes here ...
 * 
 * @access   public
 * @param    $special (required)
 * @param    $value (required)
 * @param    $change (required)
 */

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $parts (Array, required)
		 */
		public function parseOption(array $parts)
		{
			$data = implode($parts);

			list($option, $value) = explode('=', $data, 2);
			$option = trim($option);

			if (array_key_exists($option, $this->parsed['options'])) {
				throw $this->syntaxError('Duplicate option entries');
			} else {
				/**
				 * Description goes here ...
				 * 
				 * @access   public
				 * @param    $changes (Array, required)
				 * @param    $parse (Array, required)
				 */
				$this->parsed['options'][$option] = trim($value);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function compile() {}
	}
?>
