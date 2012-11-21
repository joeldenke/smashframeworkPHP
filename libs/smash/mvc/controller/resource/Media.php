<?php
	class Smash_Mvc_Controller_Resource_Media implements Smash_Mvc_Controller_Resource_Interface
	{
		private $file         = false;
		private $resource     = null;
		private $resourceType = 'page';
		private $mimeTypes    = array(
			'page' => 'Smash_Mvc_Controller_Resource_Page',
            
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
			'rss'  => 'application/rss+xml',
			'atom' => 'application/atom+xml',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
			'gz'  => 'application/x-gzip',
			'tar' => 'application/x-tar',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
			'avi'  => 'video/mpeg',
			'mpeg' => 'video/mpeg',
			'mpg'  => 'video/mpeg',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
		);
		
		public function __construct(Smash_Mvc_Controller_Resource $resource)
		{
			$this->resource = $resource;
		}
		
		public function guessResourceType(Smash_Mvc_Controller_Router $router)
		{
			$url   = $router->getUrl();
			$uri   = $url->getComponent('path');
			$parts = explode($router->getOption('uriDelimiter'), $uri);
			
			while (count($parts)) {
				$test = strtolower(array_shift($parts));
				
				if (array_key_exists($test, $this->mimeTypes)) {
					return $test;
				}
			}
			
			return false;
		}
		
		public function parseResourceType(Smash_Mvc_Controller_Router $router)
		{
			$parts = $router->getRoute();
			
			if (isset($parts['resource'])) {
				$resource = $parts['resource'];
				
				if ($resource === 'force') {
					$this->resourceType = $this->guessResourceType($router);
				} else {
					if (isset($this->mimeTypes[$resource])) {
						$this->resourceType = $resource;
					}
				}
			} else {
				$this->resourceType = $this->guessResourceType($router);
			}
		}
		
		public function getResourceType()
		{
			return $this->resourceType;
		}
		
		public function getMimeType($resource = null)
		{
			if (empty($resource)) {
				$resource = $this->getResourceType();
			}
			
			return array_key_exists($resource, $this->mimeTypes) ? $this->mimeTypes[$resource] : 'unknown';
		}
		
		public function isValid()
		{
			if ($this->file instanceof Smash_Os_File) {
				return true;
			} else {
				return false;
			}
		}
		
		public function getFile()
		{
			return $this->file;
		}
		
		public function assembleFile(Smash_Mvc_Controller_Router $router, array $paths)
		{
			$parts = $router->getRoute();
			
			if (isset($parts['pathModel'])) {
				$model      = $parts['pathModel'];
				$pathPrefix = $router->getOption('pathPrefix');

				preg_match_all(
					'/['. $router->getOption('paramPrefix') .'|'. $pathPrefix .']([+%\-_a-z0-9\*]+)/i',
					$model, $matches, PREG_SET_ORDER
				);
				
				foreach ($matches as $match) {
					list($param, $name) = $match;
					$prefix = substr($param, 0, 1);
					
					if ($prefix === $pathPrefix) {
						if (array_key_exists($name, $paths)) {
							$model = str_replace($param, rtrim($paths[$name], '\/'), $model);
						}
					} else {
						if (array_key_exists($name, $parts)) {
							$model = str_replace($param, $parts[$name], $model);
						} else if (array_key_exists($name, $parts['extra'])) {
							$model = str_replace($param, $parts['extra'][$name], $model);
						}
					}
				}

				$file = Smash_Object::factory('Smash_Os_File');
				$this->parseResourceType($router);
				
				if (!$file->hasExtension($model)) {
					$model = $model .'.'. $this->getResourceType();
				}
				
				$this->file = ($file->exists($model)) ? $file : $model;
			}
			
			return $this;
		}
	}
?>