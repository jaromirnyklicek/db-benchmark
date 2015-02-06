<?php


require __DIR__ . '/../print_benchmark_result.php';
require __DIR__ . '/vendor/NotORM/NotORM.php';
require __DIR__ . '/NotORMStructure.php';

date_default_timezone_set('Europe/Prague');

$useCache = TRUE;
$limit = 500;

$connection = new PDO(
	'mysql:dbname=employees',
	'root',
	''
);

$notorm = new NotORM(
	$connection,
	new NotORMStructure,
	$useCache ? new NotORM_Cache_File(__DIR__ . '/temp/notorm') : NULL
);

$time = -microtime(TRUE);
ob_start();

foreach ($notorm->employees()->limit($limit) as $employee) {
	echo "$employee[first_name] $employee[last_name] ($employee[emp_no])\n";
	echo "Salaries:\n";
	foreach ($employee->salaries() as $salary) {
		echo $salary['salary'], "\n";
	}
	echo "Departments:\n";
	foreach ($employee->dept_emp() as $relationship) {
		echo $relationship->departments['dept_name'], "\n";
	}
}

ob_end_clean();

print_benchmark_result('NotOrm');
