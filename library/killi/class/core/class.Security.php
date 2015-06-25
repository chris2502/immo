<?php

/**
 *  @class Security
 *  @Revision $Revision: 4515 $
 *
 */
class Security
{
	// temps de calcul
	public static $_cumulateProcessTime = 0.0;
	private static $_key = 'vzd6VFbs';

	/**
	*  Cryptage
	*
	*  @param string $data Valeur à crypter
	*  @param string $encrypted_data Valeur cryptée
	*  @param string $user_key Suffixe
	*  @return boolean
	*
	*/
	public static function crypt($data, &$encrypted_data, $user_key = false)
	{
		// null ou (string)""
		if ($data === null || $data === '')
		{
			$encrypted_data = '';
			
			return TRUE;
		}
		
		if (is_array ( $data ))
		{
			$encrypted_data = array ();
			foreach ( $data as $key => $sub_data )
			{
				$crypted_sub_data = null;
				$crypted_key = null;
				
				self::crypt ( $sub_data, $crypted_sub_data );
				self::crypt ( $key, $crypted_key );
				
				$encrypted_data [$crypted_key] = $crypted_sub_data;
			}
			
			return true;
		}
		
		// On commence le compteur
		$start_time = microtime ( true );
		
		$key = md5 ( $user_key != false ? $user_key : self::$_key );
		
		$i = 0;
		$encrypted_data = '';
		foreach ( str_split ( $data ) as $char )
		{
			$encrypted_data .= chr ( ord ( $char ) ^ ord ( $key {$i ++ % strlen ( $key )} ) );
		}
		
		$encrypted_data = strtr ( base64_encode ( $encrypted_data ), '+=/', ',_-' );
		
		// On accumule le temps de calcul
		self::$_cumulateProcessTime += (microtime ( true ) - $start_time);
		
		return TRUE;
	}
	//.....................................................................
	/**
	*  Décryptage
	*
	*  @param string $data Valeur à décrypter
	*  @param string $decrypted_data Valeur décryptée
	*  @param string $user_key Suffixe
	*  @return boolean
	*
	*/
	public static function decrypt($data, &$decrypted_data, $user_key = false)
	{
		// null ou (string)""
		if ($data === null || $data === '')
		{
			$decrypted_data = '';
			
			return TRUE;
		}
		
		if (is_array ( $data ))
		{
			$decrypted_data = array ();
			foreach ( $data as $key => $sub_data )
			{
				$decrypted_sub_data = null;
				$decrypted_key = null;
				
				self::decrypt ( $sub_data, $decrypted_sub_data );
				
				if (! is_numeric ( $key ))
				{
					self::decrypt ( $key, $decrypted_key );
				}
				else
				{
					// retrocompatiblité
					$decrypted_key = $key;
				}
				
				$decrypted_data [$decrypted_key] = $decrypted_sub_data;
			}
			
			return true;
		}
		
		// On commence le compteur
		$start_time = microtime ( true );
		
		// les données sont rawurlencodées
		$data_to_decrypt = base64_decode ( strtr ( $data, ',_-', '+=/' ) );
		$decrypted_data = '';
		
		$key = md5 ( $user_key != false ? $user_key : self::$_key );
		
		$i = 0;
		foreach ( str_split ( $data_to_decrypt ) as $char )
		{
			$decrypted_data .= chr ( ord ( $char ) ^ ord ( $key {$i ++ % strlen ( $key )} ) );
		}
		
		// on test si la chaine décodée ressemble à quelque chose de printable (or binaire donc)
		// si ça ne ressemble à rien, les données ont été encodés à l'ancienne
		if (! ctype_print ( $decrypted_data ) && ctype_xdigit ( $data ))
		{
			// Suffixe
			$user_key = (! empty ( $user_key )) ? $user_key : (self::$_key);
			
			// @codeCoverageIgnoreStart
			if (! function_exists ( 'mcrypt_get_key_size' ))
			{
				throw new Exception ( "Impossible de trouver la fonction mcrypt_get_key_size verifier l'existance du module mcrypt" );
			}
			// @codeCoverageIgnoreEnd
			

			if (! empty ( $data ))
			{
				// Les variables ne sont pas toutes en cache, on les calcule
				if (! Cache::get('mcrypt_killi_key_' . $user_key, $key) || ! Cache::get('mcrypt_killi_iv_' . $user_key, $iv))
				{
					$key = sha1 ( md5 ( $user_key ) );
					$key_size = mcrypt_get_key_size ( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC );
					$key = mb_substr ( $key, 0, $key_size );
					$size = mcrypt_get_iv_size ( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC );
					$iv = mcrypt_create_iv ( $size, MCRYPT_DEV_URANDOM );
					
					Cache::set('mcrypt_killi_key_' . $user_key, $key);
					Cache::set('mcrypt_killi_iv_' . $user_key, $iv);
				}
				
				$decrypted_data = trim ( mcrypt_decrypt ( MCRYPT_RIJNDAEL_128, $key, pack ( "H*", $data ), MCRYPT_MODE_ECB, $iv ) );
			}
		}
		
		// On accumule le temps de calcul
		self::$_cumulateProcessTime += (microtime ( true ) - $start_time);
		
		return TRUE;
	}
	//---------------------------------------------------------------------
	/**
	*  Protection anti injection SQL
	*
	*  @param string $str Valeur à protéger
	*  @return Valeur protégée
	*
	*/
	public static function secure($str)
	{
		global $hDB;
		if (is_object ( $hDB ))
		{
			return $hDB->db_escape_string ( $str );
		}
		else
		{
			throw new Exception ( "erreur lors de la recuperation de hdb" );
		}
	}
}
