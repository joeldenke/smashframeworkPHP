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
	namespace Smash\Mvc;

	use   Smash\Module,
		Smash\Core,
		Smash\Error,
		Smash\Mvc\Controller\Resource,
		Smash\Inflector,
		Smash\Library;

	/**
	 * Description goes here ...
	 * 
	 * @namespace   Smash\Mvc
	 * @uses        Smash\Module,  Smash\Core,  Smash\Error,  Smash\Mvc\Controller\Resource,  Smash\Inflector,  Smash\Library
	 * @package     Controller
	 * @author      Joel Denke <mail@happyness.se>
	 * @license     http://www.opensource.org/licenses/gpl-3.0.html - GNU General Public License version 3
	 */
	class Controller
	{
		private $model;
		private $view;
		private $resource;
		private $module;

		private $tracker;
		private $library;

		private $controllerDelimiter = '_';
		private $error     = false;
		private $satisfied = true;

		private $initialized = array();
		private $parts       = array();
		private $params      = array();
		private $wildcards   = array();
		private $route       = array(
			'module'     => array(),
			'controller' => 'index',
			'command'    => 'index'
		);

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $module (Smash\Module Object, required)
		 * @param    $resource (Smash\Mvc\Controller\Resource Object, required)
		 */
		public function __construct(Module $module, Resource $resource)
		{
			$this->module   = $module;
			$this->resource = $resource;

			/* Some kind of autoload if the user want to
			if ($module->hasAutoloads()) {
				$tracker = $this->getTracker()->loadModule('chain');
				$tracker->trackChain($module->getAutoLoads());
			} */
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $e (Smash\Error Object, required)
		 */
		public function setError(Error $e)
		{
			$this->error = $e;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $part (required)
		 * @param    $data (required)
		 */
		public function setup($part, $data)
		{
			switch ($part) {
				case 'module'     :
					if (is_array($data)) {
						$this->route[$part] = $data;
					}
					break;
				case 'controller' :
				case 'command'    :
					if (is_string($data)) {
						$this->route[$part] = $data;
					}
					break;
				case 'components' :
					if (is_array($data)) {
						$this->setup('controller', array_pop($data));

						if (!empty($data)) {
							$this->setup('module', $data);
						}
					}
					break;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $enable (optional)
		 */
		public function satisfied($enable = null)
		{
			if (is_bool($enable)) {
				$this->satisfied = $enable;
				return $this;
			} else {
				return $this->satisfied;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $mode (optional)
		 * @param    $default (optional)
		 */
		public function getRoute($mode = 'plain', $default = null)
		{
			if (isset($this->route[$mode])) {
				if ($mode == 'module') {
					if (!empty($default)) {
						return implode($default, $this->route[$mode]);
					} else {
						return implode(Core::DS, $this->route[$mode]);
					}
				} else {
					return $this->route[$mode];
				}
			} else {
				switch ($mode) {
					case 'plain' :
						$route = array();

						foreach ($this->route['module'] as $module) {
							array_push($route, $module);
						}

						array_push($route, $this->route['controller'], $this->route['command']);

						return $route;
					default :
						return $this->route;
						break;
				}
			}

			return $default;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getError()
		{
			if ($this->error instanceof Error) {
				return $this->error;
			} else {
				return null;
			}
		}

		// Smart method parameter forwarding, with optional parameters
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $method (ReflectionMethod Object, required)
		 */
		public function feedParams(\ReflectionMethod $method)
		{
			$params = array();

			foreach ($method->getParameters() as $param) {
				switch ($param->getName()) {
					case 'controller' :
						array_push($params, $this);
						break;
					case 'config'     :
						array_push($params, $this->getModule()->getConfig());
						break;
					case 'module'     :
					case 'model'      :
					case 'resource'   :
					case 'view'       :
					case 'error'      :
					case 'params'     :
					case 'wildcards'  :
						$method = 'get'. ucfirst($param->getName());
						array_push($params, $this->$method());
						break;
					case 'parts'  :
						array_push($params, $this->parts);
						break;
					default :
						array_push($params, $this->getComponent($param->getName()));
						break;
				}
			}

			return $params;
		}
		
		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $class (ReflectionClass Object, required)
		 */
		public function isInitialized(\ReflectionClass $class)
		{
			return in_array($class->getName(), $this->initialized);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $class (ReflectionClass Object, required)
		 * @param    $controller (required)
		 * @param    $parts (Array, required)
		 */
		public function execute(\ReflectionClass $class, $controller, array $parts)
		{
			try {
				if ($class->hasMethod('init') && !$this->isInitialized($class)) {
					$init   = $class->getMethod('init');
					$params = $this->feedParams($init);

					$init->invokeArgs($controller, $params);
					$this->initialized[] = $class->getName();

					if (!$this->satisfied()) { // Forward inside init()-method;
						return;
					}
				}

				if ($class->hasMethod('map')) {
					$this->parts = $parts;
					$command = 'map';
				} else if (!empty($parts)) {
					$command = array_shift($parts);

					if (!empty($parts)) {
						$this->setWildcards($parts);
					}
				} else {
					$command = 'index';
				}

				$method = $class->getMethod($command);
				$params = $this->feedParams($method);

				if ($method->isProtected() || $method->isPrivate()) {
					throw new \ReflectionException('Protected Controller Action');
				} else {
					$this->setup('command', $command);
					return $method->invokeArgs($controller, $params);
				}
			} catch (\ReflectionException $e) {
				throw Core::error('mvc.controller.command-not-available', array('command' => $command, 'class' => $class->getName()), 400);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $parts (Array)
		 */
		public function run(array $parts = array())
		{
			if (empty($parts)) {
				$request = Library::dependecy('mvc.controller.request');
				$uri          = $request->getUri();
				$parts      = explode('/', $uri);
			}

			$components = array();
			$included   = false;

			do {
				$part = array_shift($parts);

				if (empty($part)) {
					$part = 'index';
				}

				array_push($components, $part);

				$module = implode(Core::DS, $components);
				$path   = $this->getModule()->getPath('controller', array('controller' => $module));

				if (is_dir($path) && is_readable($path)) {
					if (empty($parts)) {
						array_push($parts, 'index');
					}

					continue;
				}

				$this->setup('components', $components);

				$components = array_map('ucfirst', $components);
				$class      = 'Controller'. $this->controllerDelimiter . implode($this->controllerDelimiter, $components);
				$file       = $this->getModule()->getPath('controller', array('controller' => $module. '.php'));

				if (is_readable($file)) {
					if (!in_array($file, get_included_files()) && !class_exists($class, false)) {
						include $file;
					}

					try {
						$reflect    = new \ReflectionClass($class);
						$controller = Library::factory($reflect);
					} catch (\ReflectionException $e) {
						throw Core::error('mvc.controller.class-not-found', array('class' => $class), 400);
					}

					do {
						$result = $this->execute($reflect, $controller, $parts);

						if (!$this->satisfied()) {
							if ($this->isInitialized($reflect)) {
								$parts = array($this->getRoute('command'));
							} else {
								$components = array();
								$parts      = $this->getRoute('plain');
							}

							$this->satisfied = true;
							continue;
						} else {
							return $result;
						}
					} while ($this->isInitialized($reflect));
				} else {
			   		throw Core::error('mvc.controller.file-not-found', array('file' => $file), 404);
				}
			} while (count($parts));
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $params (Array, required)
		 */
		public function setParams(array $params)
		{
			$this->params = array_merge($this->params, $params);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $data (Array, required)
		 */
		public function setWildCards(array $data)
		{
			$this->wildcards = array_merge($this->wildcards, $data);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getModule()
		{
			return $this->module;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $config (optional)
		 * @param    $connect (optional)
		 */
		public function getModel($config = null, $connect = true)
		{
			if (!$this->model instanceof Model) {
				$this->model = Library::factory('mvc.model', $this);
			}

			if (!is_null($config)) {
				if (!$this->model->hasDriver()) {
					($connect === true) ? $this->model->connect($config) : $this->model->driverFactory($config);
				}
			}

			return $this->model;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $layout (optional)
		 * @param    $renderer (optional)
		 */
		public function getView($layout = null, $renderer = null)
		{
			if ($this->view instanceof View) {
				return $this->view;
			} else {
				$this->view = Library::factory('mvc.view', $this, $renderer);

				if (!empty($layout)) {
					$this->view->setLayout($layout);
				}

				return $this->view;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getTracker()
		{
			return ($this->tracker instanceof \Smash\Tracker) ? $this->tracker : Library::factory('tracker');
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getResource()
		{
			return $this->resource;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $index (required)
		 * @param    $default (optional)
		 */
		public function getParam($index, $default = null)
		{
			if (isset($this->params[$index])) {
				return $this->params[$index];
			} else {
				return $default;
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getParams()
		{
			return $this->params;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 */
		public function getWildcards()
		{
			return $this->wildcards;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $pattern (required)
		 * @param    $params (optional)
		 */
		public function forward($pattern, $params = null)
		{
			$parts = explode('.', $pattern);

			if (count($parts) === 1) {
				$this->setup('command', $parts[0]);
			} else {
				$count = 0;

				do {
					switch ($count) {
						case 0 :
							$this->setup('command', array_pop($parts));
							$count += 1;
							break;
						case 1 :
							$this->setup('controller', array_pop($parts));
							$count += 1;
							break;
						default :
							$this->setup('module', $parts);
							$parts = array();
							break;
					}
				} while (count($parts));
			}

			if (is_array($params)) {
				$this->setParams($params);
			}

			$this->satisfied = false;
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $pattern (required)
		 */
		public function getCustomName($pattern)
		{
			$name  = array();
			$parts = explode('.', $pattern);
			$key   = 0;

			foreach ($parts as $key => $part) {
				switch ($key) {
					case 0 :
					case 1 :
					case 2 :
						$name[$key] = str_replace('-', Core::DS, array_shift($parts));
						break;
					default :
						throw Core::error('mvc.controller.invalid-pattern', array('pattern' => $pattern), Error::CODE_MVC);
						break;
				}
			}

			return implode(Core::DS, $name);
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $type (required)
		 * @param    $data (optional)
		 */
		public function getFile($type, $data = null)
		{
			switch ($type) {
				case 'view'     :
				case 'template' :
					$customPath = ':base-views-pages-:custom.:suffix';
					$pathKey    = 'template';
					$suffix     = $this->getView()->getSuffix();
					break;
				case 'model'    :
					$customPath = ':base-models-:custom.:suffix';
					$pathKey    = $type;
					$suffix     = $this->getModel()->getSuffix();
					break;
				default :
					throw Core::error('mvc.controller.invalid-file-type', array('type' => $type), Error::CODE_MVC);
					break;
			}

			$module = $this->getModule();

			if (is_array($data) || is_null($data)) {
				foreach (array('module', 'controller', 'command', 'suffix', 'default') as $key) {
					if (!isset($data[$key])) {
						switch ($key) {
							case 'module'     :
							case 'controller' :
							case 'command'    :
								$data[$key] = $this->getRoute($key);
								break;
							case 'default'    :
								$component = $this->getRoute('module');

								if (!empty($component)) {
									$data[$key] = $component . Core::DS . $this->getRoute('command');
								} else {
									$c = $this->getRoute('controller');

									if ($c === 'index') {
										$data[$key] = $this->getRoute('command');
									} else {
										$data[$key] = $c . Core::DS . $this->getRoute('command');
									}
								}
								break;
							case 'suffix'     :
								$data[$key] = $suffix;
								break;
						}
					}
				}

			   	$file = $module->getPath($pathKey, $data);
			} else if (is_string($data)) {
				$model = $module->getPathModel($pathKey);
				$model->change($customPath);

				$file = $this->getFile($type, array('custom' => $this->getCustomName($data)));
			} else {
				throw Core::error('mvc.controller.invalid-template-format', array('template' => $data), Error::CODE_MVC);
			}

			if (is_readable($file)) {
				return $file;
			} else {
				throw Core::error('mvc.controller.template-not-readable', array('template' => $file), Error::CODE_MVC);
			}
		}

		/**
		 * Description goes here ...
		 * 
		 * @access   public
		 * @param    $type (required)
		 * @param    $args (Array, required)
		 */
		public function __call($type, array $args)
		{
			throw Core::error('mvc.controller.method-invalid', array('method' => $type, 'args' => $args), Error::CODE_MVC);
		}
	}
?>