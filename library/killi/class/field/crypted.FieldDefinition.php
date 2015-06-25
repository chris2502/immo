<?php

/**
 *  @class CryptedFieldDefinition
 *  @Revision $Revision: 4558 $
 *
 */

class CryptedFieldDefinition extends ExtendedFieldDefinition
{
	public $extract_csv = FALSE;
	public $render = 'text';
	//.....................................................................
	/**
	 * Cast la valeur avant utilisation dans l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function inCast(&$value)
	{
		Security::crypt ( $value, $encrypted_value );

		$value = $encrypted_value;

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
		Security::decrypt ( $value, $decrypted_value );

		$value = array (
			'value' => $decrypted_value
		);

		return TRUE;
	}
}
