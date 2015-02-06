<?php

/**
 * Seznam IP adres, ze kterych budou povoleny specialni funkce.
 * Napr. posilani databazovych dotazu do firebugu
 *
 * @author Michal Kvita <michal.kvita@viaaurea.cz> Úpravy
 * @deprecated since version r1071 - použij IpEnvDetector::detectIPBc($asStr);
 */
class VAIP
{
	/** @var IpEnvDetector */
	private static $ipDetector;

	/**
	 * Získá detektor ip/prostředí
	 *
	 * @return IpEnvDetector
	 */
	public static function getDetector()
	{
		if (!isset(self::$ipDetector)) {
			self::$ipDetector = new IpEnvDetector;
		}

		return self::$ipDetector;
	}

	/**
	 * Patří detekovaná IP adresa k vývojovým adresám
	 *
	 * @return boolean TRUE pokud ano, FALSE pokud ne
	 */
	public static function match()
	{
		return self::getDetector()->isDevelopmentIp();
	}

}
