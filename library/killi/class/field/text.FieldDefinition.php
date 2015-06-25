<?php

/**
 *  @class TextFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class TextFieldDefinition extends ExtendedFieldDefinition
{
	public $max_length = NULL;

	function __construct($max_length = NULL)
	{
		$this->setMaxLength ( $max_length );

		return $this;
	}
	//.....................................................................
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

		if ($this->max_length > 0)
		{
			$len = mb_strlen ( $value, 'UTF-8' );
			if ($len > $this->max_length)
			{
				$this->constraint_error [] = sprintf ( "Le champ ne doit pas dépasser %d caractères (ici %d)", $this->max_length, $len );

				return FALSE;
			}
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

		$value = trim ( ( string ) $value );

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
			'value' => trim ( ( string ) $value )
		);
		return TRUE;
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 *
	 * @return TextFieldDefinition
	 */
	public static function create($max_length = null)
	{
		$class_name = get_called_class ();

		return new $class_name ( $max_length );
	}
	//.....................................................................
	/**
	 * Définit la taille maximale
	 *
	 * @param int $max_length
	 * @return TextFieldDefinition
	 */
	public function setMaxLength($max_length)
	{
		$this->max_length = (( int ) $max_length > 0 ? ( int ) $max_length : NULL);

		return $this;
	}
}
