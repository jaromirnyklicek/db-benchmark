<?php
/**
* Rozhrani pro tridy, ktere vytvari instance trid. 
* @see class ClassFactory;
*/
interface IFactory
{	
	public function newInstance();	
}
