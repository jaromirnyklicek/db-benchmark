<?php

/**
 * Třída pro detekci IP adres a vývojářského prostředí aplikace
 *
 * @author Michal Kvita <michal.kvita@viaaurea.cz>
 * @copyright Copyright Via Aurea s. r. o.
 */
class IpEnvDetector
{
	/** Výchozí jména prostředí */
	const DEVELOPMENT = 'development';
	const PRODUCTION = 'production';
	const LOCAL = 'local';

	/** Ip adresy */
	const IPv4_LOCALHOST = '127\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}';
	const IPv6_LOCALHOST = '::1';
	const VA_PRIVATE_IP = '192\.168\.3\.[0-9]{1,3}';

	/** @var string Detekovaná ip adresa */
	private $ip;

	/** @var callback Callback pro detekci ip adresy */
	private $ipDetectCallback;

	/** @var array Pole vývojových ip adres */
	private $developmentIps = array(
		'81.19.1.213',
		'213.194.216.150',
	);

	/**
	 *
	 * @param callback $ipDetectCallback Callback pro detekce ip adresy, musí vracet string s IPv4 nebo IPv6 adresou
	 * @throws InvalidArgumentException
	 */
	public function __construct($ipDetectCallback = NULL)
	{
		if ($ipDetectCallback !== NULL) {
			if (!is_callable($ipDetectCallback)) {
				throw new InvalidArgumentException(sprintf('$ipDetectCallback must be callable, %s given', gettype($ipDetectCallback)));
			}

			$this->ipDetectCallback = $ipDetectCallback;
		}
	}

	/**
	 * Detekuje IP adresu
	 *
	 * @return string IP adresa
	 */
	protected function detectIp()
	{
		return self::detectIPBc();
	}

	/**
	 * Získá detekovanou IP adresu
	 *
	 * @return string
	 * @throws UnexpectedValueException
	 */
	public function getIp()
	{
		if (!isset($this->ip)) {
			$ip = isset($this->ipDetectCallback) ? call_user_func($this->ipDetectCallback) : $this->detectIp();

			if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
				throw new UnexpectedValueException('Returned detected ip must be in valid string IPv4 or IPv6 format');
			}

			// Kvůli normalizaci písmen IPv6
			$this->ip = strtoupper($ip);
		}

		return $this->ip;
	}

	/**
	 * Je detekovaná IP adresa IPv6
	 *
	 * @return boolean TRUE pokud je IPv6, FALSE pokud je IPv4
	 */
	public function isIpv6()
	{
		return self::detectIPv6Bc($this->getIp());
	}

	/**
	 * Přidá IP adresu do pole vývojových adres
	 *
	 * @param string $ip
	 * @return \IpEnvDetector
	 * @throws InvalidArgumentException
	 */
	public function addDevelopmentIp($ip)
	{
		if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
			throw new InvalidArgumentException('$ip must be in valid string IPv4 or IPv6 format');
		}

		$this->developmentIps[] = $ip;

		return $this;
	}

	/**
	 * Patří detekovaná IP adresa k vývojovým adresám
	 *
	 * @return boolean TRUE pokud ano, FALSE pokud ne
	 */
	public function isDevelopmentIp()
	{
		$ip = $this->getIp();
		return in_array($ip, $this->developmentIps);
	}

	/**
	 * Je detekovaná IP adresa lokální adresa
	 *
	 * @return boolean TRUE pokud ano, FALSE pokud ne
	 */
	public function isLocalIp()
	{
		$ip = $this->getIp();
		return preg_match('#^' . self::IPv4_LOCALHOST . '$#', $ip) || $ip === self::IPv6_LOCALHOST;
	}

	/**
	 * Je nastavena proměnná vývojového prostředí
	 *
	 * @return boolean TRUE pokud ano, FALSE pokud ne
	 */
	public function isDevelopmentEnv()
	{
		return getenv('SERVER') === self::DEVELOPMENT;
	}

	/**
	 * Detekuje vývojářský mód
	 *
	 * @return boolean TRUE pokud je vývojářský mód, FALSE pokud ne
	 */
	public function isDevelopment()
	{
		$ip = $this->getIp();
		return $this->isDevelopmentEnv() && ($this->isLocalIp() || $this->isDevelopmentIp() || preg_match('#^' . self::VA_PRIVATE_IP . '$#', $ip));
	}

	/**
	 * Detekuje konzolový mód
	 *
	 * @return boolean TRUE pokud je konzolový mód, FALSE pokud ne
	 */
	public function isConsole()
	{
		return PHP_SAPI === 'cli';
	}

	/**
	 * Získá název aktuálního prostředí
	 *
	 * @return string
	 */
	public function getEnvName()
	{
		return $this->isLocalIp() ? self::LOCAL : ($this->isDevelopmentEnv() ? self::DEVELOPMENT : self::PRODUCTION);
	}

	// Metody pro zpětnou kompatiblitu
	// -------------------------------------------------------------------------------------

	/**
	 * Výchozí detekce IP adresy (zpětná kompatibilita)
	 *
	 * @param boolean $asStr TRUE pro vrácení adresy jako string, FALSE pro vrácení jako integer
	 * @return string/integer
	 */
	public static function detectIPBc($asStr = TRUE)
	{
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
			if (array_key_exists($key, $_SERVER)) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
						if (!$asStr && self::detectIPv6Bc()) {
							return NULL;
						}
						// vrátí IPv4 nebo IPv6 ve zkrácené formě, pokud je požadavek na string, jinak IPv4 jako číslo
						return ($asStr) ? inet_ntop(inet_pton($ip)) : (int) IP2Long($ip);
					}
				}
			}
		}
	}

	/**
	 * Zjistí, zda je IP adresa IPv6
	 *
	 * @param string|NULL $ip NULL pro aplikaci na detekovanou IP adresu
	 * @return boolean TRUE pokud je IPv6, FALSE pokud je IPv4
	 */
	public static function detectIPv6Bc($ip = NULL)
	{
		return filter_var($ip === NULL ? self::detectIPBc() : $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== FALSE;
	}

}
