<?php
	use	Smash\Core,
		Smash\Library,
		Smash\Inflector,
		Smash\Error;
	
	class Controller_Blog_Index
	{
		public function init($controller, $config, $model)
		{
			$users   = $model->factory('default.users', $config->db->dsn);
			$request = Library::dependecy('mvc.controller.request');
			$auth    = Library::dependecy('backend', $users, 'mcrypt');

			if (!$auth->verify($request)) {
				$auth->clearIdentity();
				$controller->forward('blog.auth.login', array('referer' => $request->getURL()));
			}
	    }

		public function index($controller)
		{	
			return $controller->getView('layout');
		}
	}
?>