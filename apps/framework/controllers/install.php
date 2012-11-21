<?php
	use	Smash\Library,
		Smash\Core,
		Smash\Inflector,
		Smash\Error;

	class Controller_Install
	{
		protected $i18n;
		protected $writer;
		protected $request;
		protected $installer;
		protected $stage = 1;

		public function init($controller, $module, $view, $config, $model)
		{
			$this->i18n      = Library::dependecy('i18n.locale.translate', $controller, $module->getOptions());
			$this->writer    = Library::dependecy('storage.config.hydrator');
			$this->request   = Library::dependecy('mvc.controller.request');
			$this->installer = $view->install(array($config, $module->getPath('tmp', array('file' => ''))));

			$form = $this->installer->getForm(array('action' => '/install/stage/1', 'method' => 'post'));
			$form->decorate(array(
				'fieldset' => array(
					'options' => array('legend' => 'Obligatoriska fält är markerade med en asterisk (*)')
				),
				'dtdd' => array(
					'options' => array('elementsClass' => 'element')
				)
			));
			
			$options = array('required' => true);
			$drivers = $model->getValidDrivers();
			$list   = array();
			
			foreach ($drivers as $driver) {
				$list[$driver] = $driver;
			}
			
			$form->setOption('html', true);
			$form->addElement('select',  'driver',   array('label' => 'Databasmotor', 'selected' => 'mysqli'), $list);
			$form->addElement('input.text',  'hostname', array('label' => 'Server'), $options);
			$form->addElement('input.text',  'username', array('label' => 'Användarnamn'), $options);
			$form->addElement('input.password',  'password', array('label' => 'Lösenord'), $options);
			$form->addElement('input.text',  'database', array('label' => 'Databas'), $options);
			$form->decorateElements('errors');
			$form->addElement('submit', 'next-step',  array('value' => 'Nästa steg', 'class' => 'button'));
			$form->addElement('cancel', 'abort',    array('value' => 'Avbryt', 'class' => 'button'));
			$form->addContainer('buttons', array('next-step', 'abort'));
			$form->addDefaults(array('hostname' => 'localhost:3306', 'driver' => 'mysqli'));
		}

		public function index($view)
		{
			$cache = Library::factory('storage.cache', 'memcached', null);
			
			if ($cache->exists('install-index')) {
				return $cache->get('install-index');
			} else {
				$view->setLayout('install');
				$view->form = $this->installer->getForm();
				$result = $view->render('install.index');
				$cache->save('install-index', $result, true, 3600);
				return $result;
			}
		}

		public function stage($wildcards, $controller, $view)
		{
			$stage = array_shift($wildcards);
			$view->setLayout('install');

			switch ($stage) {
				case 1  :
				case 2  :
				case 3  :
				case 4  :
					$controller->forward('step'. $stage);
					break;
				default :
					$controller->forward('index');
			}
		}

		public function step1($view, $config, $module, $resource, $controller)
		{	
			// @TODO: SPARA ALL DATA FRÅN FORMULÄR OCH POTENTIELL SPARBAR DATA SOM KAN BEHÖVAS I INSTALLATIONEN
			//        DÄREFTER SKA MAN VID SISTA STEGET FÅ KUNNA TA BACKUP SAMT KÖRA ALLA OPERATIONER PÅ EN GÅNG
			
			if ($this->request->isPost()) {
				$form = $this->installer->getForm();
				$form->addTranslator($this->i18n);
				$form->addRule('username', 'alphanum::between', array(3, 20));
				$form->addRule('password', 'alphanum::alpha', array(true, true));

				$this->installer->addEvent('getDSN', array(array(
					'driver' => 'form-valid-driver',
					'username' => 'form-valid-username',
					'password' => 'form-valid-password',
					'hostname' => 'form-valid-hostname',
					'database' => 'form-valid-database',
				)), 'dsn');
				$this->installer->addEvent('checkDB', array('store-dsn'));
				$this->installer->addEvent('do.config', array('db', 'dsn'), 'store-dsn');
				$this->installer->addEvent('do.backup', array('prefix' => 'database_', 'archive' => 'tar'));
				$this->installer->addAction('invalid.form.populate');
				$this->installer->addAction('success.redirect', array('/install/stage/2'));

				return $this->installer->run($this->request->getPost(), 'install.index', 'install');
			} else {
				$controller->forward('index');
			}
		}

		public function step2($view, $module, $resource, $controller)
		{
			if (stristr($this->request->getReferer(), 'install') === false) {
				$resource->redirect('/install');
				return;
			}

			$options = array('required' => true);
			$base    = $module->getPath();
			$path    = Inflector::pathify($base .'.config.sql');
			$config  = $this->installer->getTmp('store', false);
			$model   = $controller->getModel($config['dsn']);
			
			$form    = $this->installer->getForm(array('action' => '/install/stage/2', 'method' => 'post'));
			$form->decorate(array(
				'fieldset' => array(
					'options' => array('legend' => 'Installera tabeller från given absolut sökväg')
				),
				'dtdd' => array(
					'options' => array('elementsClass' => 'element')
				)
			));

			$form->addElement('input.text',  'tbprefix', array('label' => 'Tabellprefix'), $options);
			$form->addElement('input.text',  'tbpath',   array('label' => 'Sökväg till tabeller'), $options);
			$form->decorateElements('errors');
			$form->addElement('submit', 'next-step',  array('value' => 'Nästa steg', 'class' => 'button'));
			$form->addElement('cancel', 'abort',    array('value' => 'Avbryt', 'class' => 'button'));
			$form->addContainer('buttons', array('next-step', 'abort'));
			$form->addDefaults(array('tbprefix' => 'splog_', 'tbpath' => $path));

			if ($this->request->isPost()) {
				$form->addRule('tbprefix', 'alphanum::alpha', array(true));
				$form->addRule('tbpath',   'alphanum::alpha', array(true));
				$this->installer->addAction('invalid.form.populate');
				$this->installer->addAction('success.redirect', array('/install/stage/3'));
 				$this->installer->addEvent('store', array('tbpath' => 'form-valid-tbpath', 'tbprefix' => 'form-valid-tbprefix'));
				$this->installer->addEvent('process.serialize.query', array(
					'import' => array('store-tbpath'),
					'run'  => array(
						array('tablename' => 'prefix|[%store-tbprefix%]'),
						array('ast' => 'create|insert', 'command' => 'table|into'),
						2,
						false
					)
				), array($model));
				$this->installer->addEvent('serialize.query-getEntries', array(
					'tablename', array('ast' => 'create', 'command' => 'table')
				), 'tables');
				$this->installer->addEvent('do.config', array('db', 'tables'), 'store-tables');

				return $this->installer->run($_POST, 'install.index', 'install');
			} else {
				$view->form = $form;
				$view->backup = $this->installer->getTmp('backup');
				$controller->forward('index');
			}
		}

		public function step3($view, $module, $resource, $controller, $config)
		{
			if (stristr($this->request->getReferer(), 'install/stage') === false) {
				$resource->redirect('/install');
				return;
			}

			$base = $module->getPath();
			$path = Inflector::pathify($base .'.config.sql');
			$options = array('required' => true);

			$form = $this->installer->getForm(array('action' => '/install/stage/3', 'method' => 'post'));
			$form->decorate(array(
				'fieldset' => array(
					'options' => array('legend' => 'Skapa adminstratörens konto')
				),
				'dtdd' => array(
					'options' => array('elementsClass' => 'element')
				)
			));

			$form->addElement('input.text',      'username',  array('label' => 'Användarnamn'), $options);
			$form->addElement('input.password',  'password',  array('label' => 'Lösenord'),     $options);
			$form->addElement('input.password',  'cpassword', array('label' => 'Bekräfta lösenord'));
			$form->decorateElements('errors');
			
			$form->addElement('submit', 'next-step',  array('value' => 'Nästa steg', 'class' => 'button'));
			$form->addElement('cancel', 'abort',      array('value' => 'Avbryt',     'class' => 'button'));
			
			$form->addContainer('buttons', array('next-step', 'abort'));
			$form->addDefaults(array('username' => 'admin'));

			if ($this->request->isPost(array('cpassword', 'password'))) {
				$form->addRule('password', 'alphanum::between', array(5, 20, true));
				$form->addRule('cpassword', 'alphanum::match', array($_POST['password'], $_POST['cpassword'], true, 'error' => 'Lösenorden stämmer inte överens med varandra!'));
				
				$this->installer->addAction('invalid.form.populate');
				$this->installer->addAction('success.redirect', array('/install/stage/4'));
				$this->installer->addEvent('store', array('user' => 'form-valid-username', 'pass' => 'form-valid-password'));

				return $this->installer->run($_POST, 'install.index', 'install');
			} else {
				$view->form = $form;
				$controller->forward('index');
			}
		}
		
		public function step4($view, $module, $resource, $controller)
		{	
			if (stristr($this->request->getReferer(), 'install/stage/') === false) {
				$resource->redirect('/install');
				return;
			}

			$options = array('required' => true);
			$base    = $module->getPath();
			$path    = Inflector::pathify($base .'.config.sql');
			$config  = $this->installer->getTmp('config');
			$store   = $this->installer->getTmp('store', false);
			$model   = $controller->getModel($config->db->dsn);
			$form    = $this->installer->getForm(array('action' => '/install/step4', 'method' => 'post'));
			$form->decorate(array(
				'fieldset' => array(
					'options' => array('legend' => 'Bekräfta att du vill installera följande')
				),
				'dtdd' => array(
					'options' => array('elementsClass' => 'element')
				)
			));
			
			$php          = Library::factory('serialize.php');
			$view->config = $this->installer->getTmp('config', false);
			$identity     = $store['user'];
			$credential   = Smash\Backend\Crypto\Hash::generate(
							'sha256', $store['pass'], $identity, 10, 25);

			$form->addElement('submit', 'install',  array('value' => 'Installera', 'class' => 'button'));
			$form->addElement('submit', 'abort',    array('value' => 'Avbryt', 'class' => 'button'));
			$form->addContainer('buttons', array('install', 'abort'));

			if ($this->request->isPost()) {
				$form->addRule('tbprefix', 'alphanum::alpha', array(true));
				$form->addRule('tbpath',   'alphanum::alpha', array(true));

				$this->installer->addAction('invalid.form.populate');
				$this->installer->addAction('success.redirect', array('/blog'));
				$this->installer->addEvent('process.serialize.query', array(
					'import' => array('store-tbpath'),
					'run'    => array(
						array('tablename' => 'prefix|[%store-tbprefix%]'),
						array('ast' => 'create|insert', 'command' => 'table|into'),
						2
					)
				), array($model));
				$this->installer->addEvent('do.models', array('store-tables', true), $config->db->dsn);
				$this->installer->addEvent('chain', array(
					'getDriver',
					'insert'    => array(
						$config->db->tables->users,
						array('auth' => 5, 'nick' => $identity, 'pass' => $credential)
					),
					'execute'
				), $model);
				$this->installer->addAction('success.clear');

				return $this->installer->run($_POST, 'install.index', 'install');
			} else {
				$view->form = $form;
				$view->backup = $this->installer->getTmp('backup');
				$controller->forward('index');
			}
		}
	}
?>