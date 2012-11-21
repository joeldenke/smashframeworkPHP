<?php
	class Controller_Index
	{
		public function init($controller, $config, $model)
		{
			$users   = $model->factory('default.users', $config->db->dsn);
			$request = Smash\Library::dependecy('mvc.controller.request');
			$backend = Smash\Library::dependecy('backend', $users, 'mcrypt');

			if (!$backend->verify($request)) {
				$backend->clearIdentity();
				$controller->forward('blog.auth.login', array('referer' => $request->getURL()));
			}
		}

		public function index($controller)
		{
			// set_time_limit(0);
			$commentator = Smash\Library::factory('serialize.commentator',  Smash\Core::$libs . 'smash',  Smash\Core::$libs . 'smashdoc');
			
			$result = $commentator->generate(array(
				'version' =>  Smash\Core::CORE, 'author' => 'Joel Denke', 'category' => 'Smash - Smash Makes A Sweet Harmony',
				'year' => 2011, 'license' => 'gplv3', 'email' => 'mail@happyness.se',
				'description' => 'Smash Framework is a Open Source PHP web framework to make it easier, efficient and more fun to create web applications.'));
			
			return $controller->getView('layout');
		}
	}
?>