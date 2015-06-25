<?php

/**
 *  @class PriceFieldDefinition
 *  @Revision $Revision: 4558 $
 *
 */
class PriceFieldDefinition extends ExtendedFieldDefinition
{
	public $strict = true;
	//.....................................................................
	public function __construct($strict = true)
	{
		$this->strict = $strict;
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return Many2oneFieldDefinition
	 */
	public static function create($strict = true)
	{
		$class_name = get_called_class ();
		return new $class_name ( $strict );
	}
	
	//.....................................................................
	/**
	* Vérifie la valeur envoyée par l'utilisateur
	* @param mixed $value
	* @return boolean
	*/
	public function secure($value)
	{
		if ($this->strict and (! preg_match ( '#^([0-9\s]*)(,|.)?([0-9\s]*)(€)?\s*$#', $value )))
		{
			$this->constraint_error [] = "Le champ n'est pas un montant !";
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
		
		$parsed = str_replace ( ' ', '', (str_replace ( ',', '.', str_replace ( '€', '', $value ) )) );
		if (is_numeric ( $parsed ))
		{
			$value = $parsed;
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
		if (is_numeric ( $value ))
		{
			$value = array (
				'value' => ( float ) str_replace ( ',', '.', $value ),
				'reference' => (is_numeric ( $value )) ? number_format ( $value, 2, ',', ' ' ) . ' €' : $value
			);
		}
		else
		{
			$value = array (
				'value' => $value,
				'reference' => $value
			);
		}
		
		// retrocompatibilite
		$value['html'] = $value['reference'];
		
		return TRUE;
	}
}
