<?php
	class Controller_Cms_Test_Index
	{	
	    public function init()
	    {
	    	$config = Smash_Storage_Record::fetch('config');
			$db     = Smash_Storage_Record::fetch('db');

			$auth = Smash_Backend::singleton();
			$auth->setModel($db, $config->db->tables->users);
			$auth->getModel()->setIdentityCol('nick');
			
			$request    = $this->getFront()->request;
			$user       = $this->auth->verify(
						$request->getIP(),
						$request->getServer('HTTP_USER_AGENT')
			);
			
			if (!$user->isAuthorized($request->getIP())) {
				$this->auth->clearIdentity();
				$this->forward('login', 'auth', 'cms', array('referer' => $request->getURL()));
			}
			
			$this->autoRender(true);
	    }
	    
		public function helloWorld()
		{
			$view = $this->getView();
			return $view->render('auth/login');
		}
	}
?>