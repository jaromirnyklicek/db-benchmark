<?php

use \model;

require __DIR__ . '/../print_benchmark_result.php';
if (@!include __DIR__ . '/vendor/autoload.php') {
    echo 'Install Nette using `composer install`';
    exit(1);
}


require_once __DIR__ . '/model/Department.php';
require_once __DIR__ . '/model/DepartmentsMapper.php';
require_once __DIR__ . '/model/DepartmentsRepository.php';
require_once __DIR__ . '/model/Employee.php';
require_once __DIR__ . '/model/EmployeesMapper.php';
require_once __DIR__ . '/model/EmployeesRepository.php';
require_once __DIR__ . '/model/Salary.php';
require_once __DIR__ . '/model/SalariesMapper.php';
require_once __DIR__ . '/model/SalariesRepository.php';

date_default_timezone_set('Europe/Prague');

$useCache = TRUE;
$limit = 500;

$cacheStorage = new Nette\Caching\Storages\FileStorage(__DIR__ . '/temp');

$connection  = new Nette\Database\Connection('mysql:dbname=employees', 'root', '');
$structure   = new Nette\Database\Structure($connection, $useCache ? $cacheStorage : NULL);
$conventions = new Nette\Database\Conventions\DiscoveredConventions($structure);
$context     = new Nette\Database\Context($connection, $structure, $conventions, $useCache ? $cacheStorage : NULL);

$repositories = [
	'employees'   => new model\EmployeesRepository(new model\EmployeesMapper($context)),
	'salaries'   => new model\SalariesRepository(new model\SalariesMapper($context)),
	'departments' => new model\DepartmentsRepository(new model\DepartmentsMapper($context)),
];

$time = -microtime(TRUE);
ob_start();

$mf = new \Nextras\Orm\Model\SimpleModelFactory($cacheStorage, $repositories);
$model = $mf->create();

$employees = $model->employees->findOverview($limit);

foreach ($employees as $employee) {
	echo "$employee->firstName $employee->lastName ($employee->id)\n";
	echo "Salaries:\n";
	foreach ($employee->salaries as $salary) {
		echo "-", $salary->salary, "\n";
	}
	echo "Departments:\n";
	foreach ($employee->departments as $department) {
		echo "-", $department->name, "\n";
	}
}

ob_end_clean();

print_benchmark_result('Nextras\Orm');
