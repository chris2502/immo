<?php

/**
 *  @class DateFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class DateType extends DateTime implements JsonSerializable
{
	/**
	 * Return Date in ISO8601 format
	 *
	 * @return String
	 */
	public function __toString()
	{
		return $this->format('Y-m-d');
	}
	
	public function jsonSerialize()
	{
		return $this->__toString();
	}
}

class DateFieldDefinition extends ExtendedFieldDefinition
{
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		if (! preg_match ( '#^([0-9]{2})/([0-9]{2})/([0-9]{4})\s*$#', $value ))
		{
			$this->constraint_error [] = 'La date doit être au format "JJ/MM/AAAA".';
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
		if ($value == '0000-00-00' || $value == '0000-00-00 00:00:00' || $value === NULL || $value === '')
		{
			$value = NULL;

			return TRUE;
		}

		// "Y-m-d ..."
		if (preg_match ( '#^\s*([0-9]{4})\s*-\s*([0-9]{2})\s*-\s*([0-9]{2})\s*#', $value, $date_parts ))
		{
			$value = $date_parts [1] . '-' . $date_parts [2] . '-' . $date_parts [3];

			return TRUE;
		}
		// "d/m/Y ..." to "Y-m-d"
		else if (preg_match ( '#^\s*([0-9]{2})\s*/\s*([0-9]{2})\s*/\s*([0-9]{4})\s*#', $value, $date_parts ))
		{
			$value = $date_parts [3] . '-' . $date_parts [2] . '-' . $date_parts [1];

			return TRUE;
		}
		// timestamp to "Y-m-d"
		else if (is_numeric ( $value ))
		{
			$value = date ( 'Y-m-d', $value );

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
		if(!($value instanceof DateType))
		{
			if ($value == '0000-00-00' || $value == '0000-00-00 00:00:00' || $value === NULL || $value === '')
			{
				$value = array (
					'value' => NULL
				);
	
				return TRUE;
			}
			
			if (is_numeric ( $value ))
			{
				$value = new DateType('@'.$value);
			}
			else
			{
				preg_match ( '#^\s*([0-9]{4})\s*-\s*([0-9]{2})\s*-\s*([0-9]{2})\s*(([0-9]{2})\s*:\s*([0-9]{2})\s*:\s*([0-9]{2})\s*)?$#', $value, $date_parts );
		
				$value = new DateType($date_parts [1] . '-' . $date_parts [2] . '-' . $date_parts [3]);
			}
		}
		
		$value = array (
			'value' => $value,
			'timestamp' => $value->getTimestamp(),
			'reference' => $value->format('d/m/Y')
		);
		
		// retrocompatibilite
		$value['html'] = $value['reference'];
		
		return TRUE;
	}
}
