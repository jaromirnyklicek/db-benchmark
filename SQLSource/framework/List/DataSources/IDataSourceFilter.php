<?php
/**
* @package Lists
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
* @version $1.0$
*/


interface IDataSourceFilter 
{				
	/**
	* Vrati nejcasteji SQL konstrukci do WHERE
	* 
	*/
	public function buildSql();
}