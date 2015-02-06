<?php
/**
* Trida pro bezpecnosti operace
*
*/
final class Security
{

	/**
	* Zakodovani hesla slozitym algoritmem s osolenim
	*
	* @param mixed $password	Plain text heslo
	* @param mixed $salt		Solici retezec
	* @return string
	*/
	public static function encodePassword($password, $salt = 'DE9uZHJlaiBOb3ZhazC')
	{
		return substr(sha1($salt.md5($password)), 0, 32);
	}


	/**
	* Zakodovani retezce (nejcasteji hesla) symetrickym 3DEC algoritmem
	*
	* @param mixed $input	 Plain text heslo
	* @param mixed $key		 Klic
	* @return string
	*/
	public static function encode3DES($input, $key = 'DE9uZHJlaiBOb3ZhazC')
	{
		return mcrypt_ecb(MCRYPT_3DES, $key, $input, MCRYPT_ENCRYPT);
	}

	/**
	* Zakodovani retezce (nejcasteji hesla) symetrickym 3DEC algoritmem
	*
	* @param mixed $input	 Plain text heslo
	* @param mixed $key		 Klic
	* @return string
	*/
	public static function decode3DES($input, $key = 'DE9uZHJlaiBOb3ZhazC')
	{
		$s = mcrypt_ecb(MCRYPT_3DES, $key, $input, MCRYPT_DECRYPT);
		return str_replace(chr(0), '', $s);
	}


	/**
	* Vygeneruje nahodny klic
	*/
	public static function getRandomKey()
	{
		return sha1(microtime(true).mt_rand(10000,90000));
	}

	/**
	* Zakodovani retezece
	*
	* @param string $data
	* @param string $key
	* @return String
	*/
	public static function encrypt($data, $key)
	{
		$iv = '12345678';
		$cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
		mcrypt_generic_init($cipher, $key, $iv);
		$encrypted = mcrypt_generic($cipher, $data);
		mcrypt_generic_deinit($cipher);
		return $encrypted;
	}

	/**
	* Dekodovani retezece
	*
	* @param string $data
	* @param string $key
	* @return String
	*/
	public static function decrypt($data, $key)
	{
		$iv = '12345678';
		$cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
		mcrypt_generic_init($cipher, $key, $iv);
		$decrypted = mdecrypt_generic($cipher, $data);
		mcrypt_generic_deinit($cipher);
		return $decrypted;
	}


	/**
	* Vraceni osobniho SSL certifikatu
	*
	*/
	public static function getSSLClientCert()
	{
	  return isset($_SERVER['SSL_CLIENT_CERT']) ? $_SERVER['SSL_CLIENT_CERT'] : NULL;
	}

	/**
	* Zjisteni CN z osobniho SSL certifikatu
	*
	*/
	public static function getSSLCommonName()
	{
	  $cert = self::getSSLClientCert();
	  $arr = openssl_x509_parse($cert);
	  return $arr['subject']['CN'];
	}

	/**
	* Zalovani htmlspecialchars na polozky pole.
	*
	* @param array $arr
	* @return array
	*/
	public static function arrayHtmlspecialchars($arr)
	{
		foreach ($arr as &$item) {
			$item = htmlspecialchars($item, ENT_QUOTES);
		}
	  	return $arr;
	}
}