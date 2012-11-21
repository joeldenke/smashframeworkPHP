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
	use   Smash\Core,
		Smash\Serialize\Query\Ast,
		Smash\Serialize\Query\Lexer;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Serialize\Query\Ast
	 * @uses        Smash\Core,  Smash\Serialize\Query\Ast,  Smash\Serialize\Query\Lexer
	 * @extends     Smash\Serialize\Query\Ast
	 * @package     Create
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Create extends Ast
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function parse()
		{
			$this->parsed['ast'] = $this->match(Lexer::T_CREATE);

			$match = $this->lexer->skipUntil(array(
                            Lexer::T_DATABASE,
                            Lexer::T_FUNCTION,
/**
 * Description goes here ...
 * 
 * @access   public
 * @param    $lexer (Smash\Serialize\Query\Lexer Object, required)
 * @param    $driver (Smash\Mvc\Model\Driver\Surface Object, required)
 */
                            Lexer::T_INDEX,
                            Lexer::T_PROCEDURE,
                            Lexer::T_TABLE,
                            Lexer::T_TRIGGER,
                            Lexer::T_VIEW
                        ));

			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 * @param    $special (required)
			 * @param    $value (required)
			 * @param    $change (required)
			 */
			if (empty($match)) {
				throw Core::error('Invalid SQL CREATE syntax');
			}

			$this->parsed['command'] = $match->data;

			switch ($match->type) {
				case Lexer::T_TABLE :
					$this->table();
					break;
				case Lexer::T_DATABASE :
					/**
					 * Description goes here ...
					 * 
					 * @access   public
					 * @param    $changes (Array, required)
					 * @param    $parse (Array, required)
					 */
					$this->database();
					break;
			}

			return $this->parsed;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $tokens (Array, required)
		 */
		public function options(array $tokens)
		{
			$iterator = new \ArrayIterator($tokens);
			$this->parsed['options'] = array();

			while ($iterator->valid()) {
				$value = $tokens[$iterator->key()]->data;

				if (strtolower($value) == 'default') {
					$iterator->next();

					list($key, $option) = explode('=', $value .' '. $tokens[$iterator->key()]->data, 2);
					$this->parsed['options'][$key] = $option;
				} else {
					list($key, $option) = explode('=', $value, 2);
					$this->parsed['options'][$key] = $option;
				}
/**
 * Description goes here ...
 * 
 * @access   public
 * @param    $changes (Array, required)
 */

				$iterator->next();
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function table()
		{
			$class = get_class($this->lexer);
			$parts = $this->lexer->loopFetchUntil(Lexer::T_IDENTIFIER);
/**
 * Description goes here ...
 * 
 * @access   public
 * @param    $token (required)
 * @param    $data (required)
 */

			if (!empty($parts)) {
				$this->parsed['statement'] = implode(' ', $parts);
			}

			$this->parsed['tablename'] = $this->filter($this->lexer->getToken('data'), Lexer::T_IDENTIFIER);

			if ($this->lexer->isCurrent(Lexer::T_DATA)) {
				$data = $this->lexer->getToken('data');
				$this->parsed['definitions'] = $this->parseSpecial(Lexer::T_DATA, $data);
			} else {
				$parts = $this->lexer->loopFetchUntil(Lexer::T_DATA);
				/**
				 * Description goes here ...
				 * 
				 * @access   public
				 * @param    $value (required)
				 */
				print_r($parts);

				$data = $this->lexer->getToken('data');
				$data = ltrim(rtrim($data, ')'), '(');
				$data = trim($data);
/**
 * Description goes here ...
 * 
 * @access   public
 * @param    $error (required)
 */

				$this->parsed['definitions'] = array_map('trim', explode(',', $data));
			}

			$this->options($this->lexer->loopFetchAll());
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $token (required)
		 */
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function compile()
		{
			$compiled = array($this->parsed['ast'], $this->parsed['command']);

			if (isset($this->parsed['statement'])) {
				$compiled[] = $this->parsed['statement'];
			}

			$compiled[] = $this->driver->quoteIdentify($this->parsed['tablename']);
			/**
			 * Description goes here ...
			 * 
			 * @access   public
			 * @param    $query (required)
			 */
			$compiled[] = '('. implode(', ', $this->parsed['definitions']) . ')';

			if (!empty($this->parsed['options'])) {
				foreach ($this->parsed['options'] as $key => $option) {
					$compiled[] = "$key = $option";
				/**
				 * Description goes here ...
				 * 
				 * @access   public
				 */
				}
			}

			return implode(' ', $compiled);
		}
	}
?>