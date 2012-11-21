<?php	
	class Model_Default_Users extends Smash_Mvc_Model_Activerecord implements Smash_Backend_Model_Interface
	{
		private $table   = 'user_accounts';
		private $columns = array(
	    	'credential' => 'pass',
	    	'identity'   => 'nick',
	    );
		
		public function verify($identity)
		{
			if (empty($this->table)) {
				throw Smash::error('A table name must be supplied');
			}
			
			$driver = $this->getDriver();
			$select = $driver->select();
			$select->from($this->table)
				   ->cols(array($this->columns['identity']))
				   ->where($driver->quoteIdentify($this->columns['identity']) .' = '. $driver->quote($identity));
			$result = $driver->query((string) $select)->fetchAll(Smash_Mvc_Model::FETCH_ASSOC);
			
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
				throw Smash::error('A table name must be supplied');
			} else if (empty($identity)) {
				throw Smash::error('An identity must be supplied');
			} else if (empty($credential)) {
				throw Smash::error('A credential must be supplied');
			}
			
			$driver = $this->getDriver();
			$select = $driver->select();
			$select->from($this->table)
				   ->cols(array('*'))
				   ->where($driver->quoteIdentify($this->columns['identity']) .' = '. $driver->quote($identity));
			$result = $driver->query((string) $select)->fetchAll(Smash_Mvc_Model::FETCH_ASSOC);
			
			if (count($result) < 1) {
				return Smash_Backend::STATUS_IDENTITY_NOT_FOUND;
			} else if (count($result) > 1) {
				return Smash_Backend::STATUS_IDENTITY_NOT_EXCLUSIVE;
			}
			
			$pass = $result[0][$this->columns['credential']];
			unset($result[0][$this->columns['credential']]);
			
			if ($credential !== $pass) {
				return Smash_Backend::STATUS_CREDENTIAL_INVALID;
			} else {
				return Smash_Backend::STATUS_AUTHORIZED;
			}
		}
		
		public function getTable()
		{
			return $this->table;
		}
		
		public function getInfo($id)
		{	
			$sql  = 'SELECT * FROM '. $this->driver->quoteIdentify($this->name) .' WHERE id = %d';
			$stmt = $this->driver->prepare($sql);
			$rows = $stmt->execute($id)->fetchAll();
			
			return $rows[0];
		}
		
		public function getID($username)
		{
			$sql  = 'SELECT id FROM '. $this->driver->quoteIdentify($this->name) .' WHERE username = %s';
			$stmt = $this->driver->prepare($sql);
			$rows = $stmt->execute($username)->fetchAll();
			
			return $rows[0]['id'];
		}

		public function getGroupID($id)
		{
			$sql  = 'SELECT group_id FROM '. $this->driver->quoteIdentify($this->name) .' WHERE id = %d';
			$stmt = $this->driver->prepare($sql);
			$row  = $stmt->execute($id)->fetchAll();
			
			return $row[0]['group_id'];
		}
		
		public function getEntries()
		{
			$sql  = 'SELECT id FROM '. $this->driver->quoteIdentify($this->name);
			$stmt = $this->driver->prepare($sql);
			$rows = $stmt->execute()->fetchAll();
			
			return $rows;
		}
	}
?>