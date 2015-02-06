<?php

use Model\Mapper;
use Model\Repository\EmployeesRepository;

require __DIR__ . '/../print_benchmark_result.php';

require_once __DIR__ . '/LeanMapper/dibi/dibi/dibi.php';
require_once __DIR__ . '/LeanMapper/VADateTime/Carbon/Carbon.php';
require_once __DIR__ . '/LeanMapper/VADateTime/VADateTime.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Object.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/ObjectMixin.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Entity.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/IMapper.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/DefaultMapper.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Data.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/InvalidAnnotationException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/InvalidArgumentException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/InvalidMethodCallException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/InvalidStateException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/InvalidValueException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/MemberAccessException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/RuntimeException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Exception/UtilityClassException.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Relationship/BelongsTo.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Relationship/BelongsToMany.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Relationship/BelongsToOne.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Relationship/HasMany.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Relationship/HasOne.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/Aliases.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/AliasesBuilder.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/AliasesParser.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/AnnotationsParser.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/EntityReflection.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/Property.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/PropertyValuesEnum.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/PropertyFactory.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/PropertyFilters.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Reflection/PropertyType.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Repository.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Result.php';
require_once __DIR__ . '/LeanMapper/LeanMapper/Row.php';

require_once __DIR__ . '/Model/Mapper.php';
require_once __DIR__ . '/Model/Entity/Employee.php';
require_once __DIR__ . '/Model/Entity/Salary.php';
require_once __DIR__ . '/Model/Entity/Department.php';
require_once __DIR__ . '/Model/Repository/EmployeesRepository.php';

date_default_timezone_set('Europe/Prague');

$limit = 500;

$connection = new DibiConnection(array(
	'username' => 'root',
	'password' => 'asdex',
	'database' => 'employees',
));


$time = -microtime(TRUE);
ob_start();

$mapper = new Mapper();
$employeesRepository = new EmployeesRepository($connection, $mapper);

foreach ($employeesRepository->findAll($limit) as $employee) {
	echo "$employee->firstName $employee->lastName ($employee->empNo)\n";
	echo "Salaries:\n";
	foreach ($employee->salaries as $salary) {
		echo $salary->salary, "\n";
	}
	echo "Departments:\n";
	foreach ($employee->departments as $department) {
		echo $department->deptName, "\n";
	}
}

ob_end_clean();

print_benchmark_result('VaLeanMapper');
