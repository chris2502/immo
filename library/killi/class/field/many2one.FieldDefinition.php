<?php

/**
 *  @class Many2oneFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class Many2oneFieldDefinition extends ExtendedFieldDefinition
{
	public function __construct($object_relation = NULL)
	{
		$this->setObjectRelation ( $object_relation );
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return Many2oneFieldDefinition
	 */
	public static function create($object_relation = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $object_relation );
	}

	//.....................................................................
	/**
	 * Cast la valeur après lecture par l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function outCast(&$value)
	{
		if(is_array($value)) return TRUE; // Fix temporaire (cf. Timothé)
		if ($value == 0)
		{
			$value = array (
				'value' => NULL
			);

			return TRUE;
		}

		$value = array (
			'value' => $value
		);
		return TRUE;
	}
}
