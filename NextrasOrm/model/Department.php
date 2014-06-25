<?php

namespace model;

use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\ManyHasMany;


/**
 * @property string $name
 * @property ManyHasMany|Employee[] $employees {m:n EmployeesRepository $departments}
 */
class Department extends Entity
{
}
