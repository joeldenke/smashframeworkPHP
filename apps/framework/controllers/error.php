<?php
	class Controller_Error
	{
		public function error($error, $view, $resource)
		{
			$view->error = $error;
			
			if ($error->getCode() >= 200 && $error->getCode() < 599) {
				$resource->setResponseCode($error->getCode());
			}
			
			return $view->render('error');
		}
	}
?>