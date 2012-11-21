<?php
	use	Smash\Library,
		Smash\Backend,
		Smash\Error;
		
	class Controller_Blog_Auth
	{
	    public function init($controller, $config, $model)
	    {
			$users   = $model->factory('default.users', $config->db->dsn);
			$request = Library::dependecy('mvc.controller.request');
			$backend = Library::dependecy('backend', $users, 'mcrypt');

			if (!$backend->verify($request)) {
				$backend->clearIdentity();
				$controller->forward('login', array('referer' => $request->getURL()));
			}
	    }

	    public function index($view)
	    {
	    	return $view;
	    }

		public function login($resource, $view, $controller, $module)
		{
			$i18n    = Library::dependecy('i18n.locale.translate', $controller, $module->getOptions());
			$request = Library::dependecy('mvc.controller.request');

			if ($request->isPost()) {
				if (!$request->isPost(array('identity', 'credential'))) {
					$view->error = $i18n->translate('empty-username-or-password');
				} elseif (!$request->isPost(array('referer'))) {
					$view->error = $i18n->translate('potential-hack-attempt');
				} else {
					$referer  = $request->getPost('referer');
					$username = $request->getPost('identity');
					$password = $request->getPost('credential');
					$password = Smash\Backend\Crypto\Hash::generate('sha256', $password, $username, 10, 25);
					$backend  = Library::dependecy('backend');

					if ($backend->identify($username, $password, $request)) {
						$resource->redirect($request->getHost() .'/blog');
					} else {
						switch ($backend->getStatus()) {
							case Backend::STATUS_CREDENTIAL_INVALID :
								echo $password;
								$view->error = $i18n->translate('invalid-credential');
								break;
							case Backend::STATUS_IDENTITY_NOT_FOUND :
								$view->error = $i18n->translate('identity-not-found');
								break;
							case Backend::STATUS_IDENTITY_NOT_EXCLUSIVE :
								$view->error = $i18n->translate('identity-not-exclusive');
								break;
							default :
								$view->error = $i18n->translate('unknown-login-problem');
								break;
						}
					}
				}

				return $view;
			} else {
				$referer = $controller->getParam('referer');

				if (empty($referer)) {
					$referer = $request->getHost() .'/blog';
				}

				$view->referer = $referer;
			}

			return $view;
		}

		public function logout($resource)
		{
			$auth = Library::dependecy('backend');
			$auth->clearIdentity();

			$resource->redirect('/blog/auth');
		}
	}
?>