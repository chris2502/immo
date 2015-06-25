<?php

/**
 *  @class One2oneFieldDefinition
 *  @Revision $Revision: 4490 $
 *
 */

class One2oneFieldDefinition extends ExtendedFieldDefinition
{
	public $is_db_column = FALSE;

	//.....................................................................
	public function __construct($object_relation = NULL, $focused_field = NULL)
	{
		$this->setObjectRelation ( $object_relation );
		$this->setFieldRelation ( $focused_field );
		$this->is_db_column = False;
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return One2oneFieldDefinition
	 */
	public static function create($object_relation = NULL, $focused_field = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $object_relation, $focused_field );
	}
}
