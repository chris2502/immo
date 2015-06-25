<?php

/**
 *  @class DatetimeFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class DatetimeType extends DateTime implements JsonSerializable
{
	/**
	 * Return Date in ISO8601 format
	 *
	 * @return String
	 */
	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}
	
	public function jsonSerialize()
	{
		return $this->__toString();
	}
}

class DatetimeFieldDefinition extends ExtendedFieldDefinition
{
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		if (! preg_match ( '#^([0-9]{2})/([0-9]{2})/([0-9]{4})\s*([0-9]{2}):([0-9]{2}):([0-9]{2})$#', $value ))
		{
			$this->constraint_error [] = 'La date doit être au format "JJ/MM/AAAA HH:MM:SS".';
			return FALSE;
		}

		return TRUE;
	}
	//.....................................................................
	/**
	 * Cast la valeur avant utilisation dans l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function inCast(&$value)
	{
		if ($value == '0000-00-00 00:00:00' || $value === NULL || $value === '')
		{
			$value = NULL;

			return TRUE;
		}
		
		// "Y-m-d H:i:s"
		if (preg_match ( '#^\s*([0-9]{4})\s*-\s*([0-9]{2})\s*-\s*([0-9]{2})\s*([0-9]{2})\s*:\s*([0-9]{2})\s*:\s*([0-9]{2})\s*$#', $value, $date_parts ))
		{
			$value = $date_parts [1] . '-' . $date_parts [2] . '-' . $date_parts [3] . ' ' . $date_parts [4] . ':' . $date_parts [5] . ':' . $date_parts [6];

			return TRUE;
		}
		// "d/m/Y H:i:s" to "Y-m-d H:i:s"
		else if (preg_match ( '#^\s*([0-9]{2})\s*/\s*([0-9]{2})\s*/\s*([0-9]{4})\s*([0-9]{2})\s*:\s*([0-9]{2})\s*:\s*([0-9]{2})\s*$#', $value, $date_parts ))
		{
			$value = $date_parts [3] . '-' . $date_parts [2] . '-' . $date_parts [1] . ' ' . $date_parts [4] . ':' . $date_parts [5] . ':' . $date_parts [6];

			return TRUE;
		}
		// timestamp to "Y-m-d H:i:s"
		else if (is_numeric ( $value ))
		{
			$value = date ( 'Y-m-d H:i:s', $value );

			return TRUE;
		}

		return FALSE;
	}
	//.....................................................................
	/**
	 * Cast la valeur après lecture par l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function outCast(&$value)
	{
		if(!($value instanceof DatetimeType))
		{
			if ($value === '0000-00-00 00:00:00' || $value === NULL || $value === '')
			{
				$value = array (
					'value' => NULL
				);
	
				return TRUE;
			}
	
			if (is_numeric ( $value ))
			{
				$value = new DatetimeType('@'.$value);
			}
			else
			{
				preg_match ( '#^\s*([0-9]{4})\s*-\s*([0-9]{2})\s*-\s*([0-9]{2})\s*([0-9]{2})\s*:\s*([0-9]{2})\s*:\s*([0-9]{2})\s*$#', $value, $date_parts );
		
				$value = new DatetimeType($date_parts [1] . '-' . $date_parts [2] . '-' . $date_parts [3] . ' ' . $date_parts [4] . ':' . $date_parts [5] . ':' . $date_parts [6]);
			}
		}
		
		$value = array (
			'value' => $value,
			'timestamp' => $value->getTimestamp(),
			'reference' => $value->format('d/m/Y H:i:s')
		);
		
		// retrocompatibilite
		$value['html'] = $value['reference'];
		
		return TRUE;
	}
}
