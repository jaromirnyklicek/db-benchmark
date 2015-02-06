<?php


interface IFormFilter
{	
	/**
	* Vrati cely SQL string do klausule WHERE.
	* 
	*/
	public function getFilter($filter);    

}