<?php

/**
 *  @class NumericFieldDefinition
 *  @Revision $Revision: 4588 $
 *
 */

class PhoneFieldDefinition extends ExtendedFieldDefinition
{
	private $default_prefix = NULL;
	//.....................................................................
	function __construct($prefix = '+33')
	{
		$this->setDefaultPrefix ( $prefix );
	
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
		$value = preg_replace('#\s#', '', $value);

		// test numéros français et internationaux
		if (! preg_match ( '#^[0-9]{10}$#', $value ) && ! preg_match ( '#^\+(?!33)#', $value ) && ! preg_match ( '#^\+33[0-9]{9}$#', $value ))
		{
			$this->constraint_error [] = "Le champ n'est pas un numéro de téléphone !";
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
		
		$value = preg_replace('#\s#', '', (string)$value);
		
		// ajout du prefix par défaut
		if(!preg_match ( '#^\+#', $value ))
		{
			$value = $this->default_prefix.substr($value,1);
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
		if ($value === NULL || preg_match ( '#^\+?0+$#', $value ))
		{
			$value = array (
				'value' => NULL
			);

			return TRUE;
		}
		
		$value = preg_replace('#\s#', '', $value);
		
		// ajout du prefix par défaut
		if(!preg_match ( '#^\+#', $value ))
		{
			$value = array (
				'value' => $this->default_prefix.substr($value,1)
			);
		}
		else
		{
			$value = array (
				'value' => $value
			);
		}

		return TRUE;
	}
	//.....................................................................
	/**
	 * Cast la valeur après lecture par l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	 public function setDefaultPrefix($prefix = '+33')
	 {
	 	$this->default_prefix = $prefix;
	 }
}
