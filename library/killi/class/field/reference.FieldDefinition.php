<?php

/**
 *  @class ReferenceFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class ReferenceFieldDefinition extends ExtendedFieldDefinition
{
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		if (! is_string ( $value ))
		{
			$this->constraint_error [] = "Le champ n'est pas une chaîne de caractères !";
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
			$value = NULL;

			return TRUE;
		}

		if(is_array($value))
		{
			foreach($value AS &$v)
			{
				$v = (string) $v;
			}
			unset($v);
		}
		else
		{
			$value = (string) $value;
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
			'value' => ( string ) $value
		);
		return TRUE;
	}
}
