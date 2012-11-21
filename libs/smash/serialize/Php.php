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
	
	use	Smash\Core;

	/**
	 * Description goes here ...
	 * 
	 * @namespace    Smash\Serialize
	 * @uses         Smash\Core
	 * @extends      Smash\Serialize\Lexer
	 * @interfaces   Iterator, Traversable, ArrayAccess, SeekableIterator, Serializable, Countable, Smash\Serialize\Surface
	 * @package      Php
	 * @author       Joel Denke <mail@happyness.se>
	 * @license      http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Php extends Lexer implements Surface
	{
		const T_NONE = 0;
		const T_OPEN_CURLY_BRACKET = 1000;
		const T_CLOSE_CURLY_BRACKET = 1001;
		const T_OPEN_SQUARE_BRACKET = 1002;
		const T_CLOSE_SQUARE_BRACKET = 1003;
		const T_OPEN_PARENTHESIS = 1004;
		const T_CLOSE_PARENTHESIS = 1005;
		const T_COLON = 1006;
		const T_STRING_CONCAT = 1007;
		const T_INLINE_THEN = 1008;
		const T_NULL = 1009;
		const T_FALSE = 1010;
		const T_TRUE = 1011;
		const T_SEMICOLON = 1012;
		const T_EQUAL = 1013;
		const T_MULTIPLY = 1015;
		const T_DIVIDE = 1016;
		const T_PLUS = 1017;
		const T_MINUS = 1018;
		const T_MODULUS = 1019;
		const T_POWER = 1020;
		const T_BITWISE_AND = 1021;
		const T_BITWISE_OR = 1022;
		const T_ARRAY_HINT = 1023;
		const T_GREATER_THAN = 1024;
		const T_LESS_THAN = 1025;
		const T_BOOLEAN_NOT = 1026;
		const T_SELF = 1027;
		const T_PARENT = 1028;
		const T_DOUBLE_QUOTED_STRING = 1029;
		const T_COMMA = 1030;
		const T_HEREDOC = 1031;
		const T_PROTOTYPE = 1032;
		const T_THIS = 1033;
		const T_REGULAR_EXPRESSION = 1034;
		const T_PROPERTY = 1035;
		const T_LABEL = 1036;
		const T_OBJECT = 1037;
		const T_COLOUR = 1038;
		const T_HASH = 1039;
		const T_URL = 1040;
		const T_STYLE = 1041;
		const T_ASPERAND = 1042;
		const T_NEW_LINE = 1043;

		private $lines = array();
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $input (optional)
		 */
		public function __construct($input = null)
		{
			if (!empty($input)) {
				if (is_file($input)) {
					$source = file_get_contents($input);
				} else if (is_string($input)) {
					$source = $input;
				} else {
					throw Core::error('Unsupported input format specified');
				}
				
				$this->import($this->tokenize($source));
			}
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getLines()
		{
			return $this->lines;
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $source (required)
		 */
		public function tokenize($source)
		{
			$tokens = token_get_all($source);
			$parsed = array();
			$line   = 1;

			foreach ($tokens as $token) {
				if (!array_key_exists($line, $this->lines)) {
					$this->lines[$line] = array();
				}

				$element  = $this->parseToken($token);
				$segments = preg_split("/(\r\n|\n|\r)/", $element->data, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				
				foreach ($segments as $segment) {
					switch ($segment) {
						case "\n" :
						case "\r\n" :						
						case "\r" :
							$rowElement           = $this->parseToken(array(self::T_NEW_LINE, $segment));
							$rowElement->line     = $line;
							$this->lines[$line][] = $rowElement;
							$parsed[]             = $rowElement;
							$line++;
							break;
						default :
							if ($element->type === T_COMMENT) {
								$element = $this->parseToken(array(T_COMMENT, $segment));
							}

							$element->line        = $line;
							$element->data        = $segment;
							$this->lines[$line][] = $element;
							$parsed[]             = $element;
					}
				}
			}
			
			return $parsed;
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $token (required)
		 */
		public function parseToken($token)
		{
			$element = new \StdClass();

			if (is_array($token)) {
				$element->data = $token[1];

				switch ($token[0]) {
					case self::T_NEW_LINE :
						$element->name = 'T_NEW_LINE';
						$element->type = constant(__NAMESPACE__ .'\\Php::'. $element->name);
						break;
					case T_STRING :
						switch (strtolower($token[1])) {
							case 'false':
								$element->name = 'T_FALSE';
								break;
							case 'true':
								$element->name = 'T_TRUE';
								break;
							case 'null':
								$element->name = 'T_NULL';
								break;
							case 'self':
								$element->name = 'T_SELF';
								break;
							case 'parent':
								$element->name = 'T_PARENT';
								break;
							default:
								$element->name = 'T_STRING';
								$element->type   = $token[0];
								break;
						}

						if ($element->name !== 'T_STRING') {
							$element->type = constant(__NAMESPACE__ .'\\Php::'. $element->name);
						}
						break;
					case T_CURLY_OPEN:
						$element->type = self::T_OPEN_CURLY_BRACKET;
						$element->name = 'T_OPEN_CURLY_BRACKET';
						break;
					default:
						$element->type = $token[0];
						$element->name = token_name($token[0]);
						break;
				}
			} else {
				switch ($token) {
					case '{':
						$element->name = 'T_OPEN_CURLY_BRACKET';
						break;
					case '}':
						$element->name = 'T_CLOSE_CURLY_BRACKET';
						break;
					case '[':
						$element->name = 'T_OPEN_SQUARE_BRACKET';
						break;
					case ']':
						$element->name = 'T_CLOSE_SQUARE_BRACKET';
						break;
					case '(':
						$element->name = 'T_OPEN_PARENTHESIS';
						break;
					case ')':
						$element->name = 'T_CLOSE_PARENTHESIS';
						break;
					case ':':
						$element->name = 'T_COLON';
						break;
					case '.':
						$element->name = 'T_STRING_CONCAT';
						break;
					case '?':
						$element->name = 'T_INLINE_THEN';
						break;
					case ';':
						$element->name = 'T_SEMICOLON';
						break;
					case '=':
						$element->name = 'T_EQUAL';
						break;
					case '*':
						$element->name = 'T_MULTIPLY';
						break;
					case '/':
						$element->name = 'T_DIVIDE';
						break;
					case '+':
						$element->name = 'T_PLUS';
						break;
					case '-':
						$element->name = 'T_MINUS';
						break;
					case '%':
						$element->name = 'T_MODULUS';
						break;
					case '^':
						$element->name = 'T_POWER';
						break;
					case '&':
						$element->name = 'T_BITWISE_AND';
						break;
					case '|':
						$element->name = 'T_BITWISE_OR';
						break;
					case '<':
						$element->name = 'T_LESS_THAN';
						break;
					case '>':
						$element->name = 'T_GREATER_THAN';
						break;
					case '!':
						$element->name = 'T_BOOLEAN_NOT';
						break;
					case ',':
						$element->name = 'T_COMMA';
						break;
					case '@':
						$element->name = 'T_ASPERAND';
						break;
					default:
						$element->name = 'T_NONE';
						break;
				}

				$element->type = constant(__NAMESPACE__ .'\\Php::'. $element->name);
				$element->data = $token;
			}

			return $element;
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public, static
		 * @param    $array (Array, required)
		 * @param    $indent (optional)
		 */
		static public function arrayAsString(array $array, $indent = 0)
		{
			$data = "";
			$first = true;

			foreach ($array as $key => $value) {
				if (!$first) {
					$data .= ",". Core::CRLF;
				} else {
					$first = false;
				}

				$data .= str_repeat("\t", $indent);

				if (is_array($value)) {
					$data .= "'$key' => array(". Core::CRLF . self::arrayAsString($value, $indent+1) . Core::CRLF . str_repeat("\t", $indent) .")";
				} elseif (is_string($value)) {
					$data .= "'$key' => '$value'";
				} elseif (is_int($value)) {
					$data .= "'$key' => $value";
				} elseif (is_bool($value)) {
					$data .= "'$key' => ". ($value ? "true" : "false");
				}
			}

			return $data;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   private
		 * @param    $file (required)
		 */
		private function getData($file)
		{
			ob_start();
			include($file);
			$constants = get_defined_constants(true);
			$constants = array_key_exists('user', $constants) ? $constants['user'] : array();
			return array('vars' => get_defined_vars(), 'constants' => $constants, 'return' => ob_get_clean());
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $file (required)
		 * @param    $options (Array, required)
		 */
		public function convertPHP($file, array $options)
		{
			$data = $this->getData($file);

			if (is_array($data['return'])) {
				return $data['return'];
			} else {
				$type = $options['phpType'];

				if (is_string($options['rootNode'])) {;
					switch ($type) {
						case 'vars' :
						case 'constants' :
						case 'return' :
							if (array_key_exists($options['rootNode'], $data[$type])) {
								return $data[$type][$options['rootNode']];
							} else {
								return $data[$type];
							}
							break;
						default :
							return array();
							break;
					}
				} else {
					return $data[$type];
				}
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $data (Array, required)
		 * @param    $type (required)
		 * @param    $name (required)
		 */
		public function arrayToPHP(array $data, $type, $name)
		{
			$serialized = "<?php". Core::CRLF . "\t";

			switch ($type) {
				case 'return' :
					$serialized .= "return array(". Core::CRLF . self::arrayAsString($data, 2);
				case 'vars' :
				default      :
					$serialized .= "$"."$name = array(". Core::CRLF . self::arrayAsString($data, 2);
					break;
			}

			return $serialized . Core::CRLF ."\t);". Core::CRLF ."?>";
		}
	}
?>