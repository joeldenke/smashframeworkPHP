<?php
	define('STARTTIME', microtime());

	$rootPath = dirname(__FILE__)  . DIRECTORY_SEPARATOR;
	include $rootPath .'libs'  . DIRECTORY_SEPARATOR .'smash'  . DIRECTORY_SEPARATOR .'Core.php';
	include $rootPath .'apps/framework/config/config.php';

	try {
		$core = new \Smash\Core();
		$core->connect('framework', $config);
		
		/*$directory = new \Smash\Storage\Directory($rootPath .'libs'  . DIRECTORY_SEPARATOR .'smash');
		$stats = $directory->stats();

		echo 'rader: '. $stats['lines'] . "<br />\n";
		echo 'filer: '. $stats['files'] . "<br />\n";
		echo 'mappar: '. $stats['dirs'] . "<br />\n";
		echo 'storlek: '.\Smash\Storage\Filestream::niceSize($stats['size']) . "<br />\n";*/
	} catch (Exception $e) {
		echo $e;
	}

	define('ENDTIME', microtime());

	// echo '<strong>Execution time:</strong> '. (ENDTIME - STARTTIME) .' seconds';
?>