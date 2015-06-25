<?php

/**
 *  @class SerializedFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class SerializedFieldDefinition extends ExtendedFieldDefinition
{
	public $extract_csv = FALSE;
	//.....................................................................
	/**
	 * Cast la valeur avant utilisation dans l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function inCast(&$value)
	{
		$value = serialize ( $value );

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
		if(!is_string($value))
		{
			$value = array (
					'value' => $value
			);
			
			return TRUE;
		}
		
		$value = array (
			'value' => unserialize ( $value )
		);

		return TRUE;
	}
}
