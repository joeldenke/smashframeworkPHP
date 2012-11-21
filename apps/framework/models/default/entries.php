<?php	
	class EntriesModel extends ArrayIterator
	{
		protected $driver;
		
		protected $name   = null;
		protected $schema = null;
		
		public function __construct($name, $driver)
		{
			$this->setTable($name);
			$this->setDriver($driver);
			
			parent::__construct($this->getEntries());
			$this->ksort();
		}
		
		public function setTable($name)
		{
			$this->name = $name;
		}
		
		public function setDriver($driver)
		{
			if ($driver instanceof Smash_DBA_Driver_Interface) {
				$this->driver = $driver;
			}
			
			return $this;
		}
		
		public function insert($data) 
		{	
			$fields = array();
			
			foreach ($data as $field => $value) {
				if (is_int($value)) {
					$fields[$field] = $value;
				} else if (is_string($value)) {
					$fields[$field] = $value;
				} else {
					throw new Smash_Exception('Invalid data in array');
				}
			}
			
			$stmt = $this->driver->insert($this->name, $fields);
			
			try {
				$this->driver->begin();
				$rows = $stmt->execute();
				
				if ($rows == 1) {
					$this->driver->commit();
					return true;
				} else {
					$this->driver->rollback();
					throw new Smash_Exception('Insert new user to database failed');
				}
			} catch (Exception $e) {
				$this->driver->rollback();
				return false;
			}
		}
			
		public function update($id, array $data)
		{
			if (empty($data)) {
				throw new Smash_Exception('No data input to update in the database');
			}
			
			$fields = array();
			
			foreach ($data as $field => $value) {
				if (is_int($value)) {
					$fields[$field] = $value;
				} else if (is_string($value)) {
					$fields[$field] = $value;
				} else {
					throw new Smash_Exception('Invalid data in array');
				}
			}
			
			$stmt = $this->driver->update($this->name, $fields, 'id = %d');
			
			try {
				$this->driver->begin();
				$rows = $stmt->execute($id);
				
				if ($rows == 1) {
					$this->driver->commit();
					return true;
				} else {
					$this->driver->rollback();
					throw new Smash_Exception('Update user info failed');
				}
			} catch (Exception $e) {
				$this->driver->rollback();
				return false;
			}
		}
		
		public function delete($id) 
		{
			$stmt = $this->driver->delete($this->name, 'id = %d');
			
			try {
				$this->driver->begin();
				$rows = $stmt->execute($id);
				
				if ($rows == 1) {
					$this->driver->commit();
					return true;
				} else {
					$this->driver->rollback();
					throw new Smash_Exception('Deleting user failed');
				}
			} catch (Exception $e) {
				$this->driver->rollback();
				return false;
			}
		}

		
		public function getEntries()
		{
			$sql  = 'SELECT * FROM '. $this->driver->quoteIdentify($this->name);
			$stmt = $this->driver->prepare($sql);
			$rows = $stmt->execute()->fetchAll(Smash_DBA::FETCH_ASSOC);
			
			return $rows;
		}
		
		public function getEntriesByCol($col, $id)
		{
			$sql  = 'SELECT * FROM '. $this->driver->quoteIdentify($this->name) .'WHERE '. $col .' = %d';
			$stmt = $this->driver->prepare($sql);
			$rows = $stmt->execute($id)->fetchAll(Smash_DBA::FETCH_ASSOC);
			
			return $rows;
		}
		
		public function getInfo($id, $col = null)
		{
			if (empty($col)) {
				$sql  = 'SELECT * FROM '. $this->driver->quoteIdentify($this->name) .' WHERE id = %d';
				$stmt = $this->driver->prepare($sql);
				$row  = $stmt->execute($id)->fetchRow();
			} else {
				$sql  = 'SELECT '. $col .' FROM '. $this->driver->quoteIdentify($this->name) .' WHERE id = %d';
				$stmt = $this->driver->prepare($sql);
				$row  = $stmt->execute($id)->fetchRow();
				$info = $row[$col];
			}
			
			return $row;
		}
	}
?>