<?php
	namespace Smash\Storage\Cache\Server;
	
	use Smash\Inflector,
		Smash\Library,
		Smash\Core,
		Smash\Storage\Config;

	class Memcached implements Surface
	{
		protected $options = array(
			'servers' => array(
				array('', 11211)
			)
		);
		protected $server;
		
		public function __construct($model, $options)
		{
			if (!extension_loaded('memcached')) {
				throw Core::error('general.extension-not-loaded', array('memcached'));
			}
			
			$this->model   = $model;
			$this->options = $options->merge($this->options, false);
			
			if ($this->options->exists('persistent_id') && is_string($this->options->persisent_id)) {
				$this->server = new \Memcached($this->options->persistent_id);
			} else {
				$this->server = new \Memcached;
			}
			
			if (!count($this->server->getServerList())) {
	   			$this->server->addServers($this->options->servers);
			}
			
			if ($this->options->exists('memcached')) {
				$this->setOptions($this->options->memcached);
			}
		}
		
		public function setOptions(\Config $config)
		{
			$options = $config->asArray();
			
			foreach ($options as $key => $option) {
				$this->server->setOption($key, $option);
			}
		}
		
		// Dont wrap around an already awesome object to work with
		public function __call($method, array $params)
		{
			if (method_exists($this->server, $method)) {
				call_user_func_array(array($this->server, $method), $params);
			} else {
				throw Core::error('class.method-not-exist', array($method, $this->server));
			}
		}
	}
?>