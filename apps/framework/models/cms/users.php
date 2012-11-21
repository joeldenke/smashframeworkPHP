<?php
	class Model_Cms_Users
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

	}
?>
