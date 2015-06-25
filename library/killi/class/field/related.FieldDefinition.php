<?php

/**
 *  @class RelatedFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class RelatedFieldDefinition extends ExtendedFieldDefinition
{
	public $editable = FALSE;
	public $is_db_column = FALSE;

	//.....................................................................
	public function __construct($local_field = NULL, $focused_field = NULL)
	{
		$this->setObjectRelation ( $local_field );
		$this->setFieldRelation ( $focused_field );
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return RelatedFieldDefinition
	 */
	public static function create($local_field = NULL, $focused_field = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $local_field, $focused_field );
	}
}
