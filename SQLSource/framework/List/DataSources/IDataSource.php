<?php

// objekty implementuji rozhrani IDataSource lze pouzit pro Listy
interface IDataSource
{	
	/**
	* Vrati nactene data.
	* 
	*/
	public function getItems();
	
	/**
	 * Nacte data k zobrazeni
	 * Parametr obsahuje nepovinne indexy:
	 * - where - filtrovaci formular   
	 * - page - pozadovana stranka
	 * - limit - pocet zaznamu na stranku
	 * - order - textova reprezentace razeni (napr.: table.id DESC)
	 * aletrenativa k textovemu $order:
	 * - order->column - instance Column - sloupec, podle ktere se bude radit
	 * - order->direction - emun('a','d') - smer razeni
	 *
	 * @param array $params
	 * @return SQLSource
	 */
	public function loadData($params = NULL);
	
	/**
	* Vrati celkovy pocet zaznamu
	* 
	*/
	public function getAllRows();
}