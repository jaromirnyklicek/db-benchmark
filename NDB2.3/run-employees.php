<?php


require __DIR__ . '/../print_benchmark_result.php';
if (@!include __DIR__ . '/vendor/autoload.php') {
    echo 'Install Nette using `composer install`';
    exit(1);
}

$useCache = TRUE;

date_default_timezone_set('Europe/Prague');

$cacheStorage = new Nette\Caching\Storages\FileStorage(__DIR__ . '/temp');

$connection  = new Nette\Database\Connection('mysql:dbname=employees', 'root', '');
$structure   = new Nette\Database\Structure($connection, $cacheStorage);
$conventions = new Nette\Database\Conventions\DiscoveredConventions($structure);
$context     = new Nette\Database\Context($connection, $structure, $conventions, $useCache ? $cacheStorage : NULL);


$time = -microtime(TRUE);
ob_start();

foreach ($context->table('employees')->limit(500) as $employe) {
	echo "$employe->first_name $employe->last_name ($employe->emp_no)\n";
	echo "Salaries:\n";
	foreach ($employe->related('salaries') as $salary) {
		echo $salary->salary, "\n";
	}
	echo "Departments:\n";
	foreach ($employe->related('dept_emp') as $department) {
		echo $department->dept->dept_name, "\n";
	}
}

ob_end_clean();

print_benchmark_result('NDB 2.3', 'Nette: 2.3.x');
