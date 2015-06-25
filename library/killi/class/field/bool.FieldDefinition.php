<?php

/**
 *  @class BoolFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class BoolFieldDefinition extends ExtendedFieldDefinition
{
	/**
	 * Cast la valeur avant utilisation dans l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function inCast(&$value)
	{
		// si le champs n'est pas nullable, on remplace la valeur par FALSE
		if ($value === NULL && $this->required)
		{
			$value = '0';

			return TRUE;
		}

		if ($value === NULL && ! $this->required)
		{
			$value = NULL;

			return TRUE;
		}

		if ($value === 1 || $value === '1' || $value === TRUE)
		{
			$value = '1';

			return TRUE;
		}

		if ($value === 0 || $value === '0' || $value === FALSE)
		{
			$value = '0';

			return TRUE;
		}
	}
	//.....................................................................
	/**
	 * Cast la valeur après lecture par l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function outCast(&$value)
	{
		if ($value === NULL && ! $this->required)
		{
			$value = array (
				'value' => NULL
			);

			return TRUE;
		}

		if ($value === 1 || $value === '1' || $value === TRUE)
		{
			$value = TRUE;
		}

		if ($value === 0 || $value === '0' || $value === FALSE)
		{
			$value = FALSE;
		}

		// si le champs n'est pas nullable, on remplace la valeur par FALSE
		if ($value == NULL && $this->required)
		{
			$value = FALSE;
		}

		$value = array (
			'value' => $value === TRUE ? TRUE : FALSE,
			'reference' => $value ? 'OUI' : 'NON'
		);
		
		// retrocompatibilite
		$value['html'] = $value['reference'];
		
		return TRUE;
	}
	//.....................................................................
	/**
	 * Inverse les valeurs
	 *
	 * @param boolean $inverted
	 * @return BoolFieldDefinition
	 */
	public function setInverted($inverted = TRUE)
	{
		$this->object_relation = ($inverted === TRUE ? 'inverted' : NULL);

		return $this;
	}
}