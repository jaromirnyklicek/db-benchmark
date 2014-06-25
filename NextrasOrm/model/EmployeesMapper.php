<?php

namespace model;

use Nextras\Orm\Mapper\IMapper;
use Nextras\Orm\Mapper\Mapper;


class EmployeesMapper extends Mapper
{

	protected function createStorageReflection()
	{
		$reflection = parent::createStorageReflection();
		$reflection->addMapping('id', 'emp_no');
		return $reflection;
	}


	public function getManyHasManyParameters(IMapper $mapper)
	{
		if ($mapper instanceof DepartmentsMapper) {
			return ['dept_emp', ['emp_no', 'dept_no']];
		}

		return parent::getManyHasManyParameters($mapper);
	}

}
