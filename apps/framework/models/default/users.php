<?php
	use Smash\Core,
		Smash\Backend\Model\Surface;
	
	class Model_Default_Users extends Smash\Mvc\Model\Activerecord implements Smash\Backend\Model\Surface
	{
		public $table      = 'splog_accounts'; 
		public $primaryKey = 'id';
		public $fields     = array(
			'id',
			'auth',
			'nick',
			'pass',
		);
		public $rules      = array(
			'id' => array(
				array('integer'),
				array('min', 0),
				array('maxlength', 3),
				array('notnull'),
			),
			'auth' => array(
				array('integer'),
				array('maxlength', 2),
				array('notnull'),
			),
			'nick' => array(
				array('maxlength', 15),
				array('notnull'),
			),
			'pass' => array(
				array('maxlength', 25),
				array('notnull'),
			),
		);

		public function verify($identity)
		{
			if (empty($this->table)) {
				throw Core::error('A table name must be supplied');
			}
			
			$driver = $this->getDriver();
			$select = $driver->select();
			$select->from($this->table)
				   ->cols(array($this->fields[2]))
				   ->where($driver->quoteIdentify($this->fields[2]) .' = '. $driver->quote($identity));
			$result = $driver->query((string) $select)->fetchAll(Smash\Mvc\Model::FETCH_ASSOC);
			
			if (count($result) < 1) {
				return false;
			} else if (count($result) > 1) {
				return false;
			} else {
				return true;
			}
		}
		
		public function identify($identity, $credential)
		{
			if (empty($this->table)) {
				throw Core::error('A table name must be supplied');
			} else if (empty($identity)) {
				throw Core::error('An identity must be supplied');
			} else if (empty($credential)) {
				throw Core::error('A credential must be supplied');
			}
			
			$driver = $this->getDriver();
			$select = $driver->select();
			$select->from($this->table)
				   ->cols(array('*'))
				   ->where($driver->quoteIdentify($this->fields[2]) .' = '. $driver->quote($identity));
			$result = $driver->query((string) $select)->fetchAll(Smash\Mvc\Model::FETCH_ASSOC);
			
			if (count($result) < 1) {
				return Smash\Backend::STATUS_IDENTITY_NOT_FOUND;
			} else if (count($result) > 1) {
				return Smash\Backend::STATUS_IDENTITY_NOT_EXCLUSIVE;
			}
			
			$pass = $result[0][$this->fields[3]];
			unset($result[0][$this->fields[3]]);
			
			if ($credential !== $pass) {
				return Smash\Backend::STATUS_CREDENTIAL_INVALID;
			} else {
				return Smash\Backend::STATUS_AUTHORIZED;
			}
		}
	}
?>
