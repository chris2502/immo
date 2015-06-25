<?php

/**
 *  @class JSONFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class JSONFieldDefinition extends ExtendedFieldDefinition
{
	public $extract_csv = FALSE;
	//.....................................................................
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		if(json_encode(json_decode($value)) != $value)
		{
			$this->constraint_error [] = "Les données au format JSON ne sont pas bonnes. Vérifier la syntaxe.";
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
		if (! is_string ( $value ))
		{
			$value = json_encode ( $value );
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
		$value = array (
			'value' => is_string($value) ? json_decode ( $value, true ) : $value
		);

		return TRUE;
	}
}
