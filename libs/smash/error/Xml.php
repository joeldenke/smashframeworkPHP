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
	namespace Smash\Error;

	use  Smash\Error,
		Smash\Core;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Error
	 * @uses        Smash\Error,  Smash\Core
	 * @extends     Smash\Error
	 * @package     Xml
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Xml extends Error
	{
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $error (required)
		 * @param    $code (optional)
		 * @param    $xml (optional)
		 */
		public function __construct($error, $code = 0, $xml = null)
		{
			$debug        = '';
			$libxmlErrors = libxml_get_errors();

			foreach ($libxmlErrors as $libxmlerror) {
				$debug .= $this->formatError($libxmlerror) . Core::CRLF;
			}

			$debug = self::dump($debug, '<h2>XML Errors</h2>');

			libxml_clear_errors();

			if (!is_null($xml)) {
				$debug .= self::dump($xml, '<h2>XML Code</h2>');
			}

			$this->setDebug($debug);

			parent::__construct($error, $code);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   private
		 * @param    $error (required)
		 */
		private function formatError($error)
		{
			switch ($error->level) {
				case LIBXML_ERR_WARNING :
					$return = Core::locale('error.xml-warning', array('code' => $error->code));
					break;
				case LIBXML_ERR_ERROR :
					$return = Core::locale('error.xml-error', array('code' => $error->code));
					break;
				case LIBXML_ERR_FATAL :
					$return = Core::locale('error.xml-fatal', array('code' => $error->code));
					break;
			}

			$return .= trim($error->message) .
				Core::CRLF . ' '. Core::locale('general.line', array($error->line)) .
				Core::CRLF .' '. Core::locale('general.column', array($error->column));

			if ($error->file) {
				$return .= Core::CRLF .' '. Core::locale('general.file', array($error->file));
			}

			return $return;
		}
	}
?>