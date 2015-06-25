<?php

/**
 *  @class Cache
 *  @Revision $Revision: 4300 $
 *
 */

class Cache
{
	public static $enabled = FALSE;

	/**
	 * Lecture du cache
	 *
	 * @param string $key Clé du cache
	 * @param mixed $result Valeur du cache
	 * @return boolean TRUE si l'entrée existe en cache
	 */
	public static function get($key, &$result = NULL)
	{
		if(!self::$enabled)
		{
			return FALSE;
		}

		$succes = FALSE;

		try
		{
			$result = apc_fetch($key, $succes);
		}
		catch (Exception $e) {}

		return $succes;
	}

	/**
	 * Ecriture dans le cache
	 *
	 * @param string $key Clé du cache
	 * @param mixed $result Valeur du cache
	 * @param string $ttl Duré de vie en seconde
	 * @return boolean TRUE si l'entrée est bien été écrite
	 */
	public static function set($key, $value, $ttl = NULL)
	{
		if(!self::$enabled)
		{
			return FALSE;
		}

		$succes = FALSE;

		try
		{
			$succes = apc_store($key, $value, $ttl);
		}
		catch (Exception $e) {}

		return $succes;
	}
}

Cache::$enabled = (function_exists('apc_fetch') == TRUE);
