<?php

/**
 *  @class Many2manyFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class Many2manyFieldDefinition extends ExtendedFieldDefinition
{
	public $is_db_column = FALSE;

	//.....................................................................
	public function __construct($object_relation = NULL)
	{
		$this->setObjectRelation ( $object_relation );
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return Many2manyFieldDefinition
	 */
	public static function create($object_relation = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $object_relation );
	}

	public function setLiaisonObject($object)
	{
		$this->object_liaison = $object;
		return $this;
	}

	public function outCast(&$value)
	{
		if(isset($value['value'])) return TRUE;

		$value = array (
			'value' => $value
		);

		return TRUE;
	}
}
