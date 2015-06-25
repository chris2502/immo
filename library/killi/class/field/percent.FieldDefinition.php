<?php

/**
 *  @class PercentFieldDefinition
 *  @Revision $Revision: 4558 $
 *
 */

class PercentFieldDefinition extends NumericFieldDefinition
{
	public $render = 'percent';
	//.....................................................................
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		if (! preg_match ( '#^\s*[0-9]*([,\.][0-9]*)?\s*%?\s*$#', $value ))
		{
			$this->constraint_error [] = "Le champ n'est pas un pourcentage !";
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
		if ($value === NULL)
		{
			return TRUE;
		}
		elseif (strpos ( $value, '%' ) === FALSE)
		{
			$value = str_replace ( ',', '.', ( string ) $value );
		}
		else
		{
			$value = str_replace ( ',', '.', (str_replace ( ',', '.', str_replace ( '%', '', ($value) ) )) / 100 );
		}
		return TRUE;
	}
	//.....................................................................
	/**
	 * Cast la valeur après lecture par l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function outCast(&$value)
	{
		if ($value === NULL)
		{
			$value = array (
				'value' => NULL
			);

			return TRUE;
		}

		$value = array (
			'value' => ( float ) str_replace ( ',', '.', $value ),
			'reference' => str_replace ( '.', ',', round ( $value * 100, 2 ) ) . ' %'
		);
		
		// retrocompatibilite
		$value['html'] = $value['reference'];
		
		return TRUE;
	}
}
