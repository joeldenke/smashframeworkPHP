<?php
	$config = array(
		'module.framework' => array(
			'language' => 'sv',
			'environment' => 'development',
			'errorRoute' => 'error.error'
		),
		'db' => array(
			'dsn' => 'mysqli://root:@localhost:3306/blog',
			'tables' => array(
				'users' => 'splog_accounts'
			)
		)
	);
?>