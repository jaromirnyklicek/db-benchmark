<?php
/**
* Rozhrani pro LimitControly. Coz jsou komponenty pro vybrazni poctu zaznamu na stranku
*/
interface ILimitControl
{
	public function setValue($value);
		
	public function getValue();
	
	public function getDefaultValue();
	
	public function setDefaultValue($value);
	
	public function setUseAjax($value);
	
	public function exec();
   
	public function render();
}