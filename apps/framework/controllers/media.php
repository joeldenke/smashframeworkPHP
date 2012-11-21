<?php
	class Controller_Media
	{
		private $controller = null;

		public function init($controller)
		{
			$this->controller = $controller;
		}

		public function map(array $parts)
		{
			if (!empty($parts)) {
				$file  = implode('/', $parts);

				if (preg_match('#(?<type>images|image|css|js|javascript)/(?<file>\.?[a-z0-9\-_\.]+(\.[a-z]{2,})?$)$#', $file, $match)) {
					$media = $this->getMedia($match['file'], $match['type']);

					if ($media !== false) {
						$this->controller->satisfied(true);
						return $media;
					} else {
						throw Smash\Core::error('mvc.media.file-not-found', array('file' => $match['file'], 'type' => 'media'), 404);
					}
				} else {
					throw Smash\Core::error('mvc.media.invalid-format', array('file' => $file, 'type' => 'media'), 415);
				}
			} else {
				return $parts;
			}
		}

		public function getMedia($file, $type)
		{
			switch ($type) {
				case 'images' :
				case 'image'  :
					$type   = 'images';
					$suffix = pathinfo($file, PATHINFO_EXTENSION);
					$file   = pathinfo($file, PATHINFO_FILENAME);
					break;
				default :
					$suffix = $type;
					break;
			}

			$path = $this->controller->getModule()->getPath('media', array('file' => $file, 'type' => $type, 'suffix' => $suffix));

			if (Smash\Storage\Filestream::exists($path)) {
				return $path;
			} else {
				return false;
			}
		}
	}
?>