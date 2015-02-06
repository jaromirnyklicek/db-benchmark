<?php

/**
 *
 */
class AdminPaginator extends Paginator
{	  
	/**
	 * Generates list of pages used for visual control. 
	 * @return array
	 */
	public function getSteps($steps = 5, $surround = 3)
	{
		$lastPage = $this->getPageCount() - 1;		  
		$page = $this->getPageIndex();
		if ($lastPage < 1) return array($page + $this->base);

		$surround = max(0, $surround);
		$arr = range(max(0, $page - $surround) + $this->base, min($lastPage, $page + $surround) + $this->base);

		$steps = max(1, $steps - 1);
		for ($i = 0; $i <= $steps; $i++) $arr[] = round($lastPage / $steps * $i) + $this->base;
		sort($arr);
		return array_values(array_unique($arr));
	}
}