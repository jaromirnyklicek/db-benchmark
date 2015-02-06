<?php

/**
* interface pro databazove multiformy.
* Pokud multiform implementuje toto rozhrani je po ulozeni formulare 
* volana metoda save()
*/

interface IMultiFormDb
{	
  
	/** 
	* Hlavni formular zavola metodu save() 
	* a preda ji sve ID jako $parentId
	*	
	* @param ORM $parentOrm
	* @param bool $forceInsert - pokud jde o novy zaznam (hlavniho formulare)
	*/
	public function save($parentOrm = NULL, $forceInsert = FALSE);
	
	/**
	* Hlavni formular zavola metodu load() pro nacteni
	* a preda ji sve ID jako $parentId
	* @param ORM $parentId
	*/
	public function load($parentOrm);
}