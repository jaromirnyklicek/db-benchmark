<?php

namespace Benchmark;

// based on https://github.com/Majkl578/employees-doctrine2

$useCache = TRUE;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Tools\Setup;


require __DIR__ . '/../print_benchmark_result.php';
if (@!include __DIR__ . '/vendor/autoload.php') {
    echo 'Install Nette using `composer install`';
    exit(1);
}

date_default_timezone_set('Europe/Prague');


$cache = new \Doctrine\Common\Cache\FilesystemCache(__DIR__ . '/temp');

$config = Setup::createAnnotationMetadataConfiguration(
	[__DIR__ . '/Benchmark/Entities'],
	TRUE,
	__DIR__ . '/Benchmark/Entities/Proxies',
	$useCache ? $cache : NULL,
	FALSE
);
$config->setProxyNamespace('Benchmark\Entities\Proxies');
$config->setAutoGenerateProxyClasses(TRUE);


// we need __toString on DateTime, since UoW converts composite primary keys to string
// (who the hell invented composite PKs :P)
Type::overrideType(Type::DATE, 'Benchmark\Types\DateType');
Type::overrideType(Type::DATETIME, 'Benchmark\Types\DateTimeType');


// TODO you may want to change this? ;)
$em = EntityManager::create(
	[
		'driver'   => 'pdo_mysql',
		'user'     => 'root',
		'password' => '',
		'dbname'   => 'employees',
	],
	$config
);


$time = -microtime(TRUE);
ob_start();

$qb = $em->createQueryBuilder()
	->from('Benchmark\Entities\Employee', 'e')
	->select('e')
	->innerJoin('e.salaries', 's')
	->addSelect('s')
	->innerJoin('e.affiliatedDepartments', 'd')
	->addSelect('d')
	->innerJoin('d.department', 'dd')
	->addSelect('dd')
	->setMaxResults(500)
	->getQuery();

$paginator = new Paginator($qb);

foreach ($paginator->getIterator() as $emp) {
	/** @var Employee $emp */

	// $output->writeln
	echo sprintf('%s %s (%d):', $emp->getFirstName(), $emp->getLastName(), $emp->getId()), PHP_EOL;

	// $output->writeln
	echo "\tSalaries:", PHP_EOL;
	foreach ($emp->getSalaries() as $salary) {
		// $output->writeln
		echo "\t\t", $salary->getAmount(), PHP_EOL;
	}

	// $output->writeln
	echo "\tDepartments:", PHP_EOL;
	foreach ($emp->getAffiliatedDepartments() as $department) {
		// $output->writeln
		echo "\t\t" . $department->getDepartment()->getName(), PHP_EOL;
	}
}

ob_end_clean();

print_benchmark_result('Doctrine2', 'Doctrine: ' . \Doctrine\Common\Version::VERSION);
