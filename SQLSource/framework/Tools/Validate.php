<?php

/**
 * Validační pravidla
 *
 * @author karel.kolaska@viaaurea.cz
 */
class Validate
{
	const INTEGER = '/^-?[0-9]+$/';
	const FLOAT	= '/^-?[0-9]*[.,]?[0-9]+$/';
	const EMAIL	= '/^[^@]+@[^@]+\.[a-z]{2,6}$/i';
	const URL = '/^.+\.[a-z]{2,6}(\\/.*)?$/i';
	
	public static function isInteger($string)
	{
		return preg_match(self::INTEGER, $string);
	}
	
	public static function isFloat($string)
	{
		return preg_match(self::FLOAT, $string);
	}
	
	public static function isEmail($string)
	{
		return preg_match(self::EMAIL, $string);
	}	
	
	public static function isUrl($string)
	{
		return preg_match(self::URL, $string);
	}
	
}
