<?php

/**
 *  @class TimeFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class TimeType extends DateTime implements JsonSerializable
{
	/**
	 * Return Date in ISO8601 format
	 *
	 * @return String
	 */
	public function __toString()
	{
		return $this->format('H:i:s');
	}
	
	public function jsonSerialize()
	{
		return $this->__toString();
	}
}

class TimeFieldDefinition extends ExtendedFieldDefinition
{
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		if (! preg_match ( '#^\s*([0-9]{2}):([0-9]{2}):([0-9]{2})$#', $value ))
		{
			$this->constraint_error [] = 'L\'heure doit être au format "HH:MM:SS".';
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
		if ($value == '00:00:00' || $value === NULL || $value === '')
		{
			$value = NULL;

			return TRUE;
		}

		// "H:i" to "H:i:00"
		if (preg_match ( '#^\s*([0-9]{2})\s*:\s*([0-9]{2})\s*$#', $value, $date_parts ))
		{
			$value = $date_parts [1] . ':' . $date_parts [2] . ':00';

			return TRUE;
		}
		// "H:i:s"
		else if (preg_match ( '#^\s*([0-9]{2})\s*:\s*([0-9]{2})\s*:\s*([0-9]{2})\s*$#', $value, $date_parts ))
		{
			$value = $date_parts [1] . ':' . $date_parts [2] . ':' . $date_parts [3];

			return TRUE;
		}
		// timestamp to "H:i:s"
		else if (is_numeric ( $value ))
		{
			$value = date ( 'H:i:s', $value );

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
		if(!($value instanceof TimeType))
		{
			if ($value == '00:00:00' || $value === NULL || $value === '')
			{
				$value = array (
					'value' => NULL
				);
	
				return TRUE;
			}
			
			if (is_numeric ( $value ))
			{
				$value = new TimeType('@'.$value);
			}
			else
			{
				preg_match ( '#^\s*([0-9]{2})\s*:\s*([0-9]{2})\s*:\s*([0-9]{2})\s*$#', $value, $date_parts );
		
				$value = new TimeType($date_parts [1] . ':' . $date_parts [2] . ':' . $date_parts [3]);
			}
		}
		
		$value = array (
			'value' => $value,
			'timestamp' => $value->getTimestamp(),
			'reference' => $value
		);
		
		// retrocompatibilite
		$value['html'] = $value['reference'];

		return TRUE;
	}
}
