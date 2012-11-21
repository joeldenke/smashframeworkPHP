<?php
	class Controller_Cms_Auth
	{
		protected $db    = null;
		protected $auth  = null;
		
	    public function init()
	    {
	    	$config     = Smash_Storage_Record::fetch('config');	
			$this->db   = Smash_Storage_Record::fetch('db');
			
			$authModel  = new Smash_Backend_Model_DB($this->db, $config->db->tables->users);
			$authModel->setIdentityCol('nick')
					  ->setCredentialCol('pass');
			
			$this->auth = Smash_Backend::singleton();
			$this->auth->setModel($authModel);
	    }
	    
	    public function indexCommand()
	    {
			return $this->loginCommand();
		}

		public function loginCommand()
		{
			$front  = $this->getFront();
			$output = $front->response;
			$input  = $front->request;
			
			if ($input->isPost()) {
				if (!$input->isPost(array('identity', 'credential', 'referer'))) {
					throw Smash::error('What exactly do you think you are doing?');
				}
				
				$referer  = $input->getPost('referer');
				$username = $input->getPost('identity');
				$password = Smash_Crypto_Hash::sfHash($input->getPost('credential'), 25);
				
				$this->auth->setIpAddress($input->getIP());
				$this->auth->setUserAgent($input->getServer('HTTP_USER_AGENT'));
				$user     = $this->auth->identify($username, $password);
				
				if ($user->isAuthorized()) {
					if ($referer == $input->getURL()) {
						$referer = $input->getHost();
					}
					
					$this->redirect($referer);
				} else {
					switch ($user->getStatus()) {
						case Smash_Backend_User::STATUS_CREDENTIAL_INVALID :
							$error = 'Lösenordet du angivit är felaktigt';
							break;
						case Smash_Backend_User::STATUS_IDENTITY_NOT_FOUND :
							$error = 'Användarnamnet du angivit finns inte i databasen';
							break;
						case Smash_Backend_User::STATUS_IDENTITY_NOT_EXCLUSIVE :
							$error = 'Användarnamnet är inte exklusivt';
							break;
						default :
							$error = 'Något gick snett vid inloggningen, försök igen';
							break;
					}
				}
			} else {
				$referer = $input->getParam('referer');
				
				if (empty($referer)) {
					$referer = $input->getHost();
				}
			}
			
			$view = $front->view;
			$front->setPathModel('view', ':path:page_:command.:suffix');
						
			$view->referer = $referer;
			$view->error   = isset($error) ? $error : null;
			$view->host    = $input->getHost();
			$view->title   = 'Parlinvest - Logga in';
		}
	}
?>