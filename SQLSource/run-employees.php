<?php
require __DIR__ . '/../print_benchmark_result.php';

require_once __DIR__ . '/framework/Nette/Object.php';
require_once __DIR__ . '/framework/Nette/ObjectMixin.php';

require_once __DIR__ . '/framework/Database/Database.driver.php';
require_once __DIR__ . '/framework/Database/Mysqli.php';
require_once __DIR__ . '/framework/Database/Database.php';

require_once __DIR__ . '/framework/List/DataSources/IDataSource.php';

require_once __DIR__ . '/framework/Nette/Collections/ICollection.php';
require_once __DIR__ . '/framework/Nette/Collections/IList.php';
require_once __DIR__ . '/framework/Nette/Collections/Collection.php';
require_once __DIR__ . '/framework/Nette/Collections/ArrayList.php';
require_once __DIR__ . '/framework/List/Datatable.php';

require_once __DIR__ . '/framework/List/DataSources/BaseSource.php';
require_once __DIR__ . '/framework/List/DataSources/SQLOrder.php';
require_once __DIR__ . '/framework/List/DataSources/SQLSource.php';

$time = -microtime(TRUE);
ob_start();

$db = new Database([
	'persistent' => FALSE,
	'connection' => array
	(
		'type' => 'mysqli',
		'user' => 'root',
		'pass' => 'asdex',
		'host' => 'localhost',
		'port' => FALSE,
		'socket' => FALSE,
		'database' => 'employees'
	),
	'character_set' => 'utf8']);

$sqlSource = new SQLSource('SELECT * FROM employees LIMIT 500', $db);
$sqlSource->bind('salaries', 'SELECT * FROM salaries WHERE %s', 'emp_no', 'emp_no');
$sqlSource->bind('departments', 'SELECT de.emp_no, d.dept_name FROM dept_emp de
	JOIN departments d ON d.dept_no = de.dept_no
 	WHERE %s', 'emp_no', 'emp_no');

foreach ($sqlSource as $employee) {
	echo "$employee->first_name $employee->last_name ($employee->emp_no)\n";
	echo "Salaries:\n";
	foreach ($employee->salaries as $salary) {
		echo $salary->salary, "\n";
	}
	echo "Departments:\n";
	foreach ($employee->departments as $department) {
		echo $department->dept_name, "\n";
	}
}

ob_end_clean();

print_benchmark_result('SQLSource');