<?php

/**
 *  @class Constraints
 *  @Revision $Revision: 4198 $
 *
 */

class Constraints
{
	//.....................................................................
	public static function checkSize($value,$min,$max,&$error)
	{
		if (mb_strlen($value)<$min)
		{
			$error[] = "La taille de \"$value\" est < $min";
			return FALSE;
		}

		if (mb_strlen($value)>$max)
		{
			$error[] = "La taille de \"$value\" > $max";
			return FALSE;
		}

		$error = NULL;

		return TRUE;
	}
	//.....................................................................
	public static function checkMinMaxInteger($value, $min, $max, &$error)
	{
		if ($value < $min)
		{
			$error[] = 'La valeur doit être supérieure ou égale à '.$min;
			return FALSE;
		}
		elseif ($value > $max)
		{
			$error[] = 'La valeur doit être inférieure ou égale à '.$max;
			return FALSE;
		}
		$error = NULL;
		return TRUE;
	}
	//.....................................................................
	public static function checkMail($value,&$error)
	{
		if (preg_match("/^([a-zA-Z0-9]+(([\.\-\_]?[a-zA-Z0-9]+)+)?)\@(([a-zA-Z0-9]+[\.\-\_])+[a-zA-Z]{2,4})$/",$value)==0)
		{
			$error[] = "\"$value\" est mal formé";
			return FALSE;
		}
		else
		{
			$datamail = explode('@', $value);
			$domain = $datamail[1];

			if(!checkdnsrr($domain, 'MX'))
			{
				$error[] = "\"$value\" n\'est pas un email valide";
				return FALSE;
			}
		}

		return TRUE;
	}
	//.....................................................................
	public static function checkReg($value,$pattern,$insensitive,&$error)
	{
		if(preg_match("/$pattern/".($insensitive ? "i" : ""),$value)==0)
		{
			$error[] = "\"$value\" est mal formé";
			return FALSE;
		}

		return TRUE;
	}
	//.....................................................................
	public static function checkAlphaNum($value,&$error)
	{
		if (preg_match("/^[a-zA-Z0-9éèàçôêïë ûü]+$/",$value)==0)
		{
			$error[] = "\"$value\" n\'est pas alphanumerique";
			return FALSE;
		}

		return TRUE;
	}

	//.......................................................................
	public static function checkFirstName($value,&$error)
	{
		if (preg_match("/^([a-zA-Zéèàçôêïë ûü]+)([-]?)([a-zA-Zéèàçôêïë ûü]+)$/",$value)==0)
		{
			$error[] = "\"$value\" doit contenir uniquement des lettres ou un tiret !";
			return FALSE;
		}

		return TRUE;
	}

	//.....................................................................
	public static function checkFormData(&$error_list)
	{
		$valid = TRUE;
		$object_list = array();
		$data_list = array();
		foreach($_POST as $key => $value)
		{
			//---Si pas duo module/method
			if (mb_strpos($key, '/')===FALSE)
				continue;

			$raw = explode("/",$key);
			$module = $raw[0];
			$attr   = $raw[1];

			if(count($raw) >= 3 && $raw[count($raw)-1]=='timestamp')
			{
				continue;
			}

			if ($module=== 'crypt'/* || strpos($attr, 'reference') !== false*/)
				continue;

			if( class_exists( $module ) === false )
			{
				continue ;
			}

			$hInstance = ORM::getObjectInstance($module);

			if (isset($hInstance->$attr) && !is_array($value) && $hInstance->$attr->secureSet($value)===FALSE)
			{
				// WARNING: Technique du paillasson pour masquer un problème...
				if(!empty($hInstance->$attr->constraint_error))
				{
					$error_list[$key] = join('<br>', $hInstance->$attr->constraint_error);
				}
				$valid = FALSE;
			}

			if (!isset($object_list[$module]))
			{
				$object_list[$module] = $hInstance;
				$data_list[$module] = array();
			}
			$data_list[$module][$attr] = $value;
		}

		foreach ($object_list as $module => $hInstance)
		{
			if (method_exists($hInstance, 'globalConstraint') && !$hInstance->globalConstraint($data_list[$module], $error))
			{
				if (is_array($error))
				{
					$error_list = array_merge($error_list, $error);
				}
				else
				{
					$error_list[$module] = $error;
				}
				$valid = FALSE;
			}
		}
		return $valid;
	}
	//.....................................................................
	public static function checkDateMustBeInPast($value, &$error)
	{
		$date_f   = new DateTime();
		$date_f   = $date_f->createFromFormat('d/m/Y', trim($value));
		$date_now = new DateTime("now");

		if($date_f>$date_now)
			$error[] = 'La date doit etre dans inferieure à la date du jour.';
		else
			return TRUE;
	}
	//.....................................................................
	public static function checkAdresseMac($value, &$error)
	{
		$pattern='^[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}$';
		$not_used=array();
		if(!self::checkReg($value,$pattern,true,$not_used))
		{
			$error[] = 'Le format de l\'adresse mac est invalide ( ex: 00:07:CB:09:2B:18) !';
			return FALSE;
		}
		return TRUE;
	}
	//.....................................................................
	public static function checkPhoneNumber($value, &$error)
	{
		if(empty($value))
		{
			return TRUE;
		}

		if(strlen($value) != 10)
		{
			$error[] = 'Le numéro doit contenir 10 chiffres (sans aucun espace, ni caractère spécial).';
			return FALSE;
		}
		$pattern = '^[0-9]{10}$';
		if(!self::checkReg($value, $pattern, true, $not_used))
		{
			$error[] = 'Le numéro ne peut contenir que des chiffres.';
			return FALSE;
		}
		return TRUE;
	}
	//.....................................................................
	public static function checkMustBeNumeric($value, &$error)
	{
		$ret_is_numeric = is_numeric($value);
		if(!$ret_is_numeric)
		{
			$error[] = "Doit etre numerique";
			return False;
		}
		else
		{
			return True;
		}
	}
	//.....................................................................
	public static function checkDateMustBeInFutur($value, &$error)
	{
		if (!$value)
		{
			return TRUE;
		}

		if(!is_numeric($value))
		{
			$date_formats = array('d/m/Y', 'd/m/Y H:i:s');
			foreach ($date_formats as $format)
			{
				$date = DateTime::createFromFormat($format, $value);
				if ($date instanceOf DateTime)
				{
					break;
				}
			}
			if($date === FALSE)
			{
				$error[] = 'Le format de date "'.$value.'" est incorrect.';
				return FALSE;
			}

			$value = $date->getTimestamp();
		}

		if ($value >= time())
		{
			return TRUE;
		}

		$error[] = 'La date "'.date('d/m/Y H:i:s', $value).'" ne peut être antérieur à maintenant.';
		return FALSE;
	}
}
