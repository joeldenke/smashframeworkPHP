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

	use   	Smash\Core,
		Smash\Library;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Mvc\View\Helper
	 * @uses        Smash\Core,  Smash\Library
	 * @package     Doctype
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Doctype
	{
		const HTML5         = 'HTML5';
		const HTML4_STRICT  = 'HTML4_STRICT';
		const HTML4_TRANS   = 'HTML4_TRANS';
		const HTML4_FRAME   = 'HTML4_FRAME';

		const XHTML_STRICT  = 'XHTML_STRICT';
		const XHTML_TRANS   = 'XHTML_TRANS';
		const XHTML_FRAME   = 'XHTML_FRAME';
		const XHTML11       = 'XHTML11';

		const XHTML_BASIC   = 'XHTML_BASIC';
		const XHTML_BASIC11 = 'XHTML_BASIC11';

		private $docTypes   = array(
			self::HTML5         => '<!DOCTYPE html>',
			self::HTML4_STRICT  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
			self::HTML4_TRANS   => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
			self::HTML4_FRAME   => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
			self::XHTML_STRICT  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
			self::XHTML_TRANS   => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
			self::XHTML_FRAME   => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
			self::XHTML11       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
			self::XHTML_BASIC   => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">',
			self::XHTML_BASIC11 => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">'
		);
		private $docType    = self::XHTML_TRANS;

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $doctype (optional)
		 */
		public function getDoctype($doctype = null)
		{
			if (empty($doctype)) {
				$doctype = $this->docType;
			}

			return $this->docTypes[$doctype];
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $doctype (optional)
		 */
		public function doctype($doctype = null)
		{
			if (empty($doctype)) {
				return $this->getDoctype();
			} else if (isset($this->docTypes[$doctype])) {
				return $this->getDoctype($doctype);
			} else {
				throw Core::error('Doctype you specified is not valid', array('doctype' => $doctype));
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function __toString()
		{
			return $this->getDoctype();
		}
	}
?>