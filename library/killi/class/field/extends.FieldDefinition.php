<?php

/**
 *  @class ExtendsFieldDefinition
 *  @Revision $Revision: 4557 $
 *
 */

class ExtendsFieldDefinition extends ExtendedFieldDefinition
{
	public $render = 'many2one';
	//.....................................................................
	public function __construct($object_relation = NULL)
	{
		$this->setObjectRelation ( $object_relation );
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return ExtendsFieldDefinition
	 */
	public static function create($object_relation = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $object_relation );
	}
	//.....................................................................
	/**
	 * Précise si le champs peut être édité en vue formulaire ou création et à distance via NJB
	 *
	 * @param boolean $editable
	 * @return ExtendedFieldDefinition
	 */
	public function setEditable($editable = TRUE)
	{
		$this->editable = FALSE;

		return $this;
	}
}
