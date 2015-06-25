<?php

/**
 *  @class NumericFieldDefinition
 *  @Revision $Revision: 4558 $
 *
 */

class NumericFieldDefinition extends ExtendedFieldDefinition
{
	public $render = 'int';
	//.....................................................................
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		if (! preg_match ( '#^\s*-?[0-9]*((,|.)[0-9]*)?\s*$#', $value ))
		{
			$this->constraint_error [] = "Le champ n'est pas un numérique !";
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

		$value = ( float ) preg_replace ( '/,/', '.', ( string ) $value );

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
			'value' => ( float ) preg_replace ( '/,/', '.', $value )
		);
		return TRUE;
	}
}
